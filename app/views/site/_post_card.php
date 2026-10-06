<?php
/**
 * Yazı kartı — ana sayfa ve blog listesi aynı parçayı kullanır.
 *
 * Tarih rozeti fotoğrafın üstünde durur: gün büyük, ay küçük. Kartta yalnızca
 * gerçek bilgi var; yorum sistemi olmadığı için "yorum yok" gibi bir satır
 * yazılmıyor.
 */
$p     = $data['post'];
$class = $data['class'] ?? '';
$level = $data['level'] ?? 'h3';   // liste sayfasında h2, ana sayfada h3

$ts     = strtotime((string) $p['published_at']) ?: time();
$day    = date('j', $ts);
$month  = t('month.' . (int) date('n', $ts));
?>
<a class="pcard <?= e($class) ?>" href="<?= e(route_url('blog', $p['slug'])) ?>">
  <div class="pcard__media">
    <img class="pcard__img" src="<?= e(img($p['cover'], 'assets/img/placeholder-post.svg')) ?>"
         alt="" loading="lazy">
    <time class="pcard__date" datetime="<?= e(date('Y-m-d', $ts)) ?>">
      <b><?= e($day) ?></b><span><?= e($month) ?></span>
    </time>
  </div>

  <div class="pcard__b">
    <<?= $level ?> class="pcard__t"><?= e(tr_col($p, 'title')) ?></<?= $level ?>>
    <p class="pcard__x"><?= e(excerpt(tr_col($p, 'excerpt') ?: tr_col($p, 'body'), 120)) ?></p>
    <span class="pcard__more"><?= e(t('read_more')) ?><?= view('layout/icon', ['name' => 'arrow']) ?></span>
  </div>
</a>
