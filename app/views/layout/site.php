<?php
/** @var array $data */
$title       = $data['title']       ?? setting('site_name', 'Old Babyloon');
$description = ($data['description'] ?? '') ?: setting_t('seo_desc');
$bodyClass   = $data['bodyClass']   ?? '';
$content     = $data['content']     ?? '';
$loc         = locale();
$flash       = flash();
$route       = $GLOBALS['OB_ROUTE'] ?? null;

$nav = [
    ['menubook',    t('nav.pdfmenu')],
    ['qrmenu',      t('nav.qrmenu')],
    ['about',       t('nav.about')],
    ['gallery',     t('nav.gallery')],
    ['blog',        t('nav.blog')],
    ['contact',     t('nav.contact')],
];
// Yazı yokken Blog bağlantısı boş bir sayfaya gitmesin
if (!ob_has_posts()) { $nav = array_values(array_filter($nav, fn($n) => $n[0] !== 'blog')); }
?>
<!doctype html>
<html lang="<?= e($loc) ?>" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<?php if ($description !== ''): ?>
<meta name="description" content="<?= e($description) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= e(ob_abs(current_path() === '' ? url('') : '/' . current_path())) ?>">
<?php foreach (array_keys(locales()) as $l): ?>
<link rel="alternate" hreflang="<?= e($l) ?>" href="<?= e(ob_abs(alt_lang_url($l))) ?>">
<?php endforeach; ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(setting('site_name')) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<?php /* Paylaşım önizlemesi. SVG çoğu platformda (WhatsApp, Facebook)
         çalışmaz; JPG şart. */ ?>
<meta property="og:image" content="<?= e(ob_abs(asset('assets/img/og-cover.jpg'))) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($title) ?>">
<?php if ($description !== ''): ?>
<meta name="twitter:description" content="<?= e($description) ?>">
<?php endif; ?>
<meta name="twitter:image" content="<?= e(ob_abs(asset('assets/img/og-cover.jpg'))) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:locale" content="<?= e($loc === 'tr' ? 'tr_TR' : 'en_GB') ?>">
<meta name="theme-color" content="#ffffff">
<link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<?php /* Yazı tipi kendi sunucumuzdan geliyor; Google Fonts'a giden
         çizim engelleyici istek kaldırıldı. Ekranın üstündeki metin bu
         iki dosyayı kullanıyor, onlar önceden alınıyor. */ ?>
<link rel="preload" as="font" type="font/woff2" crossorigin
      href="<?= e(asset('assets/fonts/montserrat-600-latin-ext.woff2')) ?>">
<link rel="preload" as="font" type="font/woff2" crossorigin
      href="<?= e(asset('assets/fonts/montserrat-400-latin-ext.woff2')) ?>">
<?php /* Başlık yazı tipi: açılış başlığı (LCP öğesi) bununla çizilir */ ?>
<link rel="preload" as="font" type="font/woff2" crossorigin
      href="<?= e(asset('assets/fonts/playfair-display-latin.woff2')) ?>">
<?php if (!empty($data['preloadImage'])): ?>
<?= img_preload_tag($data['preloadImage'], $data['preloadSizes'] ?? '100vw') ?>
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
<script>document.documentElement.className = document.documentElement.className.replace('no-js','js');</script>
<script type="application/ld+json"><?= json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type'    => 'Restaurant',
    'name'     => setting('site_name', 'Old Babyloon'),
    'url'      => ob_abs(url('')),
    'image'    => ob_abs(asset('assets/img/og-cover.jpg')),
    'telephone' => setting('phone'),
    'email'     => setting('email'),
    'address'   => ['@type' => 'PostalAddress', 'streetAddress' => setting('address'), 'addressCountry' => 'CY'],
    'hasMenu'   => ob_abs(route_url('menubook')),
    /* Konum yalnızca panelde koordinat girildiyse yazılır — uydurma nokta verilmez. */
    'geo'        => (setting('map_lat') !== '' && setting('map_lng') !== '')
        ? ['@type' => 'GeoCoordinates', 'latitude' => (float) setting('map_lat'), 'longitude' => (float) setting('map_lng')]
        : null,
    'priceRange' => '₺₺',
    'sameAs'     => array_values(array_filter([setting('instagram'), setting('facebook')])),
    /* Saatler panelde "doğrulandı" işaretli değilse Google'a gitmez. */
    'openingHoursSpecification' => setting('hours_confirmed') !== '1' ? null : array_values(array_filter(array_map(
        function (array $h): ?array {
            if ((int) $h['is_closed'] === 1) { return null; }
            $names = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];
            return ['@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $names[(int) $h['day_no']] ?? 'Monday',
                    'opens' => $h['open_time'], 'closes' => $h['close_time']];
        }, ob_hours()
    ))),
], fn($v) => $v !== null && $v !== '' && $v !== []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip" href="#main"><?= e(t('nav.skip')) ?></a>

<header class="hdr" data-header>
  <div class="hdr__bar">
    <div class="wrap hdr__row">
      <a class="logo" href="<?= e(url('')) ?>" aria-label="<?= e(setting('site_name')) ?>">
        <?= view('layout/logo') ?>
      </a>

      <nav class="nav" aria-label="<?= e(t('nav.menu')) ?>">
        <ul class="nav__list">
          <?php foreach ($nav as [$name, $label]): ?>
          <li><a href="<?= e(route_url($name)) ?>"<?= $route === $name ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <div class="hdr__side">
        <ul class="lang" aria-label="Language">
          <?php foreach (locales() as $code => $label): ?>
          <li><a hreflang="<?= e($code) ?>" href="<?= e(alt_lang_url($code)) ?>"<?= $code === $loc ? ' aria-current="true"' : '' ?>><?= e(strtoupper($code)) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php if (setting('phone')): ?>
        <a class="btn btn--sm" href="tel:<?= e(preg_replace('~[^0-9+]~', '', setting('phone'))) ?>"><?= e(setting('phone')) ?></a>
        <?php endif; ?>
        <button class="burger" type="button" data-drawer-open aria-expanded="false" aria-controls="drawer">
          <span></span><span></span><span></span>
          <em class="sr"><?= e(t('nav.open_menu')) ?></em>
        </button>
      </div>
    </div>
  </div>

</header>

<div class="drawer" id="drawer" hidden>
  <div class="drawer__panel">
    <button class="drawer__close" type="button" data-drawer-close><em class="sr"><?= e(t('nav.close_menu')) ?></em>&times;</button>
    <ul class="drawer__nav">
      <li><a href="<?= e(url('')) ?>"><?= e(t('nav.home')) ?></a></li>
      <?php foreach ($nav as [$name, $label]): ?>
      <li><a href="<?= e(route_url($name)) ?>"><?= e($label) ?></a></li>
      <?php endforeach; ?>
      <li><a href="<?= e(route_url('menubook')) ?>"><?= e(t('book.title')) ?></a></li>
      <li><a href="<?= e(route_url('qrmenu')) ?>"><?= e(t('nav.qrmenu')) ?></a></li>
    </ul>
    <div class="drawer__meta">
      <a href="tel:<?= e(preg_replace('~[^0-9+]~', '', setting('phone'))) ?>"><?= e(setting('phone')) ?></a>
      <p><?= e(setting('address')) ?></p>
    </div>
  </div>
</div>

<?php if ($flash): ?>
<div class="flash flash--<?= e($flash['type']) ?>" role="status"><div class="wrap"><?= e($flash['msg']) ?></div></div>
<?php endif; ?>

<main id="main"><?= $content ?></main>

<?= view('layout/footer') ?>

<script src="<?= e(asset('assets/js/motion.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
