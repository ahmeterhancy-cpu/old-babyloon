<?php $posts = $data['posts']; ?>
<section class="phead">
  <div class="wrap">
    <p class="crumb"><a href="<?= e(url('')) ?>"><?= e(t('nav.home')) ?></a> · <?= e(t('blog.title')) ?></p>
    <h1 class="phead__t"><?= e(t('blog.title')) ?></h1>
    <p class="phead__x"><?= e(t('home.blog_sub')) ?></p>
  </div>
</section>
<div class="hcard hcard--page">
<section class="hsec">
  <div>
    <?php if ($posts): ?>
    <div class="grid grid--3" data-stagger>
      <?php foreach ($posts as $p): ?>
      <?= view('site/_post_card', ['post' => $p, 'class' => 'reveal', 'level' => 'h2']) ?>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="muted center"><?= e(t('blog.empty')) ?></p>
    <?php endif; ?>
  </div>
</section>
</div>
