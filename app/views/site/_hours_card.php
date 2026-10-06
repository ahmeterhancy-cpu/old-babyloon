<?php
/**
 * Fotoğraflı koyu çalışma saatleri kartı (ana sayfa ve Hakkımızda ortak).
 * Ardışık günlerde aynı saatler tek satırda gruplanır: "Pazartesi – Cuma".
 *
 * @var array $data ['hours' => ob_hours(), 'bg' => ?görsel yolu]
 */
$hours = $data['hours'];
$bg    = $data['bg'] ?? null;
$today = (int) date('N');
$tel   = preg_replace('~[^0-9+]~', '', (string) setting('phone'));

$groups = [];
foreach ($hours as $no => $h) {
    $key = (int) $h['is_closed'] === 1 ? 'closed' : $h['open_time'] . '|' . $h['close_time'];
    $last = count($groups) - 1;
    if ($last >= 0 && $groups[$last]['key'] === $key) { $groups[$last]['to'] = $no; continue; }
    $groups[] = ['key' => $key, 'from' => $no, 'to' => $no, 'h' => $h];
}
$label = function (array $g): string {
    if ($g['from'] === 1 && $g['to'] === 7) { return t('home.everyday'); }
    return $g['from'] === $g['to'] ? t('day.' . $g['from']) : t('day.' . $g['from']) . ' – ' . t('day.' . $g['to']);
};
$big = fn(string $t): string => str_replace(':', '<i>:</i>', e($t));
?>
<div class="thours reveal">
  <?php if ($bg && has_img($bg)): ?>
  <div class="thours__bg"><?= img_responsive($bg, ['alt' => '', 'loading' => 'lazy'], '(min-width: 900px) 1100px, 100vw') ?></div>
  <?php endif; ?>
  <div class="thours__body">
    <p class="kick kick--light"><?= e(t('home.hours_kicker')) ?></p>
    <h2 class="htitle htitle--light"><?= e(t('home.hours_title')) ?></h2>
    <?php if (setting('address')): ?>
    <p class="thours__addr"><?= view('layout/icon', ['name' => 'pin']) ?> <?= e(setting('address')) ?></p>
    <?php endif; ?>
    <div class="hx__cta">
      <?php if ($tel): ?>
      <a class="btn" href="tel:<?= e($tel) ?>"><?= e(t('home.call')) ?></a>
      <?php endif; ?>
      <a class="tlink tlink--light" href="<?= e(route_url('contact')) ?>"><?= e(t('nav.contact')) ?></a>
    </div>
  </div>
  <div class="thours__card">
    <?php foreach ($groups as $g): ?>
    <div class="thours__g<?= $today >= $g['from'] && $today <= $g['to'] ? ' is-today' : '' ?>">
      <p class="thours__d"><?= e($label($g)) ?></p>
      <?php if ($g['key'] === 'closed'): ?>
      <p class="thours__t"><?= e(t('closed')) ?></p>
      <?php else: ?>
      <p class="thours__t"><?= $big($g['h']['open_time']) ?></p>
      <p class="thours__t"><?= $big($g['h']['close_time']) ?></p>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
