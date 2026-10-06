<?php
declare(strict_types=1);

/* --------------------------------------------------------------------------
 *  Old Babyloon — ortak yardımcılar
 * ----------------------------------------------------------------------- */

function cfg(?string $key = null, mixed $default = null): mixed
{
    static $config;
    if ($config === null) { $config = $GLOBALS['OB_CONFIG'] ?? []; }
    if ($key === null) { return $config; }
    return $config[$key] ?? $default;
}

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---- Dil ---------------------------------------------------------------- */

function locale(): string
{
    return $GLOBALS['OB_LOCALE'] ?? cfg('default_locale', 'tr');
}

function locales(): array { return cfg('locales', ['tr' => 'Türkçe']); }

/** Sözlükten çeviri: t('nav.menu') */
function t(string $key, array $vars = []): string
{
    static $dict = [];
    $loc = locale();
    if (!isset($dict[$loc])) {
        $file = OB_APP . "/lang/$loc.php";
        $dict[$loc] = is_file($file) ? require $file : [];
    }
    $fallback = cfg('default_locale', 'tr');
    if (!isset($dict[$fallback])) {
        $file = OB_APP . "/lang/$fallback.php";
        $dict[$fallback] = is_file($file) ? require $file : [];
    }
    $out = $dict[$loc][$key] ?? ($dict[$fallback][$key] ?? $key);
    foreach ($vars as $k => $v) { $out = str_replace('{' . $k . '}', (string) $v, $out); }
    return $out;
}

/** Satırdaki çok dilli sütunu seçer: tr_col($row, 'name') → name_tr / name_en */
function tr_col(array $row, string $col, ?string $loc = null): string
{
    $loc = $loc ?: locale();
    $v = $row[$col . '_' . $loc] ?? '';
    if ($v === '' || $v === null) { $v = $row[$col . '_' . cfg('default_locale')] ?? ''; }
    return (string) $v;
}

/* ---- URL ---------------------------------------------------------------- */

function base(): string { return rtrim((string) cfg('base_url', ''), '/'); }

/** url('menu') → /tr/menu ; url('menu', 'en') → /en/menu */
function url(string $path = '', ?string $loc = null): string
{
    $loc  = $loc ?: locale();
    $path = trim($path, '/');
    return base() . '/' . $loc . ($path !== '' ? '/' . $path : '');
}

/** Dile bağlı olmayan varlık yolu (cache-busting damgalı) */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $full = OB_ROOT . '/' . $path;
    $v = is_file($full) ? '?v=' . substr((string) filemtime($full), -6) : '';
    return base() . '/' . $path . $v;
}

function admin_url(string $path = ''): string
{
    return base() . '/admin' . ($path !== '' ? '/' . trim($path, '/') : '');
}

function redirect(string $to): never
{
    header('Location: ' . $to, true, 302);
    exit;
}

function current_path(): string
{
    return $GLOBALS['OB_PATH'] ?? '';
}

/* ---- Ayarlar ------------------------------------------------------------ */

