<?php $photos = $data['photos']; ?>
<section class="phead">
  <div class="wrap">
    <p class="crumb"><a href="<?= e(url('')) ?>"><?= e(t('nav.home')) ?></a> · <?= e(t('gallery.title')) ?></p>
    <h1 class="phead__t"><?= e(t('gallery.title')) ?></h1>
    <p class="phead__x"><?= e(t('home.instagram_lead')) ?></p>
  </div>
</section>
<div class="hcard hcard--page">
<section class="hsec">
  <div>
    <?php if ($photos): ?>
    <header class="hhead reveal">
      <p class="kick kick--c"><?= e(t('home.dishes_kicker')) ?></p>
      <h2 class="htitle"><?= e(t('home.gallery_title')) ?></h2>
      <p class="hmuted"><?= e(t('gallery.count', ['n' => count($photos)])) ?></p>
    </header>
    <div class="gal" data-stagger>
      <?php foreach ($photos as $i => $g): ?>
      <?php /* Fotoğrafların çoğunun açıklaması yok; o zaman bağlantı ekran
               okuyucuya sessiz kalıyordu. Açıklama varsa o, yoksa bağlantının
               NE YAPTIĞI okunur. */ ?>
      <?php $cap = tr_col($g, 'caption'); ?>
      <a class="gal__i <?= $i % 5 === 0 ? 'gal__i--wide' : '' ?> reveal" href="<?= e(asset($g['image'])) ?>"
         aria-label="<?= e($cap !== '' ? $cap : t('gallery.open_photo', ['n' => $i + 1, 't' => count($photos)])) ?>"
         data-lightbox>
        <?= img_responsive($g['image'], ['alt' => $cap, 'loading' => 'lazy'],
              '(min-width: 1000px) 33vw, (min-width: 640px) 50vw, 100vw') ?>
        <?php if ($cap !== ''): ?>
        <span class="gal__cap"><?= e($cap) ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="hcenter reveal">
      <a class="btn" href="<?= e(route_url('qrmenu')) ?>"><?= e(t('home.dishes_cta')) ?></a>
    </div>
    <?php else: ?>
    <p class="muted center"><?= e(t('gallery.empty')) ?></p>
    <?php endif; ?>
  </div>
</section>
</div>
