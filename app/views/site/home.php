<?php
/**
 * Ana sayfa — "kart" düzeni: koyu dokulu zemin üstünde yüzen beyaz kartlar,
 * tam ekran açılış, noktalı çizgiyle ayrılan bölümler (referans: Tastyc).
 * Bütün içerik menü verisinden, fotoğraflardan ve panel ayarlarından gelir;
 * yorum, sayaç ya da slogan uydurulmaz.
 *
 * @var array $data
 */
$slides     = $data['slides'];
$menuCats   = $data['menuCats'];
$introImage = $data['introImage'];
$qrSvg      = $data['qrSvg'];
$bookCover  = $data['bookCover'];
$bookPdf    = $data['bookPdf'];
$hasBook    = $data['hasBook'];
$features   = $data['features'];
$posts      = $data['posts'];
$gallery    = $data['gallery'];
$hours      = $data['hours'];
$igPosts    = $data['instagram'] ?? [];
$pageCount  = (int) ($data['pageCount'] ?? 0);
$totalItems = array_sum(array_column($menuCats, 'n'));

?>

<?php /* ================= AÇILIŞ ================= */ ?>
<section class="hx">
  <div class="hero__slides">
    <?php foreach ($slides as $i => $s): ?>
    <article class="hero__slide hx__slide">
      <?php if (has_img($s['image'])): ?>
      <div class="hx__bg">
        <?= img_responsive($s['image'],
              ['alt' => '', 'class' => 'hx__img']
                + ($i ? ['data-defer' => '1', 'loading' => 'lazy'] : ['fetchpriority' => 'high']),
              '100vw') ?>
      </div>
      <?php endif; ?>
      <div class="wrap hx__inner">
        <?php $k = tr_col($s, 'kicker') ?: t('home.hello'); ?>
        <p class="kick hx__kick"><?= e($k) ?></p>
        <?php $et = $i === 0 ? 'h1' : 'h2'; ?>
        <<?= $et ?> class="hx__title"><?= e(tr_col($s, 'title')) ?></<?= $et ?>>
        <?php if (tr_col($s, 'text') !== ''): ?>
        <p class="hx__text"><?= e(tr_col($s, 'text')) ?></p>
        <?php endif; ?>
        <div class="hx__cta">
          <?php if (tr_col($s, 'btn_label') !== ''): ?>
          <a class="btn" href="<?= e((string) $s['btn_url'] !== '' ? url((string) $s['btn_url']) : route_url('menubook')) ?>"><?= e(tr_col($s, 'btn_label')) ?></a>
          <?php endif; ?>
          <a class="tlink tlink--light" href="<?= e(route_url('qrmenu')) ?>"><?= e(t('home.qr_link')) ?></a>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <?php if (count($slides) > 1): ?>
  <div class="wrap hx__nav">
    <div class="hero__dots hx__dots">
      <?php foreach ($slides as $i => $s): ?>
      <button type="button" aria-current="false"><span class="sr"><?= $i + 1 ?>. görsel</span></button>
      <?php endforeach; ?>
    </div>
    <div class="hx__arrows">
      <button type="button" class="hx__arrow" data-hero-prev aria-label="<?= e(t('hero.prev')) ?>"><?= view('layout/icon', ['name' => 'arrow', 'class' => 'ico ico--flip']) ?></button>
      <button type="button" class="hx__arrow" data-hero-next aria-label="<?= e(t('hero.next')) ?>"><?= view('layout/icon', ['name' => 'arrow']) ?></button>
    </div>
  </div>
  <?php endif; ?>
</section>

<?php /* Farenin durduğu dikiş: kart burada başlar */ ?>
<div class="hseam">
  <a class="hseam__mouse" href="#icerik" aria-label="<?= e(t('home.scroll')) ?>"><span></span></a>
</div>
<span id="icerik" class="anchor" aria-hidden="true"></span>