function settings(bool $reload = false): array
{
    static $cache;
    if ($cache === null || $reload) {
        $cache = [];
        foreach (DB::all('SELECT k, v FROM settings') as $r) { $cache[$r['k']] = $r['v']; }
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $s = settings();
    $v = $s[$key] ?? '';
    return $v === '' ? $default : (string) $v;
}

/** Çok dilli ayar: setting_t('hero_title') → hero_title_tr */
function setting_t(string $key, string $default = ''): string
{
    $v = setting($key . '_' . locale());
    if ($v === '') { $v = setting($key . '_' . cfg('default_locale')); }
    return $v === '' ? $default : $v;
}

function setting_put(string $key, string $value): void
{
    $exists = DB::value('SELECT 1 FROM settings WHERE k = ?', [$key]);
    if ($exists) { DB::run('UPDATE settings SET v = ? WHERE k = ?', [$value, $key]); }
    else { DB::run('INSERT INTO settings (k, v) VALUES (?, ?)', [$key, $value]); }
    // Önbellek tazelenmezse aynı istekte okuyan kod eski değeri görür
    settings(true);
}

/* ---- Görsel / para / metin ---------------------------------------------- */

/** Yüklenmiş görselin URL'i; yoksa yer tutucu SVG */
function img(?string $path, string $placeholder = 'assets/img/placeholder-cup.svg'): string
{
    if ($path && is_file(OB_ROOT . '/' . ltrim($path, '/'))) { return asset($path); }
    return asset($placeholder);
}

function has_img(?string $path): bool
{
    return $path !== null && $path !== '' && is_file(OB_ROOT . '/' . ltrim($path, '/'));
}

function money(float|int|string|null $amount): string
{
    $cur = setting('currency', '₺');
    $n   = number_format((float) $amount, 2, ',', '.');
    if (str_ends_with($n, ',00')) { $n = substr($n, 0, -3); }
    return setting('currency_position', 'after') === 'before' ? $cur . ' ' . $n : $n . ' ' . $cur;
}

/** İki boyu tek para birimiyle yazar: "140 / 160 ₺" — dar ekranlarda taşmaz. */
function money_pair(float|int|string $a, float|int|string $b): string
{
    $fmt = function ($v): string {
        $n = number_format((float) $v, 2, ",", ".");
        return str_ends_with($n, ",00") ? substr($n, 0, -3) : $n;
    };
    $cur = setting("currency", "₺");
    return setting("currency_position", "after") === "before"
        ? $cur . " " . $fmt($a) . " / " . $fmt($b)
        : $fmt($a) . " / " . $fmt($b) . " " . $cur;
}

/** Çok büyük görselleri makul boyuta indirir (GD varsa). */
function img_shrink(string $path, int $imageType, int $maxSide): void
{
    if (!function_exists('imagecreatetruecolor')) { return; }
    $info = @getimagesize($path);
    if (!$info) { return; }
    [$w, $h] = $info;
    if (max($w, $h) <= $maxSide) { return; }

    $src = match ($imageType) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
        IMAGETYPE_PNG  => @imagecreatefrompng($path),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        IMAGETYPE_GIF  => @imagecreatefromgif($path),
        default        => false,
    };
    if (!$src) { return; }

    $scale = $maxSide / max($w, $h);
    $nw = (int) round($w * $scale);
    $nh = (int) round($h * $scale);
    $dst = imagecreatetruecolor($nw, $nh);

    if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    match ($imageType) {
        IMAGETYPE_PNG  => imagepng($dst, $path, 8),
        IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($dst, $path, 82) : imagejpeg($dst, $path, 84),
        IMAGETYPE_GIF  => imagegif($dst, $path),
        default        => imagejpeg($dst, $path, 84),
    };
}

/** Fiyatı girilmemiş ürünler için: rakam yerine ince çizgi. */
function money_or_dash(float|int|string|null $amount): string
{
    return ((float) $amount) > 0 ? money($amount) : '—';
}

function slugify(string $text): string
{
    $map = ['ç'=>'c','Ç'=>'c','ğ'=>'g','Ğ'=>'g','ı'=>'i','İ'=>'i','ö'=>'o','Ö'=>'o',
            'ş'=>'s','Ş'=>'s','ü'=>'u','Ü'=>'u','â'=>'a','î'=>'i','û'=>'u'];
    $text = strtr($text, $map);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('~[^a-z0-9]+~u', '-', $text) ?? '';
    return trim($text, '-') ?: 'kayit';
}

function excerpt(string $html, int $len = 140): string
{
    // Paragraflar arasına boşluk koy, varlıkları çöz (&amp; → &) — çıktı yine e() ile kaçırılır
    $plain = strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], ' ', $html));
    $txt = trim(preg_replace('~\s+~u', ' ', html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    return mb_strlen($txt) > $len ? mb_substr($txt, 0, $len - 1) . '…' : $txt;
}

/** Yönetici içeriğini güvenli bir HTML alt kümesine indirger. */
function safe_html(string $html): string
{
    return strip_tags($html, '<p><br><strong><b><em><i><ul><ol><li><a><h2><h3><h4><blockquote>');
}

/* ---- Oturum / CSRF ------------------------------------------------------ */

function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) { return; }
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
    ]);
    session_name('oldbabyloon');
    session_start();
}

function csrf_token(): string
{
    session_boot();
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    session_boot();
    $sent = $_POST['_token'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.');
    }
}

