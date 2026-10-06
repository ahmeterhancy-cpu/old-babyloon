<?php
/**
 * Varlık dosyalarının bütünlüğü — sunucu gerekmez.
 *
 *   php tests/assets_test.php
 *
 * Buradaki ilk denetim gerçek bir olaydan doğdu: bir CSS bloğu düzenlenirken
 * başıboş bir "}" kaldı ve tarayıcı o noktadan sonraki BÜTÜN kuralları sessizce
 * atladı. Sayfa hatasız görünüyordu, yalnızca stiller uygulanmıyordu.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$pass = 0; $fail = 0;

function ok(string $name): void { global $pass; $pass++; echo "  ✓ $name\n"; }
function no(string $name, string $why): void { global $fail; $fail++; echo "  ✗ $name\n      $why\n"; }

/* ---- 1. CSS: süslü parantez dengesi ------------------------------------- */
echo "CSS bütünlüğü\n";
foreach (glob("$root/assets/css/*.css") as $file) {
    $css = (string) file_get_contents($file);
    $clean = preg_replace('~/\*.*?\*/~s', '', $css) ?? '';
    $open = substr_count($clean, '{');
    $close = substr_count($clean, '}');
    $name = basename($file);

    if ($open === $close) {
        ok("$name — parantezler dengeli ($open blok)");
    } else {
        no("$name — parantez dengesi", "{ = $open, } = $close");
    }

    // Kapanmamış yorum
    if (substr_count($css, '/*') !== substr_count($css, '*/')) {
        no("$name — yorum blokları", 'açılan ve kapanan yorum sayısı farklı');
    }
}

/* ---- 2. CSS: tanımsız değişken kullanımı -------------------------------- */
echo "CSS değişkenleri\n";
foreach (glob("$root/assets/css/*.css") as $file) {
    $css = (string) file_get_contents($file);
    $name = basename($file);

    preg_match_all('~--([a-z0-9-]+)\s*:~i', $css, $defs);
    preg_match_all('~var\(\s*--([a-z0-9-]+)~i', $css, $uses);

    // motion.js bunları çalışma anında atar; CSS tarafında fallback'lı kullanılır
    // d = kademe gecikmesi, py = parallax kayması, p = kaydırma ilerlemesi
    $jsSet = ["d", "py", "p"];
    $defined = array_merge(array_unique($defs[1]), $jsSet);
    $used = array_unique($uses[1]);
    $missing = array_values(array_diff($used, $defined));

    if (!$missing) {
        ok("$name — tüm değişkenler tanımlı (" . count($used) . ' kullanım)');
    } else {
        no("$name — tanımsız değişken", implode(', ', array_map(fn($m) => "--$m", $missing)));
    }
}

/* ---- 3. Marka varlıkları ------------------------------------------------
 * Logo, favicon, mozaik ve sosyal kapak şu an YER TUTUCU. Bu blok renk
 * beklemiyor (Old Babyloon'un paleti belli değil); dosyaların var olduğunu,
 * ölçeklenebilir olduğunu ve yer tutucu oldukları için hâlâ işaretli
 * durduklarını denetler. Gerçek logo geldiğinde "yer tutucu" uyarısı düşer.
 */
echo "Marka varlıkları
";
$assets = [
    'assets/img/logo.svg',
    'assets/img/logo-white.svg',
    'assets/img/favicon.svg',
    'assets/img/og-cover.svg',
];
$yerTutucu = [];
foreach ($assets as $rel) {
    $path = "$root/$rel";
    if (!is_file($path)) { no($rel, 'dosya yok'); continue; }
    $svg = (string) file_get_contents($path);

    if (!str_starts_with(ltrim($svg), '<svg')) { no($rel, 'svg etiketiyle başlamıyor'); continue; }
    if (!str_contains($svg, 'viewBox')) { no($rel, 'viewBox yok — ölçeklenemez'); continue; }

    if (stripos($svg, 'YER TUTUCU') !== false) { $yerTutucu[] = basename($rel); }
    ok($rel . ' (' . round(strlen($svg) / 1024, 1) . ' KB)');
}

/* Sosyal kapağın rasterı OG için şart — SVG'yi çoğu tarayıcı okumuyor */
$og = "$root/assets/img/og-cover.jpg";
if (is_file($og) && ($b = @getimagesize($og)) && $b[0] >= 1200 && $b[1] >= 630) {
    ok('og-cover.jpg ' . $b[0] . 'x' . $b[1]);
} else {
    no('og-cover.jpg', 'yok ya da 1200x630 boyutundan küçük');
}

/* Dura Coffee'nin kimliği sızmamalı — kopyalanan projede en kritik denetim */
$sizinti = [];
foreach (glob("$root/assets/img/*.svg") ?: [] as $f) {
    $icerik = (string) file_get_contents($f);
    // Yer tutucu dosyalardaki açıklama notu hariç: orada "Dura" geçmesi kasıtlı
    $icerik = preg_replace('~<!--.*?-->~s', '', $icerik) ?? $icerik;
    if (stripos($icerik, 'dura') !== false) { $sizinti[] = basename($f); }
}
if ($sizinti) { no('marka sızıntısı', 'Dura izi: ' . implode(', ', $sizinti)); }
else { ok('görsellerde Dura Coffee izi yok'); }

if ($yerTutucu) {
    echo "  ⚠ yer tutucu: " . implode(', ', $yerTutucu)
       . " — gerçek marka varlıkları bekleniyor
";
}

/* ---- 4. Palet jetonları yerinde mi? ------------------------------------
 * Old Babyloon logosundan: siyah zemin, #FDF001 sarı. Sarı yalnız siyah
 * zeminde ya da siyah yazılı dolguda kullanılır (beyaz üstünde 1,1:1).
 */
echo "Palet jetonları (logo)\n";
$app = (string) file_get_contents("$root/assets/css/app.css");
$brand = ['--yellow:' => '#FDF001', '--black:' => '#12100C'];
foreach ($brand as $varName => $hex) {
    if (preg_match('~' . preg_quote($varName, '~') . '\s*' . preg_quote($hex, '~') . '~i', $app)) {
        ok("$varName $hex");
    } else {
        no("$varName", "kılavuzdaki $hex değeri bulunamadı");
    }
}

/* ---- 5. JS: kaba sözdizimi denetimi ------------------------------------- */
echo "JavaScript\n";
foreach (glob("$root/assets/js/*.js") as $file) {
    $js = (string) file_get_contents($file);
    $name = basename($file);
    $clean = preg_replace('~//[^\n]*|/\*.*?\*/~s', '', $js) ?? '';
    $pairs = ['{' => '}', '(' => ')', '[' => ']'];
    $bad = [];
    foreach ($pairs as $o => $c) {
        if (substr_count($clean, $o) !== substr_count($clean, $c)) { $bad[] = "$o$c"; }
    }
    $bad ? no("$name — parantez dengesi", implode(', ', $bad)) : ok("$name — parantezler dengeli");
}

echo "\n$pass geçti, $fail başarısız.\n";
exit($fail ? 1 : 0);
