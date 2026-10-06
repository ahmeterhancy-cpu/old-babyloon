<?php
$hours = ob_hours();
$today = (int) date('N');
$socials = array_filter([
    'instagram' => setting('instagram'),
    'facebook'  => setting('facebook'),
]);
?>
<footer class="ftr">
  <div class="wrap ftr__grid">

    <div class="ftr__col ftr__col--brand">
      <div class="ftr__logo"><?= view('layout/logo', ['white' => true]) ?></div>
      <p class="ftr__tag"><?= e(setting_t('tagline')) ?></p>
      <?php if ($socials): ?>
      <div class="ftr__social">
        <span class="ftr__sociallabel"><?= e(t('footer.follow')) ?></span>
        <?php foreach ($socials as $key => $link): ?>
        <a href="<?= e($link) ?>" rel="noopener noreferrer" target="_blank" aria-label="<?= e(ucfirst($key)) ?>">
          <?= view('layout/icon', ['name' => $key]) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="ftr__col">
      <h2 class="ftr__h"><?= e(t('footer.pages')) ?></h2>
      <ul class="ftr__list">
        <li><a href="<?= e(route_url('about')) ?>"><?= e(t('nav.about')) ?></a></li>
        <li><a href="<?= e(route_url('gallery')) ?>"><?= e(t('nav.gallery')) ?></a></li>
        <?php if (ob_has_posts()): ?>
        <li><a href="<?= e(route_url('blog')) ?>"><?= e(t('nav.blog')) ?></a></li>
        <?php endif; ?>
        <li><a href="<?= e(route_url('contact')) ?>"><?= e(t('nav.contact')) ?></a></li>
        <li><a href="<?= e(route_url('menubook')) ?>"><?= e(t('book.title')) ?></a></li>
        <li><a href="<?= e(route_url('qrmenu')) ?>"><?= e(t('nav.qrmenu')) ?></a></li>
      </ul>
    </div>

    <div class="ftr__col">
      <h2 class="ftr__h"><?= e(t('home.hours_title')) ?></h2>
      <ul class="ftr__hours">
        <?php foreach ($hours as $no => $h): ?>
        <li<?= $no === $today ? ' class="is-today"' : '' ?>>
          <span><?= e(t('day.' . $no)) ?></span>
          <b><?= (int) $h['is_closed'] === 1 ? e(t('closed')) : e($h['open_time'] . ' – ' . $h['close_time']) ?></b>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="ftr__col">
      <h2 class="ftr__h"><?= e(t('footer.visit')) ?></h2>
      <address class="ftr__addr">
        <p><?= e(setting('address')) ?></p>
        <?php if (setting('phone')): ?>
        <p><a href="tel:<?= e(preg_replace('~[^0-9+]~', '', setting('phone'))) ?>"><?= e(setting('phone')) ?></a></p>
        <?php endif; ?>
        <?php if (setting('email')): ?>
        <p><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>
        <?php endif; ?>
      </address>
      <?php if (setting('whatsapp')): ?>
      <a class="btn btn--ghost btn--sm" target="_blank" rel="noopener noreferrer"
         href="https://wa.me/<?= e(preg_replace('~[^0-9]~', '', setting('whatsapp'))) ?>">
        <?= view('layout/icon', ['name' => 'whatsapp']) ?> <?= e(t('contact.whatsapp')) ?>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="ftr__base">
    <div class="wrap ftr__baserow">
      <small>&copy; <?= date('Y') ?> <?= e(setting('site_name')) ?>. <?= e(t('footer.rights')) ?></small>
      <?php if (setting('legal_entity')): ?>
      <small class="ftr__entity"><?= e(setting('legal_entity')) ?></small>
      <?php endif; ?>
      <small class="ftr__made">
        Website by
        <a href="https://www.amesis.com.tr" target="_blank" rel="noopener noreferrer">Amesis 360 Dijital Ajans</a>
      </small>
    </div>
  </div>
</footer>