function flash(?string $msg = null, string $type = 'ok'): ?array
{
    session_boot();
    if ($msg !== null) { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function old(string $key, mixed $default = ''): mixed
{
    session_boot();
    return $_SESSION['old'][$key] ?? $default;
}

function keep_old(array $data): void
{
    session_boot();
    unset($data['_token'], $data['password']);
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    session_boot();
    unset($_SESSION['old']);
}

/* ---- Yönetici kimliği --------------------------------------------------- */

function auth(): ?array
{
    session_boot();
    static $user = false;
    if ($user !== false) { return $user; }
    $id = $_SESSION['admin_id'] ?? null;
    $user = $id ? DB::one('SELECT * FROM admin_users WHERE id = ?', [(int) $id]) : null;
    return $user;
}

function require_auth(): array
{
    $u = auth();
    if (!$u) { redirect(admin_url('giris')); }
    return $u;
}

function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

function input(string $key, mixed $default = ''): mixed
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function input_int(string $key, int $default = 0): int
{
    return (int) input($key, $default);
}

function input_float(string $key, float $default = 0.0): float
{
    $v = str_replace(',', '.', (string) input($key, $default));
    return (float) $v;
}

function input_bool(string $key): int
{
    return !empty($_POST[$key]) ? 1 : 0;
}

/* ---- İçerik yardımcıları (hem site hem panel kullanır) ------------------ */

function ob_hours(): array
{
    static $rows;
    if ($rows === null) {
        $rows = [];
        foreach (DB::all('SELECT * FROM hours ORDER BY day_no') as $r) { $rows[(int) $r['day_no']] = $r; }
    }
    return $rows;
}

/** Bugünün açık/kapalı durumu. ['open'=>bool,'row'=>?array] */
function ob_open_state(): array
{
    $today = (int) date('N'); // 1 = Pazartesi
    $row   = ob_hours()[$today] ?? null;
    if (!$row || $row['is_closed']) { return ['open' => false, 'row' => $row]; }

    $now   = date('H:i');
    $open  = (string) $row['open_time'];
    $close = (string) $row['close_time'];
    // Gece yarısını aşan kapanış (örn. 07:30 → 01:00)
    $isOpen = $close > $open ? ($now >= $open && $now < $close) : ($now >= $open || $now < $close);
    return ['open' => $isOpen, 'row' => $row];
}

function ob_categories(): array
{
    static $rows;
    $rows ??= DB::all('SELECT * FROM menu_categories WHERE is_active = 1 ORDER BY sort, id');
    return $rows;
}

/** Yayımlanmış blog yazısı var mı? Yoksa Blog bağlantıları gizlenir. */
function ob_has_posts(): bool
{
    static $has;
    $has ??= (int) DB::value('SELECT COUNT(*) FROM posts WHERE is_published = 1', [], 0) > 0;
    return $has;
}

function ob_badges(?string $csv): array
{
    if (!$csv) { return []; }
    return array_values(array_filter(array_map('trim', explode(',', $csv))));
}

function ob_date(string $sqlDate): string
{
    $ts = strtotime($sqlDate) ?: time();
    return date('j', $ts) . ' ' . t('month.' . (int) date('n', $ts)) . ' ' . date('Y', $ts);
}

/** Kanonik + hreflang etiketleri için mutlak URL */
function ob_abs(string $path): string
{
    $scheme = ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $path;
}

/* ---- Görünüm ------------------------------------------------------------ */

function view(string $tpl, array $data = []): string
{
    // extract() KULLANMA — şablon değişkenleriyle çakışır. Açık $data erişimi.
    $__file = OB_APP . '/views/' . $tpl . '.php';
    if (!is_file($__file)) { throw new RuntimeException("Şablon yok: $tpl"); }
    ob_start();
    (static function (string $__f, array $data): void { require $__f; })($__file, $data);
    return (string) ob_get_clean();
}

function render(string $tpl, array $data = [], string $layout = 'layout/site'): void
{
    $content = view($tpl, $data);
    echo view($layout, $data + ['content' => $content]);
}

function abort404(): never
{
    http_response_code(404);
    render('site/404', ['title' => t('err.404_title')]);
    exit;
}

/**
 * Duyarlı görsel etiketi.
 *
 * tools/responsive-images.php ile üretilmiş "@genişlik" türevleri varsa
 * <picture> döner (WebP + JPG, srcset ile); yoksa sade bir <img>.
 * Böylece panelden yeni yüklenen, henüz türevi olmayan görseller de
 * çalışmaya devam eder.
 *
 * @param array<string,string> $ozellik Ek HTML özellikleri (alt, class, loading…)
 */
function img_responsive(string $path, array $ozellik = [], string $sizes = '100vw'): string
{
    $rel  = ltrim(str_replace(chr(92), '/', $path), '/');
    $tam  = OB_ROOT . '/' . $rel;
    $taban = preg_replace('~\.(jpe?g|png)$~i', '', $rel);

    $nitelik = '';
    foreach ($ozellik as $k => $v) {
        if ($v === null || $v === false) { continue; }
        $nitelik .= ' ' . $k . ($v === true ? '' : '="' . e((string) $v) . '"');
    }

    /* Türevleri bul: uploads/slider/hero-1@700.jpg …
       Kaynak zaten küçükse genişlik türevi üretilmez ama WebP kopyası
       yine de vardır; o durumda da <picture> kurulmalı. */
    $turevler = glob(OB_ROOT . '/' . $taban . '@*.jpg') ?: [];
    $tamWebpVar = is_file(OB_ROOT . '/' . $taban . '.webp');
    if ((!$turevler && !$tamWebpVar) || !is_file($tam)) {
        return '<img src="' . e(asset($rel)) . '"' . $nitelik . '>';
    }

    $boy = @getimagesize($tam);
    $jpg = []; $webp = [];
    foreach ($turevler as $f) {
        if (!preg_match('~@(\d+)\.jpg$~', $f, $m)) { continue; }
        $w = (int) $m[1];
        $jpg[$w] = asset($taban . '@' . $w . '.jpg') . ' ' . $w . 'w';
        $wp = OB_ROOT . '/' . $taban . '@' . $w . '.webp';
        if (is_file($wp)) { $webp[$w] = asset($taban . '@' . $w . '.webp') . ' ' . $w . 'w'; }
    }
    // En büyük boy olarak kaynağın kendisi
    if ($boy) {
        $jpg[$boy[0]] = asset($rel) . ' ' . $boy[0] . 'w';
        $tamWebp = OB_ROOT . '/' . $taban . '.webp';
        if (is_file($tamWebp)) { $webp[$boy[0]] = asset($taban . '.webp') . ' ' . $boy[0] . 'w'; }
    }
    ksort($jpg); ksort($webp);

    // Boyut bilgisi yerleşim kaymasını (CLS) önler
    if ($boy) { $nitelik = ' width="' . $boy[0] . '" height="' . $boy[1] . '"' . $nitelik; }

    /* Ertelenen görsel: adresler data- ile yazılır, motion.js sayfa
       yüklendikten sonra gerçek adreslere çevirir. Hero'nun görünmeyen
       slaytları için: üçü birden inince ilk yükleme 155 KB şişiyordu.
       JS kapalıyken o slaytlar zaten gizli, kayıp yok. */
    $ertele = !empty($ozellik['data-defer']);
    $sA = $ertele ? 'data-srcset' : 'srcset';
    $iA = $ertele ? 'data-src'    : 'src';

    $html = '<picture>';
    if ($webp) { $html .= '<source type="image/webp" ' . $sA . '="' . e(implode(', ', $webp)) . '" sizes="' . e($sizes) . '">'; }
    $html .= '<img ' . $iA . '="' . e(asset($rel)) . '" ' . $sA . '="' . e(implode(', ', $jpg)) . '" sizes="' . e($sizes) . '"' . $nitelik . '>';
    return $html . '</picture>';
}

/**
 * LCP görseli için önyükleme etiketi.
 *
 * Tarayıcı hero fotoğrafını ancak CSS'i ayrıştırdıktan sonra keşfediyor.
 * Burada baştan bildirilince istek erken başlar. srcset ile aynı kural
 * kullanılır ki iki farklı dosya inmesin.
 */
function img_preload_tag(string $path, string $sizes = '100vw'): string
{
    $rel   = ltrim(str_replace(chr(92), '/', $path), '/');
    $taban = preg_replace('~\.(jpe?g|png)$~i', '', $rel);
    if (!is_file(OB_ROOT . '/' . $rel)) { return ''; }

    $webp = [];
    foreach (glob(OB_ROOT . '/' . $taban . '@*.webp') ?: [] as $f) {
        if (preg_match('~@(\d+)\.webp$~', $f, $m)) {
            $webp[(int) $m[1]] = asset($taban . '@' . $m[1] . '.webp') . ' ' . $m[1] . 'w';
        }
    }
    if (is_file(OB_ROOT . '/' . $taban . '.webp')) {
        $boy = @getimagesize(OB_ROOT . '/' . $rel);
        if ($boy) { $webp[$boy[0]] = asset($taban . '.webp') . ' ' . $boy[0] . 'w'; }
    }
    if (!$webp) {
        return '<link rel="preload" as="image" href="' . e(asset($rel)) . '" fetchpriority="high">';
    }
    ksort($webp);
    return '<link rel="preload" as="image" type="image/webp"'
         . ' imagesrcset="' . e(implode(', ', $webp)) . '"'
         . ' imagesizes="' . e($sizes) . '" fetchpriority="high">';
}
