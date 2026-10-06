<?php /* Hata sayfası: iç sayfaların koyu bandı, tüm içerik bandın içinde */ ?>
<section class="phead perr">
  <div class="wrap">
    <p class="perr__code" aria-hidden="true">404</p>
    <h1 class="phead__t"><?= e(t('err.404_title')) ?></h1>
    <p class="phead__x"><?= e(t('err.404_text')) ?></p>
    <div class="perr__act">
      <a class="btn" href="<?= e(url('')) ?>"><?= e(t('err.home')) ?></a>
      <a class="tlink tlink--light" href="<?= e(route_url('menubook')) ?>"><?= e(t('nav.menu')) ?></a>
      <a class="tlink tlink--light" href="<?= e(route_url('qrmenu')) ?>"><?= e(t('home.qr_link')) ?></a>
    </div>
  </div>
</section>
