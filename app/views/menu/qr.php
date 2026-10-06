<?php
/**
 * QR menü — telefon için tek sayfa. Kendi hafif düzeni var:
 * site başlığı/altbilgisi yüklenmez, tek CSS dosyası, otomatik koyu tema.
 */
$categories = $data['categories'];
$itemsByCat = $data['itemsByCat'];
$loc        = locale();
$today      = (int) date('N');
?>
<!doctype html>
<html lang="<?= e($loc) ?>" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($data['title']) ?></title>
<meta name="description" content="<?= e(setting_t('menu_intro')) ?>">
<meta name="robots" content="noindex, follow">
<meta name="theme-color" content="#12100C">
<link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('assets/css/qr.css')) ?>">
<script>document.documentElement.className = 'js';</script>
</head>
<body class="qr">

<header class="qrhead">
  <div class="qrhead__top">
    <a class="qrhead__logo" href="<?= e(url('')) ?>" aria-label="<?= e(setting('site_name')) ?>">
      <?= view('layout/logo') ?>
    </a>
    <ul class="qrlang">
      <?php foreach (locales() as $code => $label): ?>
      <li><a hreflang="<?= e($code) ?>" href="<?= e(route_url('qrmenu', '', $code)) ?>"<?= $code === $loc ? ' aria-current="true"' : '' ?>><?= e(strtoupper($code)) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="qrsearch">
    <?= view('layout/icon', ['name' => 'search']) ?>
    <input type="search" data-menu-search placeholder="<?= e(t('qr.search')) ?>"
           aria-label="<?= e(t('qr.search')) ?>" autocomplete="off" enterkeyhint="search">
  </div>

</header>

<?php /* Şerit başlığın DIŞINDA: başlıkta overflow:clip var (mozaik için) ve
         kırpan bir kapsayıcının içindeki sticky yalnızca o kutunun içinde
         yapışır — başlık kayıp gidince şerit de gidiyordu. */ ?>
<nav class="chipbar" aria-label="<?= e(t('qr.jump')) ?>">
  <?php foreach ($categories as $c): ?>
  <a class="chip" data-chip="kat-<?= e($c['slug']) ?>" href="#kat-<?= e($c['slug']) ?>"><?= e(tr_col($c, 'name')) ?></a>
  <?php endforeach; ?>
</nav>

