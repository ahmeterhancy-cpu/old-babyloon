<?php
declare(strict_types=1);

/**
 * Old Babyloon — bağımlılıksız QR kodu üreteci.
 *
 * ISO/IEC 18004 Model 2, byte (8-bit) modu, sürüm 1–15.
 * Menü bağlantıları için fazlasıyla yeterli (15-M ≈ 412 bayt).
 *
 *   echo QrCode::svg('https://example.com/tr/menu');
 *   file_put_contents('q.png', QrCode::png('…', 8, 4));
 */
final class QrCode
{
    public const L = 0, M = 1, Q = 2, H = 3;

    /** [ecPerBlock, blocksG1, dataPerBlockG1, blocksG2, dataPerBlockG2] — sürüm 1..15 × L,M,Q,H */
    private const RS = [
        1  => [[7,1,19,0,0], [10,1,16,0,0], [13,1,13,0,0], [17,1,9,0,0]],
        2  => [[10,1,34,0,0], [16,1,28,0,0], [22,1,22,0,0], [28,1,16,0,0]],
        3  => [[15,1,55,0,0], [26,1,44,0,0], [18,2,17,0,0], [22,2,13,0,0]],
        4  => [[20,1,80,0,0], [18,2,32,0,0], [26,2,24,0,0], [16,4,9,0,0]],
        5  => [[26,1,108,0,0], [24,2,43,0,0], [18,2,15,2,16], [22,2,11,2,12]],
        6  => [[18,2,68,0,0], [16,4,27,0,0], [24,4,19,0,0], [28,4,15,0,0]],
        7  => [[20,2,78,0,0], [18,4,31,0,0], [18,2,14,4,15], [26,4,13,1,14]],
        8  => [[24,2,97,0,0], [22,2,38,2,39], [22,4,18,2,19], [26,4,14,2,15]],
        9  => [[30,2,116,0,0], [22,3,36,2,37], [20,4,16,4,17], [24,4,12,4,13]],
        10 => [[18,2,68,2,69], [26,4,43,1,44], [24,6,19,2,20], [28,6,15,2,16]],
        11 => [[20,4,81,0,0], [30,1,50,4,51], [28,4,22,4,23], [24,3,12,8,13]],
        12 => [[24,2,92,2,93], [22,6,36,2,37], [26,4,20,6,21], [28,7,14,4,15]],
        13 => [[26,4,107,0,0], [22,8,37,1,38], [24,8,20,4,21], [22,12,11,4,12]],
        14 => [[30,3,115,1,116], [24,4,40,5,41], [20,11,16,5,17], [24,11,12,5,13]],
        15 => [[22,5,87,1,88], [24,5,41,5,42], [30,5,24,7,25], [24,11,12,7,13]],
    ];

    /** Hizalama deseni merkez koordinatları, sürüm 1..15 */
    private const ALIGN = [
        1 => [], 2 => [6,18], 3 => [6,22], 4 => [6,26], 5 => [6,30], 6 => [6,34],
        7 => [6,22,38], 8 => [6,24,42], 9 => [6,26,46], 10 => [6,28,50],
        11 => [6,30,54], 12 => [6,32,58], 13 => [6,34,62], 14 => [6,26,46,66],
        15 => [6,26,48,70],
    ];

    /** ECC seviyesinin format bilgisindeki 2 bitlik karşılığı */
    private const EC_BITS = [self::L => 0b01, self::M => 0b00, self::Q => 0b11, self::H => 0b10];

    private int $size;
    /** @var array<int, array<int, int>> 0/1 modüller */
    private array $m = [];
    /** @var array<int, array<int, bool>> işlev deseni maskesi */
    private array $reserved = [];

    private function __construct(private int $version, private int $ecLevel)
    {
        $this->size = 17 + 4 * $version;
        for ($r = 0; $r < $this->size; $r++) {
            $this->m[$r] = array_fill(0, $this->size, 0);
            $this->reserved[$r] = array_fill(0, $this->size, false);
        }
    }

    /* ====================================================================
     *  Genel API
     * ================================================================= */

    /** @return array<int, array<int,int>> 0/1 matrisi */
    public static function matrix(string $text, int $ecLevel = self::M): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $version = self::pickVersion(count($bytes), $ecLevel);

