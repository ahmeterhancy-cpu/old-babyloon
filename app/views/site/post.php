<?php $post = $data['post']; $others = $data['others']; ?>
<section class="phead">
  <div class="wrap">
    <p class="crumb">
      <a href="<?= e(url('')) ?>"><?= e(t('nav.home')) ?></a> ·
      <a href="<?= e(route_url('blog')) ?>"><?= e(t('blog.title')) ?></a>
    </p>
    <h1 class="phead__t"><?= e(tr_col($post, 'title')) ?></h1>
    <p class="phead__x"><?= e(t('blog.published')) ?>: <?= e(ob_date((string) $post['published_at'])) ?><?= $post['author'] ? ' · ' . e($post['author']) : '' ?></p>
  </div>
</section>
<div class="hcard hcard--page">
<article class="hsec">
  <div>
    <?php if (has_img($post['cover'])): ?>
    <img class="postcover reveal" src="<?= e(asset($post['cover'])) ?>" alt="">
    <?php endif; ?>
    <div class="prose reveal" style="margin-inline:auto">
      <?php if (tr_col($post, 'excerpt') !== ''): ?>
      <p class="lede"><?= e(tr_col($post, 'excerpt')) ?></p>
      <?php endif; ?>
      <?= safe_html(tr_col($post, 'body')) ?>
    </div>
  </div>
</article>
<?php if ($others): ?>
<hr class="hdiv">
<section class="hsec">
  <div>
    <header class="shead reveal">
      <p class="shead__kicker"><?= e(t('blog.title')) ?></p>
      <h2 class="shead__t"><?= e(t('blog.other_posts')) ?></h2>
    </header>
    <div class="grid grid--3" data-stagger>
      <?php foreach ($others as $p): ?>
      <a class="pcard reveal" href="<?= e(route_url('blog', $p['slug'])) ?>">
        <img class="pcard__img" src="<?= e(img($p['cover'], 'assets/img/placeholder-post.svg')) ?>" alt="" loading="lazy">
        <div class="pcard__b">
          <span class="pcard__date"><?= e(ob_date((string) $p['published_at'])) ?></span>
          <h3 class="pcard__t"><?= e(tr_col($p, 'title')) ?></h3>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
</div>
