<?php
/**
 * Old Babyloon — yapılandırma
 *
 * Sunucu (Turhost) ile yerel geliştirme arasındaki farkı OTOMATİK algılar:
 *   - babyloon-config.php varsa (sunucuya elle yüklenen dosya) onun değerleri kullanılır → MySQL
 *   - yoksa yerel geliştirme kabul edilir → storage/database.sqlite
 * Böylece depoya hiçbir zaman canlı veritabanı şifresi girmez.
 */

declare(strict_types=1);

// Eksik eklenti yedekleri — mbstring kapalı bir sunucuda aşağıdaki
// mb_internal_encoding() çağrısı siteyi ölümcül hatayla düşürüyordu.
require __DIR__ . '/compat.php';

date_default_timezone_set('Asia/Famagusta'); // KKTC — Europe/Istanbul veya Nicosia KULLANMA
mb_internal_encoding('UTF-8');

define('OB_ROOT', dirname(__DIR__));
define('OB_APP', __DIR__);

$config = [
    'app_name'   => 'Old Babyloon',
    'env'        => 'local',
    'debug'      => true,
    'base_url'   => '',            // alt klasörde çalışırsa örn. '/babyloon'
    'db'         => ['driver' => 'sqlite', 'path' => OB_ROOT . '/storage/database.sqlite'],
    'locales'    => ['tr' => 'Türkçe', 'en' => 'English'],
    'default_locale' => 'tr',
    'upload_max' => 6 * 1024 * 1024,  // 6 MB — görseller
    'pdf_max'    => 25 * 1024 * 1024, // 25 MB — menü PDF'i (sunucunun kendi sınırı daha düşükse o geçerli)
];

// --- Sunucu yapılandırması (depo dışı) --------------------------------------
// Turhost'ta kökte babyloon-config.php oluşturun:
//   <?php return ['env'=>'production','debug'=>false,'db'=>[
//       'driver'=>'mysql','host'=>'localhost','name'=>'kullanici_babyloon',
//       'user'=>'kullanici_babyloon','pass'=>'…','charset'=>'utf8mb4']];
$serverConfig = OB_ROOT . '/babyloon-config.php';
if (is_file($serverConfig)) {
    $override = require $serverConfig;
    if (is_array($override)) {
        $config = array_replace_recursive($config, $override);
    }
}

// Yerelde bile MySQL kullanmak isterseniz ortam değişkeni yeterli
if (getenv('OB_DB_DSN')) {
    $config['db'] = ['driver' => 'dsn', 'dsn' => getenv('OB_DB_DSN'),
                     'user' => getenv('OB_DB_USER') ?: '', 'pass' => getenv('OB_DB_PASS') ?: ''];
}

if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

return $config;
