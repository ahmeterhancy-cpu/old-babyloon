<?php
declare(strict_types=1);

/**
 * Instagram akışı.
 *
 * Gönderiler Instagram'dan çekilir, GÖRSELLER YERELE İNDİRİLİR ve veritabanına
 * yazılır. Yerel kopya şart: Instagram'ın CDN adresleri birkaç saat içinde
 * geçersizleşir, doğrudan bağlanan siteler bir süre sonra kırık görsel gösterir.
 *
 * İki hesap türü desteklenir:
 *   - Instagram Login (kişisel/creator): jeton yeterli, /me/media kullanılır
 *   - Facebook'a bağlı Business hesabı: ayrıca IG kullanıcı kimliği girilir
 *
 * Jeton yoksa modül sessizce devre dışı kalır; elle eklenen gönderiler
 * gösterilmeye devam eder.
 */
final class Instagram
{
    /** Ön yüzde tembel yenileme aralığı (saniye) */
    private const REFRESH_EVERY = 3600;
    /** Hatadan sonra yeniden deneme aralığı — sunucuyu yormamak için */
    private const RETRY_AFTER   = 1800;
    private const TIMEOUT       = 8;
    private const API_VERSION   = 'v21.0';

    /* ---- Ön yüz -------------------------------------------------------- */

    /** Ana sayfada gösterilecek gönderiler. */
    public static function posts(int $limit = 8): array
    {
        return DB::all(
            'SELECT * FROM instagram_posts WHERE is_active = 1
              ORDER BY sort, posted_at DESC, id DESC LIMIT ' . max(1, $limit)
        );
    }

    public static function enabled(): bool
    {
        return setting('ig_enabled', '0') === '1' && setting('ig_token') !== '';
    }

    /** Sayfa gösterimi sırasında, süresi geldiyse sessizce yeniler. */
    public static function maybeRefresh(): void
    {
        if (!self::enabled()) { return; }

        $last  = (int) setting('ig_last_sync', '0');
        $error = (int) setting('ig_last_error_at', '0');
        $now   = time();

        if ($now - $last < self::REFRESH_EVERY) { return; }
        if ($error && $now - $error < self::RETRY_AFTER) { return; }

        // Aynı anda yalnızca bir isteğin yenilemesi için kilit
        $lock = OB_ROOT . '/storage/ig.lock';
        $fh = @fopen($lock, 'c');
        if (!$fh) { return; }
        if (!flock($fh, LOCK_EX | LOCK_NB)) { fclose($fh); return; }

        try { self::sync(); } catch (Throwable) { /* ön yüz asla kırılmaz */ }

        flock($fh, LOCK_UN);
        fclose($fh);
    }

    /* ---- Senkronizasyon ------------------------------------------------- */

    /**
     * Instagram'dan gönderileri çeker.
     * @return array{ok: bool, added: int, updated: int, message: string}
     */
    public static function sync(?int $limit = null): array
    {
        $token = setting('ig_token');
        if ($token === '') {
            return ['ok' => false, 'added' => 0, 'updated' => 0, 'message' => 'Erişim jetonu girilmemiş.'];
        }

        $limit  = $limit ?: max(1, min(50, (int) setting('ig_limit', '12')));
        $userId = trim(setting('ig_user_id'));
        $fields = 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp';

        $url = $userId !== ''
            ? sprintf('https://graph.facebook.com/%s/%s/media?fields=%s&limit=%d&access_token=%s',
                      self::API_VERSION, rawurlencode($userId), $fields, $limit, rawurlencode($token))
            : sprintf('https://graph.instagram.com/me/media?fields=%s&limit=%d&access_token=%s',
                      $fields, $limit, rawurlencode($token));

        [$body, $err] = self::http($url);
        if ($err !== null) { return self::fail($err); }

        $json = json_decode((string) $body, true);
        if (!is_array($json)) { return self::fail('Instagram beklenmedik bir yanıt döndürdü.'); }
        if (isset($json['error'])) {
            $m = $json['error']['message'] ?? 'Bilinmeyen API hatası';
            return self::fail('Instagram: ' . $m);
        }
        if (!isset($json['data']) || !is_array($json['data'])) {
            return self::fail('Yanıtta gönderi listesi yok.');
        }

        $added = 0; $updated = 0; $sort = 0;
        foreach ($json['data'] as $post) {
            $igId = (string) ($post['id'] ?? '');
            if ($igId === '') { continue; }

            $type   = (string) ($post['media_type'] ?? 'IMAGE');
            $srcUrl = $type === 'VIDEO'
                ? (string) ($post['thumbnail_url'] ?? $post['media_url'] ?? '')
                : (string) ($post['media_url'] ?? '');
            if ($srcUrl === '') { continue; }

            $existing = DB::one('SELECT * FROM instagram_posts WHERE ig_id = ?', [$igId]);

            // Görsel yalnızca ilk görüşte indirilir
            $image = $existing['image'] ?? null;
            if (!$image || !is_file(OB_ROOT . '/' . $image)) {
                $image = self::download($srcUrl, $igId);
                if ($image === null) { continue; }
            }

            $row = [
                'permalink'  => (string) ($post['permalink'] ?? ''),
                'media_type' => $type,
                'caption'    => mb_substr((string) ($post['caption'] ?? ''), 0, 1000),
                'image'      => $image,
                'posted_at'  => isset($post['timestamp'])
                    ? date('Y-m-d H:i:s', strtotime((string) $post['timestamp']))
                    : DB::now(),
                'sort'       => $sort++,
            ];

            if ($existing) {
                DB::update('instagram_posts', $row, 'id = :__id', ['__id' => $existing['id']]);
                $updated++;
            } else {
                DB::insert('instagram_posts', $row + [
                    'ig_id' => $igId, 'is_manual' => 0, 'is_active' => 1, 'created_at' => DB::now(),
                ]);
                $added++;
            }
        }

        setting_put('ig_last_sync', (string) time());
        setting_put('ig_last_error', '');
        setting_put('ig_last_error_at', '');

        return ['ok' => true, 'added' => $added, 'updated' => $updated,
                'message' => "$added yeni, $updated güncellenen gönderi."];
    }

