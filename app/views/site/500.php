<?php /* Hata sayfası: iç sayfaların koyu bandı, tüm içerik bandın içinde */ ?>
<section class="phead perr">
  <div class="wrap">
    <p class="perr__code" aria-hidden="true">500</p>
    <h1 class="phead__t"><?= e(t('err.500_title')) ?></h1>
    <p class="phead__x"><?= e(t('err.500_text')) ?></p>
    <div class="perr__act">
      <a class="btn" href="<?= e(url('')) ?>"><?= e(t('err.home')) ?></a>
    </div>
  </div>
</section>
