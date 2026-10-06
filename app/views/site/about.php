<?php
/**
 * Hakkımızda — ana sayfayla aynı "kart" düzeni: koyu sayfa bandı, ardından
 * koyu dokulu zemin üstünde beyaz kart. Metin panelden (Site Ayarları →
 * Hakkında); boşsa o bölüm görünmez. Diğer her şey menü verisinden gelir.
 *
 * @var array $data
 */
$team      = $data['team'];
$features  = $data['features'];
$hours     = $data['hours'];
$image     = $data['image'];
$signature = $data['signature'];
$aboutText = setting_t('about_text');
?>
<section class="phead">
  <div class="wrap">
    <p class="crumb"><a href="<?= e(url('')) ?>"><?= e(t('nav.home')) ?></a> · <?= e(t('about.title')) ?></p>
    <h1 class="phead__t"><?= e(t('about.title')) ?></h1>
    <?php if (setting_t('tagline') !== ''): ?>
    <p class="phead__x"><?= e(setting_t('tagline')) ?></p>
    <?php endif; ?>
  </div>
</section>

<div class="hcard hcard--page">

  <?php if ($aboutText !== ''): ?>
  <?php /* --- Hikâye --- */ ?>
  <section class="hsec">
    <div class="tintro tintro--story">
      <?php if ($image): ?>
      <div class="tintro__media reveal">
        <?= img_responsive($image, ['alt' => '', 'loading' => 'lazy'], '(min-width: 900px) 520px, 100vw') ?>
      </div>
      <?php endif; ?>
      <div class="tintro__body reveal">
        <p class="kick"><?= e(setting('site_name')) ?></p>
        <h2 class="htitle"><?= e(setting_t('about_title', t('about.title'))) ?></h2>
        <div class="prose hmuted"><?= safe_html($aboutText) ?></div>
        <div class="hx__cta">
          <a class="btn" href="<?= e(route_url('menubook')) ?>"><?= e(t('home.intro_cta')) ?></a>
          <a class="tlink" href="<?= e(route_url('contact')) ?>"><?= e(t('nav.contact')) ?></a>
        </div>
      </div>
    </div>
  </section>
  <hr class="hdiv">
  <?php endif; ?>

  <?php if ($signature): ?>
  <?php /* --- Mutfağın imzası: menüde "Babil" adını taşıyan tabaklar --- */ ?>
  <section class="hsec">
    <header class="hhead reveal">
      <p class="kick kick--c"><?= e(t('about.sig_kicker')) ?></p>
      <h2 class="htitle"><?= e(t('about.sig_title')) ?></h2>
      <p class="hmuted"><?= e(t('about.sig_sub')) ?></p>
    </header>
    <ul class="tdish tdish--3" data-stagger>
      <?php foreach ($signature as $d): ?>
      <?php $desc = tr_col($d, 'description'); ?>
      <li class="reveal">
        <a class="tdish__c" href="<?= e(route_url('qrmenu')) ?>#kat-<?= e($d['cat_slug']) ?>">
          <span class="tdish__ph">
            <?= img_responsive($d['image'], ['alt' => '', 'loading' => 'lazy'], '(min-width: 1000px) 360px, (min-width: 640px) 45vw, 100vw') ?>
          </span>
          <span class="tdish__b">
            <span class="tdish__n"><?= e(tr_col($d, 'name')) ?></span>
            <?php if ($desc !== ''): ?><span class="tdish__x"><?= e($desc) ?></span><?php endif; ?>
            <span class="tdish__f">
              <?php if ((float) $d['price'] > 0): ?><b><?= e(money($d['price'])) ?></b><?php endif; ?>
              <em><?= e(t('home.dishes_more')) ?></em>
            </span>
          </span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <hr class="hdiv">
  <?php endif; ?>

  <?php if ($features): ?>
  <?php /* --- Kahvaltıdan nargileye --- */ ?>
  <section class="hsec">
    <header class="hhead reveal">
      <p class="kick kick--c"><?= e(t('home.feat_kicker')) ?></p>
      <h2 class="htitle"><?= e(t('home.features_title')) ?></h2>
    </header>
    <div class="tfeat" data-stagger>
      <?php foreach ($features as $f): ?>
      <article class="tfeat__i reveal">
        <span class="tfeat__badge"><?= view('layout/icon', ['name' => $f['icon'] ?: 'plate', 'class' => 'ico tfeat__ico']) ?></span>
        <h3 class="tfeat__t"><?= e(tr_col($f, 'title')) ?></h3>
        <p class="tfeat__x"><?= e(tr_col($f, 'text')) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <hr class="hdiv">
  <?php endif; ?>

  <?php if ($team): ?>
  <section class="hsec">
    <header class="hhead reveal">
      <p class="kick kick--c"><?= e(setting('site_name')) ?></p>
      <h2 class="htitle"><?= e(t('about.team_title')) ?></h2>
    </header>
    <div class="team" data-stagger>
      <?php foreach ($team as $m): ?>
      <article class="team__m reveal">
        <img class="team__ph" src="<?= e(img($m['photo'], 'assets/img/placeholder-person.svg')) ?>" alt="" loading="lazy">
        <h3 class="team__n"><?= e($m['name']) ?></h3>
        <p class="team__r"><?= e(tr_col($m, 'role')) ?></p>
        <?php if (tr_col($m, 'bio') !== ''): ?><p class="muted"><?= e(tr_col($m, 'bio')) ?></p><?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <hr class="hdiv">
  <?php endif; ?>

  <?php /* --- Ziyaret: saat kartı --- */ ?>
  <section class="hsec">
    <?= view('site/_hours_card', ['hours' => $hours, 'bg' => $data['hoursBg'] ?? null]) ?>
  </section>

</div>