<?php /* ================= 1. KART ================= */ ?>
<div class="hcard">

  <?php /* --- Tanıtım --- */ ?>
  <section class="hsec">
    <div class="tintro">
      <?php if ($introImage): ?>
      <div class="tintro__media reveal">
        <?= img_responsive($introImage, ['alt' => '', 'loading' => 'lazy'], '(min-width: 900px) 520px, 100vw') ?>
      </div>
      <?php endif; ?>
      <div class="tintro__body reveal">
        <p class="kick"><?= e(t('home.intro_kicker')) ?></p>
        <?php if (setting_t('about_text') !== ''): ?>
        <h2 class="htitle"><?= e(setting_t('about_title') ?: t('home.intro_title')) ?></h2>
        <?php /* Ana sayfada yalnız ilk paragraf, düz metin olarak */
              $about1 = preg_match('~<p[^>]*>(.*?)</p>~s', setting_t('about_text'), $am) ? $am[1] : setting_t('about_text');
              $about1 = trim(html_entity_decode(strip_tags($about1), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?>
        <p class="hmuted"><?= e($about1) ?></p>
        <?php else: ?>
        <h2 class="htitle"><?= e(t('home.intro_title')) ?></h2>
        <p class="hmuted"><?= e(t('home.intro_text', ['c' => count($menuCats), 'n' => $totalItems])) ?></p>
        <?php endif; ?>
        <div class="hx__cta">
          <a class="btn" href="<?= e(route_url('menubook')) ?>"><?= e(t('home.intro_cta')) ?></a>
          <?php if (setting_t('about_text') !== ''): ?>
          <a class="tlink" href="<?= e(route_url('about')) ?>"><?= e(t('home.about_cta')) ?></a>
          <?php else: ?>
          <a class="tlink" href="<?= e(route_url('qrmenu')) ?>"><?= e(t('home.qr_link')) ?></a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php if ($features): ?>
  <hr class="hdiv">
  <?php /* --- Öne çıkanlar --- */ ?>
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
  <?php endif; ?>

  <hr class="hdiv">

  <?php /* --- Çalışma saatleri: fotoğraflı koyu kart + beyaz saat kartı --- */ ?>
  <section class="hsec">
    <?= view('site/_hours_card', ['hours' => $hours, 'bg' => $slides[1]['image'] ?? ($slides[0]['image'] ?? null)]) ?>
  </section>

</div>

<?php /* ================= KOYU BANT: QR menü ================= */ ?>
<section class="tband">
  <div class="wrap tband__grid">
    <div class="reveal">
      <p class="kick kick--light"><?= e(t('home.qr_kicker')) ?></p>
      <h2 class="htitle htitle--light htitle--xl"><?= e(t('home.qr_title')) ?></h2>
      <p class="tband__text"><?= e(t('home.qr_text')) ?></p>
      <div class="hx__cta">
        <a class="btn" href="<?= e(route_url('qrmenu')) ?>"><?= e(t('home.qr_open')) ?></a>
        <?php if ($bookPdf): ?>
        <a class="btn btn--dark" href="<?= e(asset($bookPdf)) ?>" download><?= e(t('book.download')) ?></a>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($qrSvg): ?>
    <figure class="tband__qr reveal">
      <div class="tband__code"><?= $qrSvg ?></div>
      <figcaption><?= e(t('home.qr_caption')) ?></figcaption>
    </figure>
    <?php endif; ?>
  </div>
</section>

<?php /* ================= 2. KART ================= */ ?>
<div class="hcard hcard--2">

  <?php if ($menuCats): ?>
  <?php /* --- Menü haritası --- */ ?>
  <section class="hsec">
    <header class="hhead reveal">
      <p class="kick kick--c"><?= e(t('home.cats_kicker')) ?></p>
      <h2 class="htitle"><?= e(t('home.cats_title')) ?></h2>
      <p class="hmuted"><?= e(t('home.cats_sub', ['c' => count($menuCats), 'n' => $totalItems])) ?></p>
    </header>
    <ul class="mcats__grid" data-stagger>
      <?php foreach ($menuCats as $c): ?>
      <li class="reveal">
        <a class="mcat" href="<?= e(route_url('qrmenu')) ?>#kat-<?= e($c['slug']) ?>">
          <span class="mcat__ph">
            <?php if ($c['photo']): ?>
            <?= img_responsive($c['photo'], ['alt' => '', 'loading' => 'lazy'], '(min-width: 1000px) 240px, 50vw') ?>
            <?php else: ?>
            <?= view('layout/icon', ['name' => $c['icon'] ?: 'plate', 'class' => 'ico mcat__ico']) ?>
            <?php endif; ?>
          </span>
          <span class="mcat__b">
            <span class="mcat__n"><?= e(tr_col($c, 'name')) ?></span>
            <span class="mcat__m">
              <?= e(t('home.cats_count', ['n' => $c['n']])) ?><?php if ($c['minp'] !== null): ?> · <?= e(t('home.cats_from', ['p' => money($c['minp'])])) ?><?php endif; ?>
            </span>
          </span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>


  <?php if ($posts): ?>
  <hr class="hdiv">
  <section class="hsec">
    <header class="hhead reveal">
      <p class="kick kick--c"><?= e(t('home.blog_title')) ?></p>
      <h2 class="htitle"><?= e(t('home.blog_sub')) ?></h2>
    </header>
    <div class="grid grid--3" data-stagger>
      <?php foreach ($posts as $p): ?>
      <?= view('site/_post_card', ['post' => $p, 'class' => 'reveal', 'level' => 'h3']) ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($hasBook): ?>
  <hr class="hdiv">
  <?php /* --- Basılı menü: fotoğraflı koyu kart (referanstaki bülten kartının yeri) --- */ ?>
  <section class="hsec">
    <div class="tbook reveal">
      <?php $bookBg = $gallery[2]['image'] ?? null; ?>
      <?php if ($bookBg && has_img($bookBg)): ?>
      <div class="tbook__bg"><?= img_responsive($bookBg, ['alt' => '', 'loading' => 'lazy'], '(min-width: 900px) 1100px, 100vw') ?></div>
      <?php endif; ?>
      <div class="tbook__body">
        <p class="kick kick--light kick--c"><?= e(t('home.book_kicker')) ?></p>
        <h2 class="htitle htitle--light"><?= e(t('home.book_title')) ?></h2>
        <p class="tbook__text"><?= e(t('home.book_text', ['p' => $pageCount])) ?></p>
        <div class="hx__cta hx__cta--c">
          <a class="btn" href="<?= e(route_url('menubook')) ?>"><?= e(t('book.open')) ?></a>
          <?php if ($bookPdf): ?>
          <a class="tlink tlink--light" href="<?= e(asset($bookPdf)) ?>" download><?= e(t('book.download')) ?></a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($igPosts || $gallery): ?>
  <hr class="hdiv">
  <?php /* --- Galeri / Instagram --- */ ?>
  <section class="hsec">
    <header class="hhead reveal">
      <p class="kick kick--c"><?= e(setting('instagram_tag') ?: t('nav.gallery')) ?></p>
      <h2 class="htitle"><?= e(t('home.gallery_title')) ?></h2>
      <p class="hmuted"><?= e(t('home.instagram_lead')) ?></p>
    </header>
    <div class="igfeed reveal">
      <?php if ($igPosts): ?>
      <?php foreach ($igPosts as $p): ?>
      <a class="igpost" href="<?= e((string) ($p['permalink'] ?: setting('instagram'))) ?>" target="_blank" rel="noopener noreferrer">
        <img src="<?= e(img($p['image'])) ?>" alt="<?= e(Instagram::shortCaption($p['caption'], 60)) ?>" loading="lazy">
        <span class="igpost__veil"><?= view('layout/icon', ['name' => 'instagram']) ?></span>
      </a>
      <?php endforeach; ?>
      <?php else: ?>
      <?php foreach (array_slice($gallery, 0, 6) as $gi => $g): ?>
      <?php $igcap = tr_col($g, 'caption'); ?>
      <a class="igpost" href="<?= e(asset($g['image'])) ?>" data-lightbox
         aria-label="<?= e($igcap !== '' ? $igcap : t('gallery.open_photo', ['n' => $gi + 1, 't' => min(6, count($gallery))])) ?>">
        <?= img_responsive($g['image'], ['alt' => $igcap, 'loading' => 'lazy'], '(min-width: 640px) 17vw, 45vw') ?>
        <span class="igpost__veil" aria-hidden="true"><?= view('layout/icon', ['name' => 'search']) ?></span>
      </a>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="hcenter reveal">
      <?php if (setting('instagram')): ?>
      <a class="btn" href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener noreferrer"><?= setting('ig_username') ? '@' . e(setting('ig_username')) : 'Instagram' ?></a>
      <?php else: ?>
      <a class="btn" href="<?= e(route_url('gallery')) ?>"><?= e(t('home.gallery_cta')) ?></a>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>
</div>
