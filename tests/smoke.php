<?php
/**
 * Duman testi — çalışan bir sunucuya karşı koşar.
 *
 *   php tests/smoke.php  (önce: yerel sunucuyu başlatın)
 *
 * Yönetici girişi yapar, her ekranı açar ve sayfada olması gereken metni arar.
 * Boş 200 dönen kırık sayfaları ve giriş ekranına düşen rotaları yakalar.
 */
$base = 'http://localhost:8145';
$jar  = sys_get_temp_dir() . '/babyloon_smoke.txt';
@unlink($jar);

function req(string $url, ?array $post = null): array {
    global $jar;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 25,
    ]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $b = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    return [(string) $b, $code, $final];
}

/* Giriş */
[$h] = req("$base/admin/giris");
preg_match('~name="_token" value="([^"]+)"~', $h, $m);
[$after, , $url] = req("$base/admin/giris", ['_token' => $m[1] ?? '', 'username' => 'admin', 'password' => 'babyloon2026']);
$loggedIn = str_contains($after, 'okunmamış mesaj');
echo ($loggedIn ? "✓" : "✗") . " yönetici girişi\n";
if (!$loggedIn) { exit(1); }

/* İletişim formundan bir mesaj bırak — panel testinde onu arayacağız.
   Daha önce elle girilmiş bir kayda bakılıyordu; veritabanı sıfırlanınca
   test kırılıyordu. Artık test kendi verisini üretiyor. */
$damga = 'Duman testi ' . substr(bin2hex(random_bytes(4)), 0, 6);
[$h] = req("$base/tr/iletisim");
preg_match('~name="_token" value="([^"]+)"~', $h, $t);
req("$base/tr/iletisim", [
    '_token' => $t[1] ?? '', 'name' => $damga, 'email' => 'test@ornek.com',
    'phone' => '', 'subject' => 'Duman testi', 'body' => 'Bu kayıt otomatik testten geldi.',
    'website' => '',
]);

/* Rota => sayfada bulunması gereken metin */
$checks = [
    '/admin'                              => 'Hızlı işlemler',
    '/admin/kaynak/menu_items/liste'      => 'Menü Ürünleri',
    '/admin/kaynak/menu_items/yeni'       => 'Ürün adı',
    '/admin/kaynak/menu_items/duzenle/1'  => 'Kaydı sil',
    '/admin/kaynak/menu_categories/liste' => 'Kısa ad',
    '/admin/kaynak/slides/liste'          => 'Ana Sayfa Slaytları',
    '/admin/kaynak/gallery/liste'         => 'Galeri',
    '/admin/kaynak/posts/liste'           => 'Blog',
    '/admin/kaynak/features/liste'        => 'Öne Çıkanlar',
    '/admin/kaynak/testimonials/liste'    => 'Müşteri Yorumları',
    '/admin/kaynak/team/liste'            => 'Ekip',
    '/admin/kitapcik'                     => 'Menü PDF',
    '/admin/kaynak/menu_pages/liste'      => 'Menü Kitapçığı',
    '/admin/saatler'                      => 'Çalışma Saatleri',
    '/admin/mesajlar'                     => $damga,
    '/admin/qr'                           => 'Baskıya hazır kart',
    '/admin/qr/kart/1'                    => 'Menü telefonunuzda',
    '/admin/instagram'                    => 'Erişim jetonu nasıl alınır',
    '/admin/ayarlar'                      => 'Para birimi simgesi',
    '/admin/kullanicilar'                 => 'Şifrenizi değiştirin',
];

$fail = 0;
foreach ($checks as $path => $needle) {
    [$body, $code] = req($base . $path);
    $onLogin = str_contains($body, 'Devam etmek için giriş yapın');
    $leak = str_contains($body, 'Fatal error') || str_contains($body, '<b>Warning</b>');
    $ok = $code === 200 && !$onLogin && !$leak && str_contains($body, $needle);
    if (!$ok) {
        $fail++;
        printf("✗ %-38s HTTP %d %s\n", $path, $code,
            $onLogin ? '(giriş sayfasına düştü)' : ($leak ? '(PHP hatası)' : "(\"$needle\" yok)"));
    } else {
        printf("✓ %-38s\n", $path);
    }
}