        $qr = new self($version, $ecLevel);
        $codewords = $qr->buildCodewords($bytes);
        $qr->drawFunctionPatterns();
        $qr->placeData($codewords);
        $mask = $qr->applyBestMask();
        $qr->drawFormatInfo($mask);
        return $qr->m;
    }

    /** Ölçeklenebilir SVG. $scale = modül başına birim, $quiet = sessiz alan modülü. */
    public static function svg(string $text, int $scale = 8, int $quiet = 4, int $ecLevel = self::M,
                               string $dark = '#1D1313', string $light = '#FFFFFF'): string
    {
        $m = self::matrix($text, $ecLevel);
        $n = count($m);
        $dim = ($n + 2 * $quiet) * $scale;

        // Her satırı tek bir path parçasına indirger — dosya küçük kalır.
        $path = '';
        foreach ($m as $r => $row) {
            $c = 0;
            while ($c < $n) {
                if ($row[$c] === 1) {
                    $run = 1;
                    while ($c + $run < $n && $row[$c + $run] === 1) { $run++; }
                    $x = ($c + $quiet) * $scale;
                    $y = ($r + $quiet) * $scale;
                    $path .= sprintf('M%d %dh%dv%dh-%dz', $x, $y, $run * $scale, $scale, $run * $scale);
                    $c += $run;
                } else {
                    $c++;
                }
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 %1$d %1$d" shape-rendering="crispEdges" role="img" aria-label="QR">'
            . '<rect width="%1$d" height="%1$d" fill="%2$s"/><path d="%3$s" fill="%4$s"/></svg>',
            $dim, $light, $path, $dark
        );
    }

    /** GD ile PNG. GD yoksa RuntimeException atar. */
    public static function png(string $text, int $scale = 8, int $quiet = 4, int $ecLevel = self::M): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('PNG için GD eklentisi gerekiyor; SVG kullanın.');
        }
        $m = self::matrix($text, $ecLevel);
        $n = count($m);
        $dim = ($n + 2 * $quiet) * $scale;

        $im = imagecreatetruecolor($dim, $dim);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 29, 19, 19);
        imagefilledrectangle($im, 0, 0, $dim, $dim, $white);
        foreach ($m as $r => $row) {
            foreach ($row as $c => $v) {
                if ($v === 1) {
                    $x = ($c + $quiet) * $scale;
                    $y = ($r + $quiet) * $scale;
                    imagefilledrectangle($im, $x, $y, $x + $scale - 1, $y + $scale - 1, $black);
                }
            }
        }
        ob_start();
        imagepng($im);
        return (string) ob_get_clean();
    }

    /* ====================================================================
     *  Kodlama
     * ================================================================= */

    private static function pickVersion(int $byteLen, int $ecLevel): int
    {
        foreach (array_keys(self::RS) as $v) {
            $countBits = $v < 10 ? 8 : 16;
            $capacityBits = self::dataCodewords($v, $ecLevel) * 8;
            if (4 + $countBits + $byteLen * 8 <= $capacityBits) { return $v; }
        }
        throw new RuntimeException('Veri QR sürüm 15 için fazla uzun (' . $byteLen . ' bayt).');
    }

    private static function dataCodewords(int $version, int $ecLevel): int
    {
        [, $b1, $d1, $b2, $d2] = self::RS[$version][$ecLevel];
        return $b1 * $d1 + $b2 * $d2;
    }

    /** Veri baytları → serpiştirilmiş nihai kod sözcükleri (veri + ECC). */
    private function buildCodewords(array $bytes): array
    {
        [$ecPerBlock, $b1, $d1, $b2, $d2] = self::RS[$this->version][$this->ecLevel];
        $totalData = $b1 * $d1 + $b2 * $d2;

        /* --- bit dizisi --- */
        $bits = [];
        $push = function (int $value, int $len) use (&$bits): void {
            for ($i = $len - 1; $i >= 0; $i--) { $bits[] = ($value >> $i) & 1; }
        };
        $push(0b0100, 4);                                   // byte modu
        $push(count($bytes), $this->version < 10 ? 8 : 16); // karakter sayısı
        foreach ($bytes as $b) { $push($b, 8); }

        // Sonlandırıcı (en fazla 4 bit) ve bayta hizalama
        $capacityBits = $totalData * 8;
        for ($i = 0; $i < 4 && count($bits) < $capacityBits; $i++) { $bits[] = 0; }
        while (count($bits) % 8 !== 0) { $bits[] = 0; }

        /* --- bitler → kod sözcükleri, dolgu --- */
        $data = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $byte = 0;
            for ($j = 0; $j < 8; $j++) { $byte = ($byte << 1) | $bits[$i + $j]; }
            $data[] = $byte;
        }
        $pad = [0xEC, 0x11];
        $p = 0;
        while (count($data) < $totalData) { $data[] = $pad[$p++ % 2]; }

        /* --- bloklara böl, her bloğun ECC'sini hesapla --- */
        $blocks = [];
        $ecBlocks = [];
        $offset = 0;
        foreach ([[$b1, $d1], [$b2, $d2]] as [$count, $size]) {
            for ($i = 0; $i < $count; $i++) {
                $block = array_slice($data, $offset, $size);
                $offset += $size;
                $blocks[] = $block;
                $ecBlocks[] = self::reedSolomon($block, $ecPerBlock);
            }
        }

        /* --- serpiştir --- */
        $out = [];
        $maxData = max(array_map('count', $blocks));
        for ($i = 0; $i < $maxData; $i++) {
            foreach ($blocks as $b) { if (isset($b[$i])) { $out[] = $b[$i]; } }
        }
        for ($i = 0; $i < $ecPerBlock; $i++) {
            foreach ($ecBlocks as $b) { $out[] = $b[$i]; }
        }
        return $out;
    }

    /* ---- GF(256) aritmetiği, üretici polinomu 0x11D --------------------- */

    private static array $exp = [];
    private static array $log = [];

    private static function initGf(): void
    {
        if (self::$exp) { return; }
        $x = 1;
        for ($i = 0; $i < 256; $i++) {
            self::$exp[$i] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) { $x ^= 0x11D; }
        }
        for ($i = 256; $i < 512; $i++) { self::$exp[$i] = self::$exp[$i - 255]; }
    }

    private static function gfMul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) { return 0; }
        return self::$exp[self::$log[$a] + self::$log[$b]];
    }

    /** @return int[] $ecLen adet hata düzeltme kod sözcüğü */
    private static function reedSolomon(array $data, int $ecLen): array
    {
        self::initGf();

        // Üretici polinom: (x - a^0)(x - a^1)…(x - a^(ecLen-1))
        $gen = [1];
        for ($i = 0; $i < $ecLen; $i++) {
            $next = array_fill(0, count($gen) + 1, 0);
            foreach ($gen as $j => $coef) {
                // gen(x) * (x + a^i): x ile çarpım indeksi korur, a^i ile çarpım bir sağa kaydırır
                $next[$j]     ^= $coef;
                $next[$j + 1] ^= self::gfMul($coef, self::$exp[$i]);
            }
            $gen = $next;
        }

        $rem = array_merge($data, array_fill(0, $ecLen, 0));
        for ($i = 0; $i < count($data); $i++) {
            $factor = $rem[$i];
            if ($factor === 0) { continue; }
            foreach ($gen as $j => $coef) {
                $rem[$i + $j] ^= self::gfMul($coef, $factor);
            }
        }
        return array_slice($rem, count($data), $ecLen);
    }

    /* ====================================================================
     *  Matris
     * ================================================================= */

    private function set(int $r, int $c, int $v, bool $reserve = true): void
    {
        $this->m[$r][$c] = $v;
        if ($reserve) { $this->reserved[$r][$c] = true; }
    }

    private function drawFunctionPatterns(): void
    {
        $n = $this->size;

        // Konum belirleme desenleri + ayırıcılar
        foreach ([[0, 0], [0, $n - 7], [$n - 7, 0]] as [$r0, $c0]) {
            for ($r = -1; $r <= 7; $r++) {
                for ($c = -1; $c <= 7; $c++) {
                    $rr = $r0 + $r; $cc = $c0 + $c;
                    if ($rr < 0 || $rr >= $n || $cc < 0 || $cc >= $n) { continue; }
                    $inRing  = ($r >= 0 && $r <= 6 && ($c === 0 || $c === 6))
                            || ($c >= 0 && $c <= 6 && ($r === 0 || $r === 6));
                    $inCore  = $r >= 2 && $r <= 4 && $c >= 2 && $c <= 4;
                    $this->set($rr, $cc, ($inRing || $inCore) ? 1 : 0);
                }
            }
        }

        // Zamanlama desenleri
        for ($i = 8; $i < $n - 8; $i++) {
            $this->set(6, $i, $i % 2 === 0 ? 1 : 0);
            $this->set($i, 6, $i % 2 === 0 ? 1 : 0);
        }

        // Hizalama desenleri
        $centers = self::ALIGN[$this->version];
        foreach ($centers as $cr) {
            foreach ($centers as $cc) {
                // Konum belirleme desenleriyle çakışanlar atlanır
                if (($cr <= 8 && $cc <= 8) || ($cr <= 8 && $cc >= $n - 9) || ($cr >= $n - 9 && $cc <= 8)) {
                    continue;
                }
                for ($r = -2; $r <= 2; $r++) {
                    for ($c = -2; $c <= 2; $c++) {
                        $on = (max(abs($r), abs($c)) !== 1) ? 1 : 0;
                        $this->set($cr + $r, $cc + $c, $on);
                    }
                }
            }
        }

        // Koyu modül
        $this->set($n - 8, 8, 1);

        // Biçim bilgisi alanları — şimdilik yalnızca rezerve edilir
        for ($i = 0; $i <= 8; $i++) {
            if ($i !== 6) { $this->set(8, $i, 0); $this->set($i, 8, 0); }
        }
        for ($i = 0; $i < 8; $i++) {
            $this->set(8, $n - 1 - $i, 0);
            $this->set($n - 1 - $i, 8, 0);
        }

        // Sürüm bilgisi (7 ve üzeri)
        if ($this->version >= 7) {
            $bits = self::versionBits($this->version);
            for ($i = 0; $i < 18; $i++) {
                $bit = ($bits >> $i) & 1;
                $r = intdiv($i, 3);
                $c = $i % 3;
                $this->set($r, $n - 11 + $c, $bit);
                $this->set($n - 11 + $c, $r, $bit);
            }
        }
    }

    /** BCH(18,6), üretici 0x1F25 */
    private static function versionBits(int $version): int
    {
        $d = $version << 12;
        $rem = $d;
        for ($i = 0; $i < 6; $i++) {
            if ($rem & (1 << (17 - $i))) { $rem ^= 0x1F25 << (5 - $i); }
        }
        return $d | ($rem & 0xFFF);
    }

    /** BCH(15,5), üretici 0x537, maske 0x5412 */
    private static function formatBits(int $ecLevel, int $mask): int
    {
        $data = (self::EC_BITS[$ecLevel] << 3) | $mask;
        $rem = $data << 10;
        for ($i = 0; $i < 5; $i++) {
            if ($rem & (1 << (14 - $i))) { $rem ^= 0x537 << (4 - $i); }
        }
        return (($data << 10) | ($rem & 0x3FF)) ^ 0x5412;
    }

    private function drawFormatInfo(int $mask): void
    {
        $n = $this->size;
        $bits = self::formatBits($this->ecLevel, $mask);

        // Sol üst: bit 0..14 — col 8 aşağı, sonra row 8 sola doğru
        for ($i = 0; $i <= 5; $i++)  { $this->m[$i][8] = ($bits >> $i) & 1; }
        $this->m[7][8] = ($bits >> 6) & 1;
        $this->m[8][8] = ($bits >> 7) & 1;
        $this->m[8][7] = ($bits >> 8) & 1;
        for ($i = 9; $i <= 14; $i++) { $this->m[8][14 - $i] = ($bits >> $i) & 1; }

        // Sağ üst + sol alt kopyası
        for ($i = 0; $i <= 7; $i++)  { $this->m[8][$n - 1 - $i] = ($bits >> $i) & 1; }
        for ($i = 8; $i <= 14; $i++) { $this->m[$n - 15 + $i][8] = ($bits >> $i) & 1; }

        $this->m[$n - 8][8] = 1; // koyu modül
    }

    /** Kod sözcüklerini zigzag düzeninde yerleştirir. */
    private function placeData(array $codewords): void
    {
        $n = $this->size;
        $bitIndex = 0;
        $total = count($codewords) * 8;

        for ($right = $n - 1; $right >= 1; $right -= 2) {
            if ($right === 6) { $right = 5; } // dikey zamanlama sütunu atlanır
            for ($v = 0; $v < $n; $v++) {
                for ($j = 0; $j < 2; $j++) {
                    $c = $right - $j;
                    $upward = ((($right + 1) & 2) === 0);
                    $r = $upward ? ($n - 1 - $v) : $v;
                    if ($this->reserved[$r][$c]) { continue; }
                    $bit = 0;
                    if ($bitIndex < $total) {
                        $bit = ($codewords[$bitIndex >> 3] >> (7 - ($bitIndex & 7))) & 1;
                    }
                    $this->m[$r][$c] = $bit;
                    $bitIndex++;
                }
            }
        }
    }

    private static function maskBit(int $mask, int $r, int $c): bool
    {
        return match ($mask) {
            0 => ($r + $c) % 2 === 0,
            1 => $r % 2 === 0,
            2 => $c % 3 === 0,
            3 => ($r + $c) % 3 === 0,
            4 => (intdiv($r, 2) + intdiv($c, 3)) % 2 === 0,
            5 => (($r * $c) % 2) + (($r * $c) % 3) === 0,
            6 => (((($r * $c) % 2) + (($r * $c) % 3)) % 2) === 0,
            7 => (((($r + $c) % 2) + (($r * $c) % 3)) % 2) === 0,
        };
    }

    /** Sekiz maskeyi dener, ceza puanı en düşük olanı uygular ve numarasını döndürür. */
    /** Yalnızca test için: null değilse maske seçimi atlanır. */
    public static ?int $debugMask = null;

    private function applyBestMask(): int
    {
        if (self::$debugMask !== null) { $this->xorMask(self::$debugMask); return self::$debugMask; }
        $best = 0;
        $bestScore = PHP_INT_MAX;
        $original = $this->m;

        for ($mask = 0; $mask < 8; $mask++) {
            $this->m = $original;
            $this->xorMask($mask);
            $this->drawFormatInfo($mask); // ceza puanı biçim bitlerini de sayar
            $score = $this->penalty();
            if ($score < $bestScore) { $bestScore = $score; $best = $mask; }
        }

        $this->m = $original;
        $this->xorMask($best);
        return $best;
    }

    private function xorMask(int $mask): void
    {
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                if (!$this->reserved[$r][$c] && self::maskBit($mask, $r, $c)) {
                    $this->m[$r][$c] ^= 1;
                }
            }
        }
    }

    private function penalty(): int
    {
        $n = $this->size;
        $score = 0;

        // N1 — beş ve üzeri ardışık aynı renk
        for ($r = 0; $r < $n; $r++) {
            $runV = 1; $runH = 1;
            for ($c = 1; $c < $n; $c++) {
                $runH = $this->m[$r][$c] === $this->m[$r][$c - 1] ? $runH + 1 : 1;
                if ($runH === 5) { $score += 3; } elseif ($runH > 5) { $score += 1; }
                $runV = $this->m[$c][$r] === $this->m[$c - 1][$r] ? $runV + 1 : 1;
                if ($runV === 5) { $score += 3; } elseif ($runV > 5) { $score += 1; }
            }
        }

        // N2 — 2×2 aynı renk bloklar
        for ($r = 0; $r < $n - 1; $r++) {
            for ($c = 0; $c < $n - 1; $c++) {
                $v = $this->m[$r][$c];
                if ($v === $this->m[$r][$c + 1] && $v === $this->m[$r + 1][$c] && $v === $this->m[$r + 1][$c + 1]) {
                    $score += 3;
                }
            }
        }

        // N3 — 1:1:3:1:1 deseni (konum belirleme deseni taklidi)
        $p1 = [1,0,1,1,1,0,1,0,0,0,0];
        $p2 = [0,0,0,0,1,0,1,1,1,0,1];
        for ($r = 0; $r < $n; $r++) {
            for ($c = 0; $c <= $n - 11; $c++) {
                $hit1 = true; $hit2 = true; $vit1 = true; $vit2 = true;
                for ($k = 0; $k < 11; $k++) {
                    $h = $this->m[$r][$c + $k];
                    $v = $this->m[$c + $k][$r];
                    if ($h !== $p1[$k]) { $hit1 = false; }
                    if ($h !== $p2[$k]) { $hit2 = false; }
                    if ($v !== $p1[$k]) { $vit1 = false; }
                    if ($v !== $p2[$k]) { $vit2 = false; }
                }
                $score += 40 * ((int) $hit1 + (int) $hit2 + (int) $vit1 + (int) $vit2);
            }
        }

        // N4 — koyu modül oranının %50'den sapması
        $dark = 0;
        foreach ($this->m as $row) { $dark += array_sum($row); }
        $ratio = ($dark * 100) / ($n * $n);
        $score += 10 * (int) floor(abs($ratio - 50) / 5);

        return $score;
    }
}
