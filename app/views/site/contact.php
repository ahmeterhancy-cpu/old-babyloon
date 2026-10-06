<?php
$errors = $data['errors'];
$hours  = $data['hours'];
$today  = (int) date('N');
$state  = $data['openState'];
$err = fn(string $k): string => isset($errors[$k]) ? ' has-err' : '';
?>
<section class="phead">
  <div class="wrap">
    <p class="crumb"><a href="<?= e(url('')) ?>"><?= e(t('nav.home')) ?></a> · <?= e(t('contact.title')) ?></p>
    <h1 class="phead__t"><?= e(t('contact.title')) ?></h1>
    <p class="phead__x"><?= e(setting('address')) ?></p>
  </div>
</section>

<?php
$tel = preg_replace('~[^0-9+]~', '', (string) setting('phone'));
$wa  = preg_replace('~[^0-9]~', '', (string) setting('whatsapp'));
?>
<div class="hcard hcard--page">

  <?php /* --- Bilgi kutuları: yalnızca girilmiş olanlar --- */ ?>
  <section class="hsec">
    <ul class="tinfo" data-stagger>
      <?php if ($tel): ?>
      <li class="reveal">
        <span class="tinfo__ico"><?= view('layout/icon', ['name' => 'phone']) ?></span>
        <p class="tinfo__k"><?= e(t('contact.call')) ?></p>
        <p class="tinfo__v"><a href="tel:<?= e($tel) ?>"><?= e(setting('phone')) ?></a></p>
      </li>
      <?php endif; ?>
      <?php if (setting('address')): ?>
      <li class="reveal">
        <span class="tinfo__ico"><?= view('layout/icon', ['name' => 'pin']) ?></span>
        <p class="tinfo__k"><?= e(t('contact.find')) ?></p>
        <p class="tinfo__v"><?= e(setting('address')) ?></p>
      </li>
      <?php endif; ?>
      <?php if (setting('email')): ?>
      <li class="reveal">
        <span class="tinfo__ico"><?= view('layout/icon', ['name' => 'mail']) ?></span>
        <p class="tinfo__k"><?= e(t('contact.write')) ?></p>
        <p class="tinfo__v"><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>
      </li>
      <?php endif; ?>
      <?php if ($wa): ?>
      <li class="reveal">
        <span class="tinfo__ico"><?= view('layout/icon', ['name' => 'whatsapp']) ?></span>
        <p class="tinfo__k">WhatsApp</p>
        <p class="tinfo__v"><a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener noreferrer"><?= e(t('contact.whatsapp')) ?></a></p>
      </li>
      <?php endif; ?>
    </ul>
  </section>

  <hr class="hdiv">

  <?php /* --- Form + saatler --- */ ?>
  <section class="hsec">
  <div class="tcontact">
    <div class="tcontact__form reveal">
      <p class="kick"><?= e(t('nav.contact')) ?></p>
      <h2 class="htitle"><?= e(t('contact.form_title')) ?></h2>
      <?php if ($errors): ?>
      <p class="field__err" role="alert"><?= e(t('err.form')) ?></p>
      <?php endif; ?>
      <form class="form" method="post" data-once>
        <?= csrf_field() ?>
        <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div class="form__row form__row--2">
          <div class="field<?= $err('name') ?>">
            <label for="c-name"><?= e(t('contact.name')) ?></label>
            <input id="c-name" name="name" type="text" required maxlength="120" value="<?= e((string) old('name')) ?>">
            <?php if (isset($errors['name'])): ?><span class="field__err"><?= e($errors['name']) ?></span><?php endif; ?>
          </div>
          <div class="field<?= $err('email') ?>">
            <label for="c-mail"><?= e(t('contact.email')) ?></label>
            <input id="c-mail" name="email" type="email" required maxlength="160" value="<?= e((string) old('email')) ?>">
            <?php if (isset($errors['email'])): ?><span class="field__err"><?= e($errors['email']) ?></span><?php endif; ?>
          </div>
        </div>
        <div class="form__row form__row--2">
          <div class="field">
            <label for="c-phone"><?= e(t('contact.phone')) ?> <span>(<?= e(t('optional')) ?>)</span></label>
            <input id="c-phone" name="phone" type="tel" maxlength="40" value="<?= e((string) old('phone')) ?>">
          </div>
          <div class="field">
            <label for="c-subject"><?= e(t('contact.subject')) ?> <span>(<?= e(t('optional')) ?>)</span></label>
            <input id="c-subject" name="subject" type="text" maxlength="200" value="<?= e((string) old('subject')) ?>">
          </div>
        </div>
        <div class="field<?= $err('body') ?>">
          <label for="c-body"><?= e(t('contact.message')) ?></label>
          <textarea id="c-body" name="body" required maxlength="4000"><?= e((string) old('body')) ?></textarea>
          <?php if (isset($errors['body'])): ?><span class="field__err"><?= e($errors['body']) ?></span><?php endif; ?>
        </div>
        <div class="actions">
          <button class="btn" type="submit" data-sending="<?= e(t('sending')) ?>"><?= e(t('send')) ?></button>
        </div>
      </form>
    </div>

    <aside class="tcontact__side reveal">
      <p class="kick"><?= e(t('home.hours_kicker')) ?></p>
      <h2 class="htitle htitle--sm"><?= e(t('home.hours_title')) ?></h2>
      <p class="tcontact__state">
        <span class="dot <?= $state['open'] ? 'is-open' : 'is-closed' ?>" aria-hidden="true"></span>
        <?= e($state['open'] ? t('open_now') : t('closed_now')) ?>
      </p>
      <ul class="hours">
        <?php foreach ($hours as $no => $h): ?>
        <li<?= $no === $today ? ' class="is-today"' : '' ?>>
          <span><?= e(t('day.' . $no)) ?></span>
          <b><?= (int) $h['is_closed'] === 1 ? e(t('closed')) : e($h['open_time'] . ' – ' . $h['close_time']) ?></b>
        </li>
        <?php endforeach; ?>
      </ul>
    </aside>
  </div>
  </section>

<?php if (setting('map_embed')): ?>
  <hr class="hdiv">
  <section class="hsec"><div class="tmap reveal"><?= setting('map_embed') ?></div></section>
<?php endif; ?>
</div>
