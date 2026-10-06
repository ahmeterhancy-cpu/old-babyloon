<?php
/** PHP yerleşik sunucusu için yönlendirici — canlıda .htaccess bu işi görür. */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file) && !str_ends_with($path, '.php')) {
    return false; // statik dosyayı sunucu versin
}
if ($path === '/migrate.php') { require __DIR__ . '/migrate.php'; return true; }
require __DIR__ . '/index.php';