/* Testin bıraktığı mesajı sil — panelde birikmesin */
[$mh] = req("$base/admin/mesajlar");
$pos = strpos($mh, $damga);
if ($pos !== false && preg_match('~mesajlar/sil/(\d+)~', substr($mh, $pos), $mid)
    && preg_match('~name="_token" value="([^"]+)"~', $mh, $mt)) {
    req("$base/admin/mesajlar/sil/{$mid[1]}", ['_token' => $mt[1]]);
    [$after] = req("$base/admin/mesajlar");
    if (str_contains($after, $damga)) { $fail++; echo "✗ test mesajı silinemedi
"; }
    else { echo "✓ test mesajı temizlendi
"; }
} else { $fail++; echo "✗ test mesajı panelde bulunamadı
"; }

/* 404 gerçekten 404 mü? */
$ch = curl_init("$base/admin/yok-boyle-sayfa");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_COOKIEJAR=>$jar, CURLOPT_COOKIEFILE=>$jar, CURLOPT_FOLLOWLOCATION=>false]);
curl_exec($ch);
$c404 = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($c404 === 404) { echo "✓ /admin/yok-boyle-sayfa 404\n"; } else { $fail++; printf("✗ /admin/yok-boyle-sayfa HTTP %d (404 bekleniyordu)\n", $c404); }

/* Genel sayfalar: kart düzeni ve içerik parçaları yerinde mi? */
$public = [
    '/tr'             => ['Menüde neler var?', 'tband__qr', 'hcard', 'tbook'],
    '/en'             => ['What’s on the menu?', 'The menu in your pocket'],
    '/tr/hakkimizda'  => ['Adını bizden alan tabaklar', 'thours__card', 'Babil İskender'],
    '/tr/galeri'      => ['Mutfaktan kareler', 'class="gal"'],
    '/tr/iletisim'    => ['Bize yazın', 'tinfo', 'Çalışma saatleri'],
    '/tr/menu'        => ['phead__t', 'data-book'],
    '/tr/qr-menu'     => ['qrhead__top', 'kat-pizza'],
    '/en/qr-menu'     => ['New Dishes', 'Soy Sauce Chicken'],
];
foreach ($public as $path => $needles) {
    [$body, $code] = req($base . $path);
    $miss = array_filter($needles, fn($n) => $n !== '' && !str_contains($body, $n));
    $leak = str_contains($body, 'Fatal error') || str_contains($body, '<b>Warning</b>') || str_contains($body, '<b>Deprecated</b>');
    // Dura iskeletinden kalan yer tutucu desen geri gelmesin
    $mosaic = str_contains($body, 'mosaic');
    if ($code !== 200 || $miss || $leak || $mosaic) {
        $fail++;
        printf("✗ %-38s HTTP %d %s
", $path, $code,
            $leak ? '(PHP hatası)' : ($mosaic ? '(mozaik izi)' : '(' . implode(', ', $miss) . ' yok)'));
    } else {
        printf("✓ %-38s
", $path);
    }
}

/* Doğrulanmamış saatler Google'a gitmesin */
[$home] = req("$base/tr");
$confirmed = str_contains($home, 'openingHoursSpecification');
echo ($confirmed ? 'ℹ' : '✓') . " JSON-LD saatleri " . ($confirmed ? 'gönderiliyor (panelde doğrulanmış)' : 'gizli (doğrulanmadı)') . "
";

/* Genel 404 sayfası */
$ch = curl_init("$base/tr/yok-boyle-sayfa");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true]);
$b404 = (string) curl_exec($ch);
$p404 = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($p404 === 404 && str_contains($b404, 'perr__code')) { echo "✓ /tr/yok-boyle-sayfa 404
"; }
else { $fail++; printf("✗ /tr/yok-boyle-sayfa HTTP %d
", $p404); }

/* QR indirmeleri */
foreach ([['svg','image/svg'], ['png','image/png']] as [$fmt, $mime]) {
    $ch = curl_init("$base/admin/qr/$fmt/1");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_COOKIEJAR=>$jar, CURLOPT_COOKIEFILE=>$jar]);
    $b = curl_exec($ch);
    $ct = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    if (str_contains($ct, $mime) && strlen($b) > 200) { echo "✓ QR $fmt indirme (" . strlen($b) . " bayt)\n"; }
    else { $fail++; echo "✗ QR $fmt indirme ($ct)\n"; }
}

echo "\n" . ($fail ? "$fail başarısız" : "hepsi geçti") . "\n";
exit($fail ? 1 : 0);
