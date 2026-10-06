<?php
/**
 * Menü kitapçığı — basılı menünün sayfa çevirmeli hâli.
 *
 * Yapı gerçek bir kitabı taklit eder: her "yaprak" iki yüzlüdür, sağdan sola
 * çevrilir. Masaüstünde iki sayfa yan yana açılır, dar ekranda tek sayfa.
 * JS kapalıysa sayfalar alt alta listelenir — içerik her koşulda okunur.
 */
$pages = $data['pages'];
$total = count($pages);

/* Yapraklar: her yaprağın önü tek, arkası çift sayfa */
$leaves = [];
for ($i = 0; $i < $total; $i += 2) {
    $leaves[] = ['front' => $pages[$i], 'back' => $pages[$i + 1] ?? null];
}
$pdf = $data['pdf'];
?>

<section class="phead">
  <div class="wrap">
    <p class="crumb"><a href="<?= e(url('')) ?>"><?= e(t('nav.home')) ?></a> · <?= e(t('nav.menu')) ?></p>
    <h1 class="phead__t"><?= e(t('book.title')) ?></h1>
    <p class="phead__x"><?= e(t('home.book_text', ['p' => $total])) ?></p>
  </div>
</section>

<section class="sec sec--milk sec--book">
  <div class="wrap">

    <div class="book" data-book data-total="<?= $total ?>">
      <div class="book__stage">
        <div class="book__shadow" aria-hidden="true"></div>

        <?php foreach ($leaves as $li => $leaf): ?>
        <div class="book__leaf" data-leaf="<?= $li ?>">
          <div class="book__face book__face--front">
            <img src="<?= e(asset($leaf['front']['image'])) ?>"
                 alt="<?= e(tr_col($leaf['front'], 'caption') ?: t('book.page', ['n' => $li * 2 + 1, 't' => $total])) ?>"
                 <?= $li > 1 ? 'loading="lazy"' : '' ?>>
          </div>
          <?php if ($leaf['back']): ?>
          <div class="book__face book__face--back">
            <img src="<?= e(asset($leaf['back']['image'])) ?>"
                 alt="<?= e(tr_col($leaf['back'], 'caption') ?: t('book.page', ['n' => $li * 2 + 2, 't' => $total])) ?>"
                 loading="lazy">
          </div>
          <?php else: ?>
          <div class="book__face book__face--back book__face--blank"></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <button class="book__edge book__edge--prev" type="button" data-book-prev>
          <span class="sr"><?= e(t('book.prev')) ?></span>
        </button>
        <button class="book__edge book__edge--next" type="button" data-book-next>
          <span class="sr"><?= e(t('book.next')) ?></span>
        </button>
      </div>
    </div>

    <div class="book__bar">
      <button class="book__btn" type="button" data-book-prev>
        <?= view('layout/icon', ['name' => 'arrow', 'class' => 'ico ico--flip']) ?>
        <span><?= e(t('book.prev')) ?></span>
      </button>

      <p class="book__count" data-book-count aria-live="polite">
        <?= e(t('book.page', ['n' => 1, 't' => $total])) ?>
      </p>

      <button class="book__btn" type="button" data-book-next>
        <span><?= e(t('book.next')) ?></span>
        <?= view('layout/icon', ['name' => 'arrow']) ?>
      </button>
    </div>

    <p class="book__hint"><?= e(t('book.hint')) ?></p>

    <div class="actions actions--c mt-l">
      <a class="btn btn--ghost" href="<?= e(route_url('qrmenu')) ?>"><?= e(t('nav.qrmenu')) ?></a>
      <?php if ($pdf): ?>
      <a class="btn btn--ghost" href="<?= e(asset($pdf)) ?>" download><?= e(t('book.download')) ?></a>
      <?php endif; ?>
    </div>

    <!-- JS yoksa sayfalar burada okunur -->
    <noscript>
      <div class="booklist">
        <h2><?= e(t('book.list')) ?></h2>
        <?php foreach ($pages as $n => $p): ?>
        <img src="<?= e(asset($p['image'])) ?>"
             alt="<?= e(tr_col($p, 'caption') ?: t('book.page', ['n' => $n + 1, 't' => $total])) ?>">
        <?php endforeach; ?>
      </div>
    </noscript>
  </div>
</section>

<script src="<?= e(asset('assets/js/book.js')) ?>" defer></script>
