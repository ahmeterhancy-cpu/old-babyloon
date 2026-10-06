<?php
/**
 * Old Babyloon — tek giriş noktası.
 * Tüm istekler .htaccess (canlı) veya router-dev.php (yerel) üzerinden buraya gelir.
 */

declare(strict_types=1);

$config = require __DIR__ . '/app/config.php';
$GLOBALS['OB_CONFIG'] = $config;

require __DIR__ . '/app/db.php';
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/routes.php';

try {
    DB::boot($config);
} catch (Throwable $ex) {
    http_response_code(503);
    if ($config['debug']) { throw $ex; }
    exit('Veritabanına bağlanılamadı. Lütfen daha sonra tekrar deneyin.');
}

/* Şema henüz kurulmadıysa ne yapılacağını anlat.
   Eski mesaj "komut satırında php migrate.php çalıştırın" diyordu; oysa
   paylaşımlı barındırmada kabuk erişimi çoğu zaman yok ve .htaccess
   migrate.php'yi web'e zaten kapatıyor. Yani olmayan bir yola
   gönderiyordu. Doğru yol phpMyAdmin'den SQL dosyasını içe aktarmak. */
try {
    DB::value('SELECT 1 FROM settings LIMIT 1');
} catch (Throwable) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    exit('<!doctype html><html lang="tr"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>Kurulum tamamlanmadı</title><style>'
       . 'body{font:16px/1.65 system-ui,-apple-system,"Segoe UI",sans-serif;margin:0;'
       . 'padding:3rem 1.25rem;background:#FAF6F1;color:#2B2422}'
       . '.w{max-width:640px;margin:0 auto;background:#fff;padding:2rem;border-radius:14px;'
       . 'box-shadow:0 12px 34px -26px rgba(43,36,34,.6)}'
       . 'h1{font-size:1.25rem;margin:0 0 1rem}ol{padding-left:1.2rem}li{margin:.45rem 0}'
       . 'code{background:#F3ECE4;padding:.1rem .35rem;border-radius:4px;font-size:.92em}'
       . 'p.k{color:#857873;font-size:.88rem;margin-top:1.5rem}</style></head><body><div class="w">'
       . '<h1>Kurulum tamamlanmadı</h1>'
       . '<p>Veritabanı bağlantısı çalışıyor, ancak tablolar henüz yok. '
       . 'cPanel &rarr; <b>phpMyAdmin</b> ile içe aktarın:</p><ol>'
       . '<li>Soldan veritabanınızı seçin</li>'
       . '<li>Üstten <b>İçe Aktar</b> sekmesi</li>'
       . '<li><code>babyloon-veritabani.sql</code> dosyasını seçin</li>'
       . '<li>Karakter kümesi <code>utf8mb4</code> olsun</li>'
       . '<li><b>Git</b></li></ol>'
       . '<p class="k">Kurulum bitince bu sayfa yerine site açılır.</p>'
       . '</div></body></html>');
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
if (base() !== '' && str_starts_with($path, base())) {
    $path = substr($path, strlen(base()));
}
$path = trim(rawurldecode($path), '/');
$GLOBALS['OB_PATH'] = $path;

try {
    ob_route($path);
} catch (Throwable $ex) {
    if ($config['debug']) { throw $ex; }
    error_log('[babyloon] ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
    http_response_code(500);
    $GLOBALS['OB_LOCALE'] = cfg('default_locale');
    render('site/500', ['title' => 'Hata']);
}
