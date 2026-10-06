<?php
declare(strict_types=1);

/**
 * Yönlendirme. Sayfa adları dile göre farklı slug alır (SEO), fakat
 * uygulama içinde her zaman kanonik ad kullanılır.
 */

const OB_SLUGS = [
    'about'       => ['tr' => 'hakkimizda',  'en' => 'about'],
    'gallery'     => ['tr' => 'galeri',      'en' => 'gallery'],
    'blog'        => ['tr' => 'blog',        'en' => 'blog'],
    'contact'     => ['tr' => 'iletisim',    'en' => 'contact'],
    'qrmenu'      => ['tr' => 'qr-menu',     'en' => 'qr-menu'],
    'menubook'    => ['tr' => 'menu',        'en' => 'menu'],
];

/** Kanonik ad → o dildeki yol. route_url('about') */
function route_url(string $name, string $suffix = '', ?string $loc = null): string
{
    $loc  = $loc ?: locale();
    $slug = OB_SLUGS[$name][$loc] ?? OB_SLUGS[$name]['tr'] ?? $name;
    return url($slug . ($suffix !== '' ? '/' . trim($suffix, '/') : ''), $loc);
}

/** O dildeki slug → kanonik ad */
function route_name(string $slug, string $loc): ?string
{
    foreach (OB_SLUGS as $name => $map) {
        if (($map[$loc] ?? null) === $slug) { return $name; }
    }
    return null;
}

/** Aynı sayfanın başka dildeki karşılığı (dil değiştirici için) */
function alt_lang_url(string $loc): string
{
    $name   = $GLOBALS['OB_ROUTE'] ?? null;
    $suffix = $GLOBALS['OB_ROUTE_SUFFIX'] ?? '';
    if ($name === null) { return url('', $loc); }
    return route_url($name, $suffix, $loc);
}

function ob_route(string $path): void
{
    $segments = $path === '' ? [] : explode('/', $path);
    $first    = $segments[0] ?? '';

    /* ---- Yönetim paneli ------------------------------------------------- */
    if ($first === 'admin') {
        $GLOBALS['OB_LOCALE'] = cfg('default_locale');
        require OB_APP . '/admin.php';
        ob_admin_route(array_slice($segments, 1));
        return;
    }

    /* ---- QR kısayolu: /qr/{slug} ---------------------------------------- */
    if ($first === 'qr') {
        ob_qr_hit($segments[1] ?? 'menu');
        return;
    }

    /* ---- robots / sitemap ------------------------------------------------ */
    if ($first === 'robots.txt')  { ob_robots();  return; }
    if ($first === 'sitemap.xml') { ob_sitemap(); return; }

    /* Tarayıcıların kendiliğinden istediği kök dosyalar. Dil yönlendirmesine
       girerlerse /tr/favicon.ico'ya düşüp her sayfa açılışında konsola hata
       yazdırıyorlar; burada doğrudan kapatılıyor. Simge zaten SVG olarak
       <head>'de bildiriliyor. */
    if (in_array($first, ['favicon.ico', 'apple-touch-icon.png', 'apple-touch-icon-precomposed.png',
                          'site.webmanifest', 'browserconfig.xml'], true)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('');
    }

    /* ---- Dil ------------------------------------------------------------- */
    if (!array_key_exists($first, locales())) {
        redirect(url(ltrim($path, '/'), ob_preferred_locale()));
    }
    $GLOBALS['OB_LOCALE'] = $first;
    $loc  = $first;
    $rest = array_slice($segments, 1);

    require OB_APP . '/site.php';

    if ($rest === []) { $GLOBALS['OB_ROUTE'] = null; page_home(); return; }

    $name = route_name($rest[0], $loc);
    if ($name === null) { abort404(); }

    $arg = $rest[1] ?? null;
    $GLOBALS['OB_ROUTE']        = $name;
    $GLOBALS['OB_ROUTE_SUFFIX'] = $arg ?? '';

    match ($name) {
        'about'       => page_about(),
        'gallery'     => page_gallery(),
        'blog'        => $arg === null ? page_blog() : page_post($arg),
        'contact'     => page_contact(),
        'qrmenu'      => page_qrmenu(),
        'menubook'    => page_menubook(),
        default       => abort404(),
    };
}

/** Tarayıcının dil tercihini sitenin dillerine eşler. */
function ob_preferred_locale(): string
{
    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    foreach (explode(',', $accept) as $part) {
        $code = strtolower(substr(trim(explode(';', $part)[0]), 0, 2));
        if (array_key_exists($code, locales())) { return $code; }
    }
    return cfg('default_locale', 'tr');
}

/** QR kodu tarandı: sayacı artır, QR menüye yönlendir. */
function ob_qr_hit(string $slug): never
{
    $qr = DB::one('SELECT * FROM qr_codes WHERE slug = ? AND is_active = 1', [$slug]);
    if ($qr) {
        DB::run('UPDATE qr_codes SET scans = scans + 1, last_scan_at = ? WHERE id = ?',
                [DB::now(), $qr['id']]);
    }
    $loc = ob_preferred_locale();

    if ($qr && $qr['kind'] === 'url' && $qr['target']) {
        redirect($qr['target']);
    }
    if ($qr && $qr['kind'] === 'category' && $qr['target']) {
        redirect(route_url('qrmenu', '', $loc) . '#kat-' . rawurlencode($qr['target']));
    }
    redirect(route_url('qrmenu', '', $loc));
}

function ob_robots(): never
{
    header('Content-Type: text/plain; charset=utf-8');
    $host = ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';
    $host .= '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    echo "User-agent: *\nDisallow: /admin\nDisallow: /uploads/tmp\n\nSitemap: $host" . base() . "/sitemap.xml\n";
    exit;
}

function ob_sitemap(): never
{
    header('Content-Type: application/xml; charset=utf-8');
    $scheme = ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';
    $host   = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

    $urls = [];
    foreach (array_keys(locales()) as $loc) {
        $urls[] = [url('', $loc), '1.0'];
        foreach (array_filter(['menubook', 'qrmenu', 'about', 'gallery', 'blog', 'contact'],
                              fn($n) => $n !== 'blog' || ob_has_posts()) as $n) {
            $urls[] = [route_url($n, '', $loc), '0.8'];
        }
        foreach (DB::all('SELECT slug FROM posts WHERE is_published = 1') as $p) {
            $urls[] = [route_url('blog', $p['slug'], $loc), '0.6'];
        }
    }

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    /* lastmod arama motoruna neyin yenilendiğini söyler. Blog yazısında
       yayın tarihi, diğer sayfalarda içeriğin son değişme tarihi. */
    $sonDegisim = date('Y-m-d', max(
        (int) strtotime((string) DB::value('SELECT MAX(published_at) FROM posts', [], '')),
        (int) @filemtime(OB_ROOT . '/app/seed_data.php')
    ));
    foreach ($urls as $satir) {
        [$u, $prio] = $satir;
        $lm = $satir[2] ?? $sonDegisim;
        echo "  <url><loc>" . e($host . $u) . "</loc>"
           . "<lastmod>" . e($lm) . "</lastmod>"
           . "<priority>$prio</priority></url>
";
    }
    echo '</urlset>';
    exit;
}
