<?php
/**
 * Tek menü satırı: ad …… fiyat + açıklama + rozetler.
 * $data: item, photo (bool), class (ek sınıf)
 */
$item   = $data['item'];
$photo  = !empty($data['photo']) && has_img($item['image']);
$extra  = $data['class'] ?? '';
$out    = (int) $item['is_available'] === 0;
$badges = ob_badges($item['badges']);
$hasSizes = $item['price2'] !== null && $item['price2'] !== '';
?>
<article class="mitem <?= $photo ? 'mitem--photo' : '' ?> <?= $out ? 'is-out' : '' ?> <?= e($extra) ?>">
  <?php if ($photo): ?>
  <img class="mitem__photo" src="<?= e(asset($item['image'])) ?>" alt="" loading="lazy">
  <?php endif; ?>

  <h3 class="mitem__name"><?= e(tr_col($item, 'name')) ?></h3>
  <span class="mitem__lead" aria-hidden="true"></span>
  <span class="mitem__price">
    <?php if ($hasSizes): ?>
      <?= e(money_pair($item['price'], $item['price2'])) ?>
      <small><?= e(tr_col($item, 'size1') ?: '') ?> / <?= e(tr_col($item, 'size2') ?: '') ?></small>
    <?php else: ?>
      <?= e(money_or_dash($item['price'])) ?>
    <?php endif; ?>
  </span>

  <?php if (tr_col($item, 'description') !== ''): ?>
  <p class="mitem__desc"><?= e(tr_col($item, 'description')) ?></p>
  <?php endif; ?>

  <?php if ($badges || $out): ?>
  <div class="tags">
    <?php if ($out): ?><span class="tag tag--out"><?= e(t('menu.unavailable')) ?></span><?php endif; ?>
    <?php foreach ($badges as $b): ?>
    <span class="tag tag--<?= e($b) ?>"><?= e(t('badge.' . $b)) ?></span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</article>
