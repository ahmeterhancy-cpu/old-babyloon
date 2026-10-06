<?php
/**
 * QR üreteci regresyon testi — bağımlılık yok.
 *
 *   php tests/qrcode_test.php
 *
 * Buradaki beklenen değerler, geliştirme sırasında bağımsız bir kodlayıcıyla
 * (npm "qrcode") karşılaştırılarak doğrulandı ve üretilen PNG'ler jsQR ile
 * okunarak 203 vakada teyit edildi. Tablolara dokunulursa bu test kırılır.
 */

declare(strict_types=1);

require __DIR__ . '/../app/lib/qrcode.php';

$pass = 0; $fail = 0;

function check(string $name, mixed $got, mixed $want): void
{
    global $pass, $fail;
    if ($got === $want) { $pass++; echo "  ✓ $name\n"; return; }
    $fail++;
    echo "  ✗ $name\n";
    echo "      beklenen: " . (is_array($want) ? implode(' ', $want) : var_export($want, true)) . "\n";
    echo "      bulunan : " . (is_array($got)  ? implode(' ', $got)  : var_export($got,  true)) . "\n";
}

$rc = new ReflectionClass('QrCode');

/* ---- 1. Reed-Solomon --------------------------------------------------- */
echo "Reed-Solomon\n";
$rs = $rc->getMethod('reedSolomon');

// "HELLO", sürüm 1-L: veri kod sözcükleri + 7 ECC
$data = [0x40,0x54,0x84,0x54,0xC4,0xC4,0xF0,0xEC,0x11,0xEC,0x11,0xEC,0x11,0xEC,0x11,0xEC,0x11,0xEC,0x11];
check('v1-L "HELLO" ECC',
    array_map(fn($b) => sprintf('%02X', $b), $rs->invoke(null, $data, 7)),
    ['4D','2A','D3','BB','9F','20','84']);

// Tek sıfır bayt, 10 ECC (v1-M blok boyu)
check('tek bayt, 10 ECC',
    count($rs->invoke(null, [0x00], 10)), 10);

/* ---- 2. Kod sözcüğü kurulumu ------------------------------------------- */
echo "Kod sözcükleri\n";
$ctor  = $rc->getConstructor();
$build = $rc->getMethod('buildCodewords');

$obj = $rc->newInstanceWithoutConstructor();
$ctor->invoke($obj, 1, QrCode::L);
check('v1-L "HELLO" tam dizi',
    array_map(fn($b) => sprintf('%02X', $b), $build->invoke($obj, array_values(unpack('C*', 'HELLO')))),
    ['40','54','84','54','C4','C4','F0','EC','11','EC','11','EC','11','EC','11','EC','11','EC','11',
     '4D','2A','D3','BB','9F','20','84']);

/* ---- 3. ECC blok tablosu tutarlılığı ------------------------------------ */
echo "Blok tablosu\n";
// Sürüm başına toplam kod sözcüğü sayısı (ISO/IEC 18004 Tablo 1)
$totals = [1=>26, 2=>44, 3=>70, 4=>100, 5=>134, 6=>172, 7=>196, 8=>242,
           9=>292, 10=>346, 11=>404, 12=>466, 13=>532, 14=>581, 15=>655];
$table = $rc->getConstant('RS');
$bad = [];
foreach ($totals as $v => $total) {
    foreach ([0, 1, 2, 3] as $lvl) {
        [$ecPer, $b1, $d1, $b2, $d2] = $table[$v][$lvl];
        $blocks = $b1 + $b2;
        $sum = $b1 * $d1 + $b2 * $d2 + $blocks * $ecPer;
        if ($sum !== $total) { $bad[] = "v$v/L$lvl ($sum≠$total)"; }
        if ($b2 && $d2 !== $d1 + 1) { $bad[] = "v$v/L$lvl grup2 boyu"; }
    }
}
check('veri+ECC toplamı her sürümde tutuyor', $bad, []);

/* ---- 4. Biçim ve sürüm bitleri ------------------------------------------ */
echo "Biçim / sürüm bitleri\n";
$fmt = $rc->getMethod('formatBits');
// ISO Ek C referans değerleri
check('L, maske 0', $fmt->invoke(null, QrCode::L, 0), 0x77C4);
check('L, maske 7', $fmt->invoke(null, QrCode::L, 7), 0x6976);
check('M, maske 0', $fmt->invoke(null, QrCode::M, 0), 0x5412);
check('M, maske 5', $fmt->invoke(null, QrCode::M, 5), 0x40CE);
check('Q, maske 7', $fmt->invoke(null, QrCode::Q, 7), 0x2BED);
check('H, maske 3', $fmt->invoke(null, QrCode::H, 3), 0x19D0);
check('H, maske 5', $fmt->invoke(null, QrCode::H, 5), 0x0255);

$ver = $rc->getMethod('versionBits');
check('sürüm 7',  $ver->invoke(null, 7),  0x07C94);
check('sürüm 10', $ver->invoke(null, 10), 0x0A4D3);
check('sürüm 15', $ver->invoke(null, 15), 0x0F928);

/* ---- 5. Matris bütünlüğü ------------------------------------------------ */
echo "Matris\n";
$m = QrCode::matrix('HELLO', QrCode::L);
check('sürüm 1 boyutu', count($m), 21);
check('konum deseni sol üst', $m[0][0] . $m[0][6] . $m[1][1] . $m[3][3], '1101');
check('koyu modül (13,8)', $m[13][8], 1);
check('yatay zamanlama', $m[6][8] . $m[6][9] . $m[6][10] . $m[6][11], '1010');
check('dikey zamanlama',  $m[8][6] . $m[9][6] . $m[10][6] . $m[11][6], '1010');

$m10 = QrCode::matrix(str_repeat('a', 200), QrCode::M);
check('200 bayt → sürüm 10 boyutu', count($m10), 57);

/* ---- 6. Sürüm seçimi ---------------------------------------------------- */
echo "Sürüm seçimi\n";
$pick = $rc->getMethod('pickVersion');
check('1 bayt → v1',    $pick->invoke(null, 1, QrCode::M), 1);
check('14 bayt → v1',   $pick->invoke(null, 14, QrCode::M), 1);
check('15 bayt → v2',   $pick->invoke(null, 15, QrCode::M), 2);
check('412 bayt → v15', $pick->invoke(null, 412, QrCode::M), 15);

$overflow = false;
try { $pick->invoke(null, 5000, QrCode::M); } catch (Throwable) { $overflow = true; }
check('kapasite aşımında istisna', $overflow, true);

/* ---- 7. Çıktı biçimleri ------------------------------------------------- */
echo "Çıktı\n";
$svg = QrCode::svg('https://example.com/qr/menu');
check('SVG kök etiketi', str_starts_with($svg, '<svg'), true);
check('SVG kapanışı', str_ends_with($svg, '</svg>'), true);
check('SVG yol içeriyor', str_contains($svg, '<path d="M'), true);

if (function_exists('imagecreatetruecolor')) {
    $png = QrCode::png('https://example.com/qr/menu', 4, 4);
    check('PNG imzası', substr($png, 1, 3), 'PNG');
} else {
    echo "  · PNG testi atlandı (GD yok)\n";
}

/* ---- Sonuç -------------------------------------------------------------- */
echo "\n$pass geçti, $fail başarısız.\n";
exit($fail ? 1 : 0);