    /** 60 günlük jetonu uzatır (yalnızca Instagram Login jetonlarında çalışır). */
    public static function refreshToken(): array
    {
        $token = setting('ig_token');
        if ($token === '') { return ['ok' => false, 'message' => 'Jeton yok.']; }

        $url = 'https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token='
             . rawurlencode($token);
        [$body, $err] = self::http($url);
        if ($err !== null) { return ['ok' => false, 'message' => $err]; }

        $json = json_decode((string) $body, true);
        if (!isset($json['access_token'])) {
            $m = $json['error']['message'] ?? 'Jeton yenilenemedi.';
            return ['ok' => false, 'message' => $m];
        }

        setting_put('ig_token', (string) $json['access_token']);
        $days = isset($json['expires_in']) ? (int) round(((int) $json['expires_in']) / 86400) : 60;
        setting_put('ig_token_expires', date('Y-m-d', time() + (int) ($json['expires_in'] ?? 5184000)));
        return ['ok' => true, 'message' => "Jeton yenilendi, $days gün daha geçerli."];
    }

    /* ---- Yardımcılar ---------------------------------------------------- */

    private static function fail(string $message): array
    {
        setting_put('ig_last_error', $message);
        setting_put('ig_last_error_at', (string) time());
        return ['ok' => false, 'added' => 0, 'updated' => 0, 'message' => $message];
    }

    /** @return array{0: ?string, 1: ?string} [gövde, hata] */
    private static function http(string $url): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_USERAGENT      => 'OldBabyloon/1.0',
            ]);
            // Bazı paylaşımlı sunucularda kök sertifika deposu tanımsızdır.
            // Kökte babyloon-cacert.pem varsa onu kullan (kurulum notlarına bakın).
            $ca = OB_ROOT . '/babyloon-cacert.pem';
            if (is_file($ca)) { curl_setopt($ch, CURLOPT_CAINFO, $ca); }

            $body = curl_exec($ch);
            $errNo = curl_errno($ch);
            $errMsg = curl_error($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errNo === 60) {
                return [null, 'Sunucu, Instagram sertifikasını doğrulayamadı (cURL 60). '
                            . 'Barındırıcınızdan curl.cainfo ayarını isteyin ya da kök dizine babyloon-cacert.pem dosyasını koyun.'];
            }
            if ($errNo !== 0) { return [null, 'Bağlantı hatası: ' . $errMsg]; }
            if ($code >= 400 && $body === false) { return [null, 'Instagram HTTP ' . $code . ' döndürdü.']; }
            return [(string) $body, null];
        }

        if (!ini_get('allow_url_fopen')) {
            return [null, 'Sunucuda cURL yok ve allow_url_fopen kapalı; dış bağlantı kurulamıyor.'];
        }
        $ctx = stream_context_create(['http' => ['timeout' => self::TIMEOUT, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        return $body === false ? [null, 'Instagram\'a bağlanılamadı.'] : [$body, null];
    }

    /** Gönderi görselini indirir, küçültür ve göreli yolunu döndürür. */
    private static function download(string $url, string $igId): ?string
    {
        [$body, $err] = self::http($url);
        if ($err !== null || $body === null || strlen($body) < 512) { return null; }

        $dir = OB_ROOT . '/uploads/instagram';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) { return null; }

        $name = 'ig-' . preg_replace('~[^0-9A-Za-z]~', '', $igId) . '.jpg';
        $path = $dir . '/' . $name;
        if (@file_put_contents($path, $body) === false) { return null; }

        // Gerçekten görsel mi?
        $info = @getimagesize($path);
        if (!$info) { @unlink($path); return null; }

        if (function_exists('imagecreatetruecolor')) {
            img_shrink($path, $info[2], 1080);
        }
        return 'uploads/instagram/' . $name;
    }

    /** Alt yazıyı vitrinde göstermek için kısaltır. */
    public static function shortCaption(?string $caption, int $len = 90): string
    {
        $c = trim((string) $caption);
        if ($c === '') { return ''; }
        $c = preg_replace('~\s+~u', ' ', $c) ?? '';
        return mb_strlen($c) > $len ? mb_substr($c, 0, $len - 1) . '…' : $c;
    }
}
