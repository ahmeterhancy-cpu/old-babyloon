<?php
declare(strict_types=1);

/* ==========================================================================
 *  Old Babyloon — genel site denetleyicileri
 * ======================================================================= */

/* ---- Sayfalar ----------------------------------------------------------- */

function page_home(): void
{
    require_once OB_APP . '/lib/instagram.php';
    Instagram::maybeRefresh();

    $slides = DB::all('SELECT * FROM slides WHERE is_active = 1 ORDER BY sort, id');

    // Menü ana sayfada listelenmez; yalnızca kitapçığa ve QR menüye yönlendirilir
    $cover = DB::one('SELECT image FROM menu_pages WHERE is_active = 1 ORDER BY sort, id LIMIT 1');
    $pageCount = (int) DB::value('SELECT COUNT(*) FROM menu_pages WHERE is_active = 1', [], 0);

    $menuCats = site_menu_cats();


    // Tanıtım görseli: öne çıkan kahvaltı fotoğrafı (Hakkımızda sayfası kendi görselini kullanır)
    $introImage = (string) DB::value("SELECT i.image FROM menu_items i JOIN menu_categories c ON c.id = i.category_id
                                      WHERE c.slug = 'kahvalti' AND i.image <> '' ORDER BY i.is_featured DESC, i.sort LIMIT 1", [], '');

    // QR menü kodu — panelde basılan kodun aynısı (/qr/menu)
    require_once OB_APP . '/lib/qrcode.php';
    $qrRow = DB::one("SELECT slug FROM qr_codes WHERE kind = 'menu' AND is_active = 1 ORDER BY id LIMIT 1");
    $qrSvg = null;
    if ($qrRow) {
        // Yayın alan adı girildiyse QR her zaman https adresine gider (baskı yerelden alınsa da)
        $scheme = setting('public_host') !== '' ? 'https' : ((($_SERVER['HTTPS'] ?? '') === 'on') ? 'https' : 'http');
        $host   = setting('public_host') ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $qrSvg  = QrCode::svg($scheme . '://' . $host . base() . '/qr/' . $qrRow['slug'], 8, 2, QrCode::M, '#12100C', '#FFFFFF');
    }

    render('site/home', [
        'menuCats'     => $menuCats,
        'introImage'   => has_img($introImage) ? $introImage : null,
        'qrSvg'        => $qrSvg,
        'pageCount'    => $pageCount,
        'title'        => setting_t('seo_title', setting('site_name', 'Old Babyloon')),
        'description'  => setting_t('seo_desc'),
        'slides'       => $slides,
        'bookCover'    => $cover['image'] ?? null,
        'bookPdf'      => site_menu_pdf(),
        'hasBook'      => $pageCount > 0,
        'features'     => DB::all('SELECT * FROM features WHERE is_active = 1 ORDER BY sort, id'),
        'testimonials' => DB::all('SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort, id'),
        'posts'        => DB::all('SELECT * FROM posts WHERE is_published = 1 ORDER BY published_at DESC LIMIT 3'),
        'gallery'      => DB::all('SELECT * FROM gallery WHERE is_active = 1 ORDER BY sort, id LIMIT 8'),
        'instagram'    => Instagram::posts(8),
        'hours'        => ob_hours(),
        /* LCP öğesi ilk slaydın fotoğrafı; baştan bildirilirse
           tarayıcı CSS'i beklemeden indirmeye başlar. */
        'preloadImage' => $slides[0]['image'] ?? null,
        'preloadSizes' => '100vw',
        'bodyClass'    => 'page-home is-cards',
    ]);
}


function page_about(): void
{
    /* Görsel: panelde "Hakkımızda görseli" varsa o; yoksa Dürüm & Meksika
       bölümünün öne çıkan fotoğrafı (ana sayfa kahvaltıyı kullanıyor). */
    $image = setting('about_image');
    if (!has_img($image)) {
        $image = (string) DB::value("SELECT i.image FROM menu_items i JOIN menu_categories c ON c.id = i.category_id
                                     WHERE c.slug = 'durum' AND i.image <> '' ORDER BY i.is_featured DESC, i.sort LIMIT 1", [], '');
    }

    // "Mutfağın imzası": menüde Babil adını taşıyan, fotoğraflı tabaklar
    $signature = array_values(array_filter(
        DB::all("SELECT i.*, c.slug AS cat_slug FROM menu_items i JOIN menu_categories c ON c.id = i.category_id
                 WHERE i.name_tr LIKE 'Babil %' AND i.is_available = 1 ORDER BY c.sort, i.sort"),
        fn(array $r) => has_img($r['image'])
    ));

    $slides = DB::all('SELECT image FROM slides WHERE is_active = 1 ORDER BY sort, id');

    render('site/about', [
        'title'       => t('about.title') . ' · ' . setting('site_name'),
        'description' => excerpt(setting_t('about_text'), 160),
        'team'        => DB::all('SELECT * FROM team WHERE is_active = 1 ORDER BY sort, id'),
        'features'    => DB::all('SELECT * FROM features WHERE is_active = 1 ORDER BY sort, id'),
        'hours'       => ob_hours(),
        'image'       => has_img($image) ? $image : null,
        'signature'   => $signature,
        'hoursBg'     => $slides[1]['image'] ?? ($slides[0]['image'] ?? null),
        'bodyClass'   => 'page-about is-cards',
    ]);
}

function page_gallery(): void
{
    render('site/gallery', [
        'title'     => t('gallery.title') . ' · ' . setting('site_name'),
        'description' => t('gallery.meta'),
        'photos'    => DB::all('SELECT * FROM gallery WHERE is_active = 1 ORDER BY sort, id'),
        'bodyClass' => 'page-gallery is-cards',
    ]);
}

function page_blog(): void
{
    render('site/blog', [
        'title'     => t('blog.title') . ' · ' . setting('site_name'),
        'posts'     => DB::all('SELECT * FROM posts WHERE is_published = 1 ORDER BY published_at DESC'),
        'bodyClass' => 'page-blog is-cards',
    ]);
}

function page_post(string $slug): void
{
    $post = DB::one('SELECT * FROM posts WHERE slug = ? AND is_published = 1', [$slug]);
    if (!$post) { abort404(); }

    render('site/post', [
        'title'       => tr_col($post, 'title') . ' · ' . setting('site_name'),
        'description' => tr_col($post, 'excerpt'),
        'post'        => $post,
        'others'      => DB::all('SELECT * FROM posts WHERE is_published = 1 AND id <> ? ORDER BY published_at DESC LIMIT 3', [$post['id']]),
        'bodyClass'   => 'page-post is-cards',
    ]);
}

function page_contact(): void
{
    $errors = [];
    $sent   = false;

    if (is_post()) {
        csrf_check();
        $name    = (string) input('name');
        $email   = (string) input('email');
        $phone   = (string) input('phone');
        $subject = (string) input('subject');
        $body    = (string) input('body');

        if ($name === '')  { $errors['name']  = t('err.required'); }
        if ($body === '')  { $errors['body']  = t('err.required'); }
        if ($email === '') { $errors['email'] = t('err.required'); }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = t('err.email'); }
        // Bal küpü — botlar bu alanı doldurur
        if (input('website') !== '') { $errors['website'] = 'spam'; }

        if (!$errors) {
            DB::insert('messages', [
                'name' => mb_substr($name, 0, 120), 'email' => mb_substr($email, 0, 160),
                'phone' => mb_substr($phone, 0, 40), 'subject' => mb_substr($subject, 0, 200),
                'body' => mb_substr($body, 0, 4000),
                'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                'is_read' => 0, 'created_at' => DB::now(),
            ]);
            clear_old();
            flash(t('contact.sent'));
            redirect(route_url('contact'));
        }
        keep_old($_POST);
    }

    render('site/contact', [
        'title'     => t('contact.title') . ' · ' . setting('site_name'),
        'description' => t('contact.meta', ['a' => setting('address'), 'p' => setting('phone')]),
        'errors'    => $errors,
        'sent'      => $sent,
        'hours'     => ob_hours(),
        'openState' => ob_open_state(),
        'bodyClass' => 'page-contact is-cards',
    ]);
}


/** Kitapçığın indirme PDF'i — panelden yüklenir; eski sabit dosya yedektir. */
function site_menu_pdf(): ?string
{
    $rel = trim((string) setting('menu_pdf'));
    if ($rel !== '' && is_file(OB_ROOT . '/' . $rel)) { return $rel; }
    return is_file(OB_ROOT . '/uploads/menu-book/menu.pdf') ? 'uploads/menu-book/menu.pdf' : null;
}

/** Menü kitapçığı — PDF menünün sayfa çevirmeli hâli. */
function page_menubook(): void
{
    $pages = DB::all('SELECT * FROM menu_pages WHERE is_active = 1 ORDER BY sort, id');
    if (!$pages) { abort404(); }

    render('site/menubook', [
        'title'       => t('book.title') . ' · ' . setting('site_name'),
        'description' => setting_t('menu_intro'),
        'pages'       => $pages,
        'pdf'         => site_menu_pdf(),
        'bodyClass'   => 'page-book',
    ]);
}

/** Telefonda açılan, arama yapılabilen tek sayfalık QR menü. */
function page_qrmenu(): void
{
    $cats = ob_categories();
    $itemsByCat = [];
    foreach ($cats as $c) {
        $itemsByCat[$c['id']] = DB::all(
            'SELECT * FROM menu_items WHERE category_id = ? ORDER BY sort, id', [$c['id']]
        );
    }

    echo view('menu/qr', [
        'title'      => t('qr.title') . ' · ' . setting('site_name'),
        'categories' => $cats,
        'itemsByCat' => $itemsByCat,
    ]);
}


/* ---- Menü özeti (ana sayfa ve Hakkımızda ortak) -------------------------- */

/** Her kategori için ürün sayısı, en düşük fiyat ve bir fotoğraf (öne çıkan önce). */
function site_menu_cats(): array
{
    $out = [];
    foreach (ob_categories() as $c) {
        $row = DB::one('SELECT COUNT(*) AS n, MIN(CASE WHEN price > 0 THEN price END) AS minp
                        FROM menu_items WHERE category_id = ? AND is_available = 1', [$c['id']]);
        $img = DB::value("SELECT image FROM menu_items WHERE category_id = ? AND image IS NOT NULL AND image <> ''
                          ORDER BY is_featured DESC, sort, id LIMIT 1", [$c['id']]);
        // Panelde kategori kapak görseli seçildiyse o; yoksa ürün fotoğrafı
        $photo = has_img($c['image'] ?? null) ? (string) $c['image']
               : (($img && has_img((string) $img)) ? (string) $img : null);
        $out[] = $c + ['n' => (int) ($row['n'] ?? 0), 'minp' => $row['minp'] ?? null, 'photo' => $photo];
    }
    return $out;
}
