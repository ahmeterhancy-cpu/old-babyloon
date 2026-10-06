<?php
declare(strict_types=1);

/* ==========================================================================
 *  PDF menüden sayfa görselleri üretme
 *
 *  Kitapçık sayfa GÖRSELLERİYLE çalışır; PDF'in kendisi yalnızca indirme
 *  bağlantısı içindir. Bu dosya ikisini birbirine bağlar: yeni bir PDF
 *  yüklendiğinde sayfaları ondan üretmeye çalışır.
 *
 *  Dönüştürme paylaşımlı barındırmada ÇALIŞMAYABİLİR — Poppler ya da
 *  Ghostscript kurulu değilse hiçbir yol yoktur. Bu yüzden her şey
 *  "olursa iyi" mantığıyla yazılmıştır: dönüştürme yoksa PDF yine
 *  yüklenir ve sayfalar elle yüklenir. Sessizce başarısız olmak yerine
 *  panelde ne bulunduğu açıkça yazılır.
 * ======================================================================= */

final class PdfPages
{
    /** PDF gerçekten PDF mi? Uzantıya değil, dosyanın kendisine bakar. */
    public static function isPdf(string $path): bool
    {
        $fh = @fopen($path, 'rb');
        if (!$fh) { return false; }
        $head = (string) fread($fh, 5);
        fclose($fh);
        return $head === '%PDF-';
    }

    /** Kabuk komutu çalıştırılabiliyor mu? */
    public static function canExec(): bool
    {
        if (!function_exists('exec')) { return false; }
        $off = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        return !in_array('exec', $off, true);
    }

    /**
     * Kullanılabilir dönüştürücüyü bulur.
     *
     * @return array{kind:string,bin:string,label:string}|null
     */
    public static function converter(): ?array
    {
        // 1. Imagick — kabuk gerektirmez ama PDF için Ghostscript'e dayanır
        if (extension_loaded('imagick')) {
            try {
                if (Imagick::queryFormats('PDF')) {
                    return ['kind' => 'imagick', 'bin' => '', 'label' => 'Imagick (PHP eklentisi)'];
                }
            } catch (Throwable) { /* sorun değil, alttakileri dene */ }
        }

        if (!self::canExec()) { return null; }

        // 2. Poppler — en iyi sonucu verir
        foreach (['pdftoppm', 'pdftocairo'] as $bin) {
            if (self::binExists($bin)) {
                return ['kind' => 'poppler', 'bin' => $bin, 'label' => "Poppler ($bin)"];
            }
        }

        // 3. Ghostscript
        foreach (['gs', 'gswin64c', 'gswin32c'] as $bin) {
            if (self::binExists($bin)) {
                return ['kind' => 'gs', 'bin' => $bin, 'label' => "Ghostscript ($bin)"];
            }
        }

        return null;
    }

    private static function binExists(string $bin): bool
    {
        $out = []; $code = 1;
        // Poppler araçları -v ile 99 döndürür; varlığı gösteren şey çıktı üretmesidir
        @exec(escapeshellarg($bin) . ' -v 2>&1', $out, $code);
        return $out !== [] || $code === 0 || $code === 99;
    }

    /**
     * PDF'i sayfa görsellerine çevirir.
     *
     * Görseller GEÇİCİ bir klasöre yazılır; çağıran taraf işi bitene kadar
     * eski sayfalara dokunmaz. Yarım kalan bir dönüştürme mevcut kitapçığı
     * bozmamalıdır.
     *
     * Çözünürlük 300 DPI: menü kartları çoğu zaman A4'ten küçük basılır ve
     * 150 DPI'da ekranda yumuşak görünürler. Uzun kenar sonradan zaten
     * 1600 piksele indiriliyor, bu yüzden büyük sayfalarda israf olmuyor.
     *
     * @param  int $max En fazla kaç sayfa (kaza sonucu 300 sayfalık PDF'e karşı)
     * @return array{ok:bool,files:string[],error:?string,used:?string}
     */
    public static function render(string $pdf, string $outDir, int $max = 40): array
    {
        $conv = self::converter();
        if (!$conv) {
            return ['ok' => false, 'files' => [], 'used' => null,
                    'error' => 'Sunucuda PDF dönüştürücü yok (Poppler, Ghostscript veya Imagick).'];
        }
        if (!is_dir($outDir) && !@mkdir($outDir, 0775, true)) {
            return ['ok' => false, 'files' => [], 'used' => $conv['label'],
                    'error' => 'Geçici klasör oluşturulamadı.'];
        }

        $prefix = $outDir . '/sayfa';
        $err = null;

        if ($conv['kind'] === 'imagick') {
            $err = self::runImagick($pdf, $prefix, $max);
        } else {
            $cmd = $conv['kind'] === 'poppler'
                ? sprintf('%s -jpeg -r 300 -f 1 -l %d %s %s 2>&1',
                    escapeshellarg($conv['bin']), $max, escapeshellarg($pdf), escapeshellarg($prefix))
                : sprintf('%s -dNOPAUSE -dBATCH -dSAFER -sDEVICE=jpeg -r300 -dJPEGQ=90 -dLastPage=%d -sOutputFile=%s %s 2>&1',
                    escapeshellarg($conv['bin']), $max, escapeshellarg($prefix . '-%d.jpg'), escapeshellarg($pdf));

            $out = []; $code = 0;
            @exec($cmd, $out, $code);
            if ($code !== 0) { $err = 'Dönüştürücü hata verdi: ' . trim(implode(' ', array_slice($out, -3))); }
        }

        // Üretilen dosyalar — adlarındaki sayıya göre sıralanır ("sayfa-10" < "sayfa-9" olmasın)
        $files = glob($prefix . '*.{jpg,jpeg,png}', GLOB_BRACE) ?: [];
        usort($files, fn($a, $b) => self::pageNo($a) <=> self::pageNo($b));

        if (!$files) {
            return ['ok' => false, 'files' => [], 'used' => $conv['label'],
                    'error' => $err ?: 'Dönüştürücü hiç sayfa üretmedi.'];
        }

        return ['ok' => true, 'files' => $files, 'used' => $conv['label'], 'error' => null];
    }

    private static function runImagick(string $pdf, string $prefix, int $max): ?string
    {
        try {
            $im = new Imagick();
            $im->setResolution(300, 300);
            $im->readImage($pdf);
            $im = $im->coalesceImages();
            $n = 0;
            foreach ($im as $page) {
                if (++$n > $max) { break; }
                $page->setImageFormat('jpeg');
                $page->setImageCompressionQuality(82);
                $page->setImageBackgroundColor('white');
                $page = $page->flattenImages();
                $page->writeImage($prefix . '-' . $n . '.jpg');
            }
            $im->clear();
            return null;
        } catch (Throwable $e) {
            return 'Imagick: ' . $e->getMessage();
        }
    }

    /** "…/sayfa-12.jpg" → 12 */
    private static function pageNo(string $file): int
    {
        return preg_match('~-(\d+)\.[a-z]+$~i', $file, $m) ? (int) $m[1] : 0;
    }

    /** Geçici klasörü ve içindekileri siler. */
    public static function cleanup(string $dir): void
    {
        if (!is_dir($dir)) { return; }
        foreach (glob($dir . '/*') ?: [] as $f) { @unlink($f); }
        @rmdir($dir);
    }
}
