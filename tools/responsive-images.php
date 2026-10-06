<?php
declare(strict_types=1);

/**
 * Var olan görseller için duyarlı türevleri toplu üretir.
 *
 *   php tools/responsive-images.php uploads/slider
 *   php tools/responsive-images.php uploads/gallery 700
 *   php tools/responsive-images.php uploads/menu --eksik   (yalnız türevi olmayanlar)
 *
 * Panelden yüklenen görsellerin türevleri zaten yükleme anında üretilir
 * (app/lib/imagevariants.php). Bu betik yalnızca eskiden yüklenmiş ya da
 * doğrudan klasöre kopyalanmış dosyalar için gerekir.
 *
 * Üretim mantığı tek yerde: app/lib/imagevariants.php.
 */

require __DIR__ . '/../app/lib/imagevariants.php';

// --eksik: türevi zaten olan kaynağı atla (dağıtımda her seferinde
// yüzlerce dosyayı baştan üretmesin). Diğer argümanlar konumsal.
$args  = array_values(array_filter(array_slice($argv, 1), fn($a) => $a !== '--eksik'));
$eksik = in_array('--eksik', $argv, true);

$dizin = $args[0] ?? 'uploads/slider';
$genislikler = isset($args[1])
    ? array_map('intval', explode(',', $args[1]))
    : IMG_VARIANT_WIDTHS;

$kok = dirname(__DIR__);
$yol = $kok . '/' . trim($dizin, '/');
if (!is_dir($yol)) { fwrite(STDERR, "Klasör yok: $yol\n"); exit(1); }

$kaynaklar = array_filter(
    array_merge(glob("$yol/*.jpg") ?: [], glob("$yol/*.png") ?: []),
    // Kendi ürettiklerimizi yeniden işleme
    fn($f) => !str_contains(basename($f), '@')
);

$uretilen = 0; $toplam = 0;

foreach ($kaynaklar as $src) {
    if ($eksik && glob(preg_replace('~\.(jpe?g|png)$~i', '', $src) . '@*.jpg')) { continue; }
    $n = image_variants_make($src, $genislikler);
    $uretilen += $n;

    $taban = preg_replace('~\.(jpe?g|png)$~i', '', $src);
    foreach (array_merge(glob("$taban@*.*") ?: [], glob("$taban.webp") ?: []) as $f) {
        $toplam += filesize($f);
    }

    $bilgi = @getimagesize($src);
    printf("  %-26s %sx%s → %d türev\n", basename($src),
           $bilgi[0] ?? '?', $bilgi[1] ?? '?', $n);
}

printf("\n%d kaynak, %d türev dosyası (%.1f MB).\n",
       count($kaynaklar), $uretilen, $toplam / 1048576);
