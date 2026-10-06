<?php
/**
 * Marka işareti. Kılavuz gereği:
 *  - renkli logo yalnızca beyaz / açık nötr zeminlerde
 *  - terracotta ya da koyu zeminlerde beyaz varyant
 * Kullanım: view('layout/logo') veya view('layout/logo', ['white' => true])
 */
$white = !empty($data['white']);
$file  = $white ? 'logo-white.svg' : 'logo.svg';

static $cache = [];
if (!isset($cache[$file])) {
    $svg = trim((string) @file_get_contents(OB_ROOT . '/assets/img/' . $file));
    $cache[$file] = str_replace('class="brand', 'class="logo__svg brand', $svg);
}
?>
<?= $cache[$file] ?>
<span class="sr"><?= e(setting('site_name', 'Old Babyloon')) ?></span>