<main class="qrmain">
  <p class="qrempty" data-empty hidden><?= e(t('qr.no_result')) ?></p>

  <?php foreach ($categories as $c): ?>
  <?php $items = $itemsByCat[$c['id']] ?? []; if (!$items) { continue; } ?>
  <section class="qrgroup" id="kat-<?= e($c['slug']) ?>" data-group>
    <header class="qrgroup__h">
      <?= view('layout/icon', ['name' => $c['icon'] ?: 'cup']) ?>
      <div>
        <h2><?= e(tr_col($c, 'name')) ?></h2>
        <?php if (tr_col($c, 'tagline') !== ''): ?><p><?= e(tr_col($c, 'tagline')) ?></p><?php endif; ?>
      </div>
    </header>

    <ul class="qrlist">
      <?php $group = null; ?>
      <?php foreach ($items as $it): ?>
      <?php $g = tr_col($it, 'group'); ?>
      <?php if ($g !== $group && $g !== ''): ?>
      <li class="qrsub" data-item="<?= e($g) ?>"><?= e($g) ?></li>
      <?php endif; ?>
      <?php $group = $g; ?>
      <?php
        $badges = ob_badges($it['badges']);
        $out    = (int) $it['is_available'] === 0;
        $needle = tr_col($it, 'name') . ' ' . tr_col($it, 'description') . ' '
                . tr_col($it, 'group') . ' ' . tr_col($c, 'name');
        $hasSizes = $it['price2'] !== null && $it['price2'] !== '';
      ?>
      <li class="qritem<?= $out ? ' is-out' : '' ?>" data-item="<?= e($needle) ?>">
        <?php if (has_img($it['image'])): ?>
        <?php /* Çok geniş tabak fotoğrafı kırpılırsa yalnız ortası (çoğu zaman
                 patates) görünüyordu; onlar kırpılmadan sığdırılır. */
              $sz = @getimagesize(OB_ROOT . '/' . $it['image']);
              $wide = $sz && $sz[1] > 0 && $sz[0] / $sz[1] > 2; ?>
        <a class="qritem__zoom" href="<?= e(asset($it['image'])) ?>" data-lightbox
           data-cap="<?= e(tr_col($it, 'name')) ?>"
           aria-label="<?= e(t('menu.enlarge_photo', ['n' => tr_col($it, 'name')])) ?>">
          <?= img_responsive($it['image'], ['class' => 'qritem__ph' . ($wide ? ' qritem__ph--wide' : ''), 'alt' => '', 'loading' => 'lazy'], '84px') ?>
        </a>
        <?php endif; ?>
        <div class="qritem__b">
          <div class="qritem__top">
            <h3><?= e(tr_col($it, 'name')) ?></h3>
            <span class="qritem__price">
              <?php if ($hasSizes): ?>
                <?= e(money_pair($it['price'], $it['price2'])) ?>
              <?php else: ?>
                <?= e(money_or_dash($it['price'])) ?>
              <?php endif; ?>
            </span>
          </div>
          <?php if ($hasSizes && tr_col($it, 'size1') !== ''): ?>
          <p class="qritem__sizes"><?= e(tr_col($it, 'size1')) ?> / <?= e(tr_col($it, 'size2')) ?></p>
          <?php endif; ?>
          <?php if (tr_col($it, 'description') !== ''): ?>
          <p class="qritem__d"><?= e(tr_col($it, 'description')) ?></p>
          <?php endif; ?>
          <?php if ($badges || $out): ?>
          <div class="tags">
            <?php if ($out): ?><span class="tag tag--out"><?= e(t('menu.unavailable')) ?></span><?php endif; ?>
            <?php foreach ($badges as $b): ?>
            <span class="tag tag--<?= e($b) ?>"><?= e(t('badge.' . $b)) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endforeach; ?>
</main>

<footer class="qrftr">
  <p class="qrftr__note"><?= e(setting_t('menu_note', t('menu.allergen_note'))) ?></p>
  <p class="qrftr__note"><?= e(t('qr.call_waiter')) ?></p>
  <div class="qrftr__actions">
    <?php if (setting('phone')): ?>
    <a class="qrbtn" href="tel:<?= e(preg_replace('~[^0-9+]~', '', setting('phone'))) ?>">
      <?= view('layout/icon', ['name' => 'phone']) ?> <?= e(setting('phone')) ?>
    </a>
    <?php endif; ?>
    <?php if (setting('instagram')): ?>
    <a class="qrbtn" href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener noreferrer">
      <?= view('layout/icon', ['name' => 'instagram']) ?> <?= e(setting('instagram_tag', 'Instagram')) ?>
    </a>
    <?php endif; ?>
    <a class="qrbtn" href="<?= e(route_url('menubook')) ?>"><?= e(t('book.open')) ?></a>
    <a class="qrbtn" href="<?= e(url('')) ?>"><?= e(t('qr.back_to_site')) ?></a>
  </div>
  <p class="qrftr__brand"><?= e(setting('site_name')) ?> · <?= e(setting('address')) ?></p>
  <?php if (setting('legal_entity')): ?>
  <p class="qrftr__brand"><?= e(setting('legal_entity')) ?></p>
  <?php endif; ?>
  <p class="qrftr__made">
    Website by
    <a href="https://www.amesis.com.tr" target="_blank" rel="noopener noreferrer">Amesis 360 Dijital Ajans</a>
  </p>
</footer>

<a class="qrtop" href="#" aria-label="<?= e(t('back')) ?>">&uarr;</a>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
