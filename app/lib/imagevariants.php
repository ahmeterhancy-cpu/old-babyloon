<?php
declare(strict_types=1);

/* ==========================================================================
 *  Duyarlı görsel türevleri
 *
 *  Bir görselin daha küçük genişliklerini ve WebP kopyalarını üretir:
 *      hero-1.jpg → hero-1@700.jpg   hero-1@700.webp
 *                   hero-1@1100.jpg  hero-1@1100.webp
 *                   hero-1.webp
 *
 *  img_responsive() bunları bulup <picture> kurar; yoksa sade <img>'e
 *  düşer. Yani türev üretimi başarısız olsa bile site çalışır — bu
 *  yüzden buradaki her şey "olursa iyi" mantığında, hata fırlatmaz.
 *
 *  Hem panelden yükleme sırasında hem tools/responsive-images.php
 *  toplu çalıştırmasında aynı kod kullanılır.
 * ======================================================================= */

/** Türev üretilecek genişlikler (kaynaktan büyük olanlar atlanır). */
/* 900 ara basamağı önemli: 375 piksellik telefon 2x ekranda 750 piksel
   ister; yalnız 700 ve 1100 varken 1100 seçiliyor ve gereğinden çok
   büyük dosya iniyordu. */
const IMG_VARIANT_WIDTHS = [240, 700, 900, 1100, 1600];

/** Bu boyuttan büyük kaynaklarda bellek riskine girme (piksel sayısı). */
const IMG_VARIANT_MAX_PIXELS = 40_000_000;

/**
 * Verilen görselin türevlerini üretir.
 *
 * @param  string $abs Mutlak dosya yolu (uploads/ altında)
 * @return int Üretilen dosya sayısı
 */
function image_variants_make(string $abs, ?array $widths = null): int
{
    if (!is_file($abs) || !function_exists('imagecreatefromjpeg')) { return 0; }

    $bilgi = @getimagesize($abs);
    if (!$bilgi) { return 0; }
    [$w, $h, $tur] = $bilgi;

    // Zaten türev olan dosyayı yeniden işleme
    if (str_contains(basename($abs), '@')) { return 0; }
    if ($w * $h > IMG_VARIANT_MAX_PIXELS) { return 0; }

    $im = match ($tur) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($abs),
        IMAGETYPE_PNG  => @imagecreatefrompng($abs),
        IMAGETYPE_WEBP => @imagecreatefromwebp($abs),
        default        => null,
    };
    if (!$im) { return 0; }

    $taban = preg_replace('~\.(jpe?g|png|webp)$~i', '', $abs);
    $webpVar = function_exists('imagewebp');
    $n = 0;

    foreach ($widths ?? IMG_VARIANT_WIDTHS as $hedef) {
        if ($hedef >= $w) { continue; }
        $nh = (int) round($h * $hedef / $w);
        $t = imagecreatetruecolor($hedef, $nh);
        // PNG saydamlığı korunsun
        imagealphablending($t, false);
        imagesavealpha($t, true);
        imagecopyresampled($t, $im, 0, 0, 0, 0, $hedef, $nh, $w, $h);

        if (@imagejpeg($t, "$taban@$hedef.jpg", 82)) { $n++; }
        if ($webpVar && @imagewebp($t, "$taban@$hedef.webp", 78)) { $n++; }
        unset($t);
    }

    // Tam boyun WebP karşılığı
    if ($webpVar && @imagewebp($im, "$taban.webp", 78)) { $n++; }

    unset($im);
    return $n;
}

/**
 * Bir görselin türevlerini siler.
 *
 * Görsel silindiğinde çağrılmazsa türevler öksüz kalır ve klasör
 * zamanla şişer.
 *
 * @return int Silinen dosya sayısı
 */
function image_variants_remove(string $abs): int
{
    $taban = preg_replace('~\.(jpe?g|png|webp)$~i', '', $abs);
    $n = 0;
    foreach (array_merge(
        glob("$taban@*.jpg") ?: [],
        glob("$taban@*.webp") ?: [],
        glob("$taban.webp") ?: []
    ) as $f) {
        if (@unlink($f)) { $n++; }
    }
    return $n;
}
