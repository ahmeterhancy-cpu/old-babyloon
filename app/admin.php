<?php
declare(strict_types=1);

/* ==========================================================================
 *  Old Babyloon — yönetim paneli
 *
 *  Kaynakların çoğu aynı işi yapar (listele / ekle / düzenle / sil / sırala),
 *  bu yüzden tek bir tanım tablosu + genel CRUD motoru kullanılıyor.
 *  Özel ekranlar (panel, saatler, mesajlar, QR, Instagram, ayarlar)
 *  ayrıca ele alınır.
 * ======================================================================= */

require OB_APP . '/admin_fields.php';
require OB_APP . '/lib/qrcode.php';
require OB_APP . '/lib/instagram.php';
require OB_APP . '/lib/pdfpages.php';
require OB_APP . '/lib/imagevariants.php';

/** Panelde solda görünen menü */
function admin_nav(): array
{
    return [
        ['', 'Panel', 'clock'],
        ['---', 'İçerik', ''],
        ['kaynak/slides', 'Ana Sayfa Slaytları', 'plate'],
        ['kaynak/menu_categories', 'Menü Kategorileri', 'leaf'],
        ['kaynak/menu_items', 'Menü Ürünleri', 'cup'],
        ['kitapcik', 'Dijital Menü', 'dripper'],
        ['kaynak/gallery', 'Galeri', 'pin'],
        ['kaynak/posts', 'Blog', 'dripper'],
        ['kaynak/features', 'Öne Çıkanlar', 'bean'],
        ['kaynak/testimonials', 'Müşteri Yorumları', 'quote'],
        ['kaynak/team', 'Ekip', 'roaster'],
        ['---', 'İşletme', ''],
        ['saatler', 'Çalışma Saatleri', 'clock'],
        ['mesajlar', 'Mesajlar', 'mail'],
        ['qr', 'QR Menü Kodu', 'search'],
        ['instagram', 'Instagram Akışı', 'instagram'],
        ['---', 'Kurulum', ''],
        ['ayarlar', 'Site Ayarları', 'cake'],
        ['kullanicilar', 'Kullanıcılar', 'roaster'],
    ];
}

function ob_admin_route(array $seg): void
{
    $page = $seg[0] ?? '';

    // Giriş / çıkış oturum gerektirmez
    if ($page === 'giris')  { admin_login();  return; }
    if ($page === 'cikis')  { admin_logout(); return; }

    require_auth();

    match ($page) {
        ''               => admin_dashboard(),
        'kaynak'         => admin_resource(array_slice($seg, 1)),
        'saatler'        => admin_hours(),
        'mesajlar'       => admin_messages(array_slice($seg, 1)),
        'kitapcik'       => admin_menubook(array_slice($seg, 1)),
        'qr'             => admin_qr(array_slice($seg, 1)),
        'instagram'      => admin_instagram(array_slice($seg, 1)),
        'ayarlar'        => admin_settings(),
        'kullanicilar'   => admin_users(array_slice($seg, 1)),
        default          => admin_abort404(),
    };
}

function admin_abort404(): never
{
    http_response_code(404);
    admin_render('admin/404', ['title' => 'Sayfa yok']);
    exit;
}

function admin_render(string $tpl, array $data = []): void
{
    echo view('layout/admin', $data + ['content' => view($tpl, $data)]);
}

/* --------------------------------------------------------------------------
 *  Giriş
 * ----------------------------------------------------------------------- */

function admin_login(): void
{
    session_boot();
    if (auth()) { redirect(admin_url()); }

    $error = '';
    if (is_post()) {
        csrf_check();
        $u = (string) input('username');
        $p = (string) input('password');

        // Kaba kuvvet frenleme: aynı oturumda 5 denemeden sonra 30 sn bekleme
        $tries = (int) ($_SESSION['login_tries'] ?? 0);
        $last  = (int) ($_SESSION['login_last'] ?? 0);
        if ($tries >= 5 && time() - $last < 30) {
            $error = 'Çok fazla deneme. 30 saniye bekleyin.';
        } else {
            $user = DB::one('SELECT * FROM admin_users WHERE username = ? AND is_active = 1', [$u]);
            if ($user && password_verify($p, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $user['id'];
                unset($_SESSION['login_tries'], $_SESSION['login_last']);
                DB::run('UPDATE admin_users SET last_login_at = ? WHERE id = ?', [DB::now(), $user['id']]);
                redirect(admin_url());
            }
            $_SESSION['login_tries'] = $tries + 1;
            $_SESSION['login_last']  = time();
            $error = 'Kullanıcı adı veya şifre hatalı.';
        }
    }

    echo view('admin/login', ['error' => $error, 'title' => 'Giriş — Old Babyloon']);
}

function admin_logout(): never
{
    session_boot();
    $_SESSION = [];
    session_destroy();
    redirect(admin_url('giris'));
}

/* --------------------------------------------------------------------------
 *  Panel
 * ----------------------------------------------------------------------- */

function admin_dashboard(): void
{
    $stats = [
        'unread'   => (int) DB::value('SELECT COUNT(*) FROM messages WHERE is_read = 0', [], 0),
        'items'    => (int) DB::value('SELECT COUNT(*) FROM menu_items', [], 0),
        'outstock' => (int) DB::value('SELECT COUNT(*) FROM menu_items WHERE is_available = 0', [], 0),
        'scans'    => (int) DB::value('SELECT COALESCE(SUM(scans), 0) FROM qr_codes', [], 0),
        'photos'   => (int) DB::value('SELECT COUNT(*) FROM gallery', [], 0),
    ];

    admin_render('admin/dashboard', [
        'title' => 'Panel',
        'stats' => $stats,
        'messages'     => DB::all('SELECT * FROM messages ORDER BY created_at DESC LIMIT 5'),
        'lowItems'     => DB::all('SELECT i.*, c.name_tr AS cat FROM menu_items i
                                     JOIN menu_categories c ON c.id = i.category_id
                                    WHERE i.is_available = 0 ORDER BY c.sort, i.sort LIMIT 8'),
    ]);
}

/* --------------------------------------------------------------------------
 *  Genel CRUD
 * ----------------------------------------------------------------------- */

function admin_resource(array $seg): void
{
    $key = $seg[0] ?? '';
    $defs = admin_resources();
    if (!isset($defs[$key])) { admin_abort404(); }
    $res = $defs[$key];
    $action = $seg[1] ?? 'liste';

    match ($action) {
        'liste' => admin_res_list($key, $res),
        'yeni'  => admin_res_form($key, $res, null),
        'duzenle' => admin_res_form($key, $res, (int) ($seg[2] ?? 0)),
        'sil'   => admin_res_delete($key, $res, (int) ($seg[2] ?? 0)),
        'sirala' => admin_res_sort($key, $res),
        'toplu-sil' => admin_res_bulk_delete($key, $res),
        'toplu-yukle' => isset($res['bulk_upload']) ? admin_res_bulk_upload($key, $res) : admin_abort404(),
        default => admin_abort404(),
    };
}

function admin_res_list(string $key, array $res): void
{
    $order = $res['order'] ?? 'id DESC';
    $rows  = DB::all("SELECT * FROM {$res['table']} ORDER BY $order");

    // İlişkili etiketler (ör. ürünün kategorisi)
    $lookups = [];
    foreach ($res['fields'] as $name => $f) {
        if (($f['type'] ?? '') === 'select' && isset($f['options'])) {
            $lookups[$name] = ($f['options'])();
        }
    }

    admin_render('admin/list', [
        'title'   => $res['title'],
        'resKey'  => $key,
        'res'     => $res,
        'rows'    => $rows,
        'lookups' => $lookups,
    ]);
}

function admin_res_form(string $key, array $res, ?int $id): void
{
    $row = $id ? DB::one("SELECT * FROM {$res['table']} WHERE id = ?", [$id]) : null;
    if ($id && !$row) { admin_abort404(); }
    $errors = [];

    if (is_post()) {
        csrf_check();
        [$data, $errors] = admin_collect($res['fields'], $row);

        if (!$errors) {
            if (isset($res['before_save'])) { $data = ($res['before_save'])($data, $row); }
            if ($row) {
                DB::update($res['table'], $data, 'id = :__id', ['__id' => $id]);
                flash('Kayıt güncellendi.');
            } else {
                if (array_key_exists('sort', $data) && $data['sort'] === 0) {
                    $data['sort'] = 1 + (int) DB::value("SELECT COALESCE(MAX(sort), 0) FROM {$res['table']}", [], 0);
                }
                $id = DB::insert($res['table'], $data);
                flash('Kayıt eklendi.');
            }
            redirect(admin_url("kaynak/$key/liste"));
        }
    }

    $lookups = [];
    foreach ($res['fields'] as $name => $f) {
        if (($f['type'] ?? '') === 'select' && isset($f['options'])) {
            $lookups[$name] = ($f['options'])();
        }
    }

    admin_render('admin/form', [
        'title'   => ($id ? 'Düzenle' : 'Yeni') . ' — ' . $res['title'],
        'resKey'  => $key,
        'res'     => $res,
        'row'     => $row,
        'errors'  => $errors,
        'lookups' => $lookups,
    ]);
}

function admin_res_delete(string $key, array $res, int $id): void
{
    if (!is_post()) { admin_abort404(); }
    csrf_check();
    $row = DB::one("SELECT * FROM {$res['table']} WHERE id = ?", [$id]);
    if ($row) {
        // Yüklenmiş görselleri de temizle
        foreach ($res['fields'] as $name => $f) {
            if (($f['type'] ?? '') === 'image' && !empty($row[$name])) { admin_delete_upload((string) $row[$name]); }
        }
        DB::delete($res['table'], 'id = ?', [$id]);
        flash('Kayıt silindi.');
    }
    redirect(admin_url("kaynak/$key/liste"));
}

/**
 * Seçilen kayıtları toplu siler.
 *
 * Tekli silmeyle aynı işi yapar; tek fark, yüklenmiş dosyaların da
 * her kayıt için tek tek temizlenmesidir. Silinemeyen bir kimlik
 * (başkası tarafından zaten silinmiş olabilir) sessizce atlanır.
 */
function admin_res_bulk_delete(string $key, array $res): void
{
    if (!is_post()) { admin_abort404(); }
    csrf_check();

    // Onay alanı yalnızca gerçek silme formundan gelir. Kazara ya da
    // tekrar gönderilmiş bir istek buradan geçemez.
    if (($_POST['onay'] ?? '') !== '1') {
        flash('Silme isteği doğrulanamadı.', 'err');
        redirect(admin_url("kaynak/$key/liste"));
    }

    $ids = $_POST['ids'] ?? [];
    $ids = is_array($ids) ? array_values(array_unique(array_filter(array_map('intval', $ids)))) : [];

    if (!$ids) {
        flash('Silinecek kayıt seçilmedi.', 'err');
        redirect(admin_url("kaynak/$key/liste"));
    }

    $silinen = 0;
    foreach ($ids as $id) {
        $row = DB::one("SELECT * FROM {$res['table']} WHERE id = ?", [$id]);
        if (!$row) { continue; }
        foreach ($res['fields'] as $name => $f) {
            if (($f['type'] ?? '') === 'image' && !empty($row[$name])) {
                admin_delete_upload((string) $row[$name]);
            }
        }
        DB::delete($res['table'], 'id = ?', [$id]);
        $silinen++;
    }

    flash($silinen . ' kayıt silindi.');
    redirect(admin_url("kaynak/$key/liste"));
}

/**
 * Birden çok fotoğrafı tek seferde ekler (galeri).
 *
 * Sunucunun POST boyutu ve dosya sayısı sınırına takılmamak için panel JS'i
 * dosyaları TEK TEK gönderir (?json=1) ve ilerlemeyi gösterir. JS kapalıysa
 * aynı form hepsini birden gönderir; sınırı aşan kısım hata olarak bildirilir.
 * Her dosya ayrı kayıt olur, sonuna eklenir, yayında açılır; açıklama boş kalır.
 */
function admin_res_bulk_upload(string $key, array $res): void
{
    $field  = (string) $res['bulk_upload'];
    $folder = (string) ($res['fields'][$field]['folder'] ?? $key);
    $json   = isset($_GET['json']);

    if (!is_post()) {
        admin_render('admin/bulk_upload', [
            'title'  => 'Toplu yükle — ' . $res['title'],
            'resKey' => $key,
            'res'    => $res,
            'limits' => [
                'file'  => min((int) cfg('upload_max', 6 * 1024 * 1024), bytes_ini((string) ini_get('upload_max_filesize'))),
                'count' => (int) ini_get('max_file_uploads') ?: 20,
            ],
        ]);
        return;
    }

    // POST sınırı aşılınca PHP $_POST'u da boşaltır; csrf_check o zaman anlamsız bir hata verir
    if (!$_POST && !$_FILES && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $msg = 'Gönderilen dosyalar sunucu sınırını aştı. Daha az fotoğrafla tekrar deneyin.';
        if ($json) { http_response_code(413); header('Content-Type: application/json'); exit(json_encode(['ok' => false, 'errors' => [$msg]], JSON_UNESCAPED_UNICODE)); }
        flash($msg, 'err');
        redirect(admin_url("kaynak/$key/toplu-yukle"));
    }
    csrf_check();

    // images[] dizisini tek tek dosya kaydına çevir; admin_handle_upload tek dosya bekler
    $files = [];
    $in = $_FILES['images'] ?? null;
    if ($in && is_array($in['name'])) {
        foreach ($in['name'] as $i => $n) {
            $files[] = ['name' => $n, 'type' => $in['type'][$i], 'tmp_name' => $in['tmp_name'][$i],
                        'error' => $in['error'][$i], 'size' => $in['size'][$i]];
        }
    }

    $sort = (int) DB::value("SELECT COALESCE(MAX(sort), 0) FROM {$res['table']}", [], 0);
    $added = 0; $errors = [];
    foreach ($files as $f) {
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { continue; }
        $_FILES['__toplu'] = $f;
        $err = null;
        $path = admin_handle_upload('__toplu', $folder, $err);
        if (!$path) { $errors[] = $f['name'] . ': ' . ($err ?? 'yüklenemedi'); continue; }
        DB::insert($res['table'], [$field => $path, 'is_active' => 1, 'sort' => ++$sort]);
        $added++;
    }
    unset($_FILES['__toplu']);

    if ($json) {
        header('Content-Type: application/json');
        exit(json_encode(['ok' => $added > 0, 'added' => $added, 'errors' => $errors], JSON_UNESCAPED_UNICODE));
    }
    if ($added === 0 && !$errors) { $errors[] = 'Fotoğraf seçilmedi.'; }
    flash($added . ' fotoğraf eklendi.' . ($errors ? ' Eklenemeyen: ' . implode(' · ', $errors) : ''), $errors && !$added ? 'err' : 'ok');
    redirect(admin_url($added ? "kaynak/$key/liste" : "kaynak/$key/toplu-yukle"));
}

/** Sürükleyip bırakma yerine: liste ekranındaki sıra numaralarını topluca kaydeder. */
function admin_res_sort(string $key, array $res): void
{
    if (!is_post()) { admin_abort404(); }
    csrf_check();
    $sorts = $_POST['sort'] ?? [];
    if (is_array($sorts)) {
        foreach ($sorts as $id => $val) {
            DB::run("UPDATE {$res['table']} SET sort = ? WHERE id = ?", [(int) $val, (int) $id]);
        }
        flash('Sıralama kaydedildi.');
    }
    redirect(admin_url("kaynak/$key/liste"));
}

/* --------------------------------------------------------------------------
 *  Form verisini toplama + doğrulama
 * ----------------------------------------------------------------------- */

/** @return array{0: array<string,mixed>, 1: array<string,string>} */
function admin_collect(array $fields, ?array $row): array
{
    $data = [];
    $errors = [];

    foreach ($fields as $name => $f) {
        $type = $f['type'] ?? 'text';

        switch ($type) {
            case 'i18n':
            case 'i18n_area':
            case 'i18n_html':
                foreach (array_keys(locales()) as $loc) {
                    $col = $name . '_' . $loc;
                    $val = trim((string) ($_POST[$col] ?? ''));
                    if ($type === 'i18n_html') { $val = safe_html($val); }
                    if (!empty($f['required']) && $loc === cfg('default_locale') && $val === '') {
                        $errors[$col] = 'Bu alan zorunlu.';
                    }
                    $data[$col] = $val;
                }
                break;

            case 'checkbox':
                $data[$name] = input_bool($name);
                break;

            case 'number':
                $data[$name] = input_int($name, (int) ($f['default'] ?? 0));
                break;

            case 'price':
                $raw = trim((string) ($_POST[$name] ?? ''));
                if ($raw === '' && !empty($f['nullable'])) { $data[$name] = null; }
                else { $data[$name] = round(input_float($name), 2); }
                break;

            case 'image':
                $current = $row[$name] ?? null;
                if (!empty($_POST['__remove_' . $name])) {
                    if ($current) { admin_delete_upload((string) $current); }
                    $data[$name] = null;
                    break;
                }
                $uploaded = admin_handle_upload($name, $f['folder'] ?? 'misc', $err);
                if ($err) { $errors[$name] = $err; }
                if ($uploaded) {
                    if ($current) { admin_delete_upload((string) $current); }
                    $data[$name] = $uploaded;
                } elseif ($row) {
                    $data[$name] = $current;   // dokunma
                } else {
                    $data[$name] = null;
                }
                break;

            case 'badges':
                $sel = $_POST[$name] ?? [];
                $sel = is_array($sel) ? array_values(array_filter(array_map('strval', $sel))) : [];
                $data[$name] = $sel ? implode(',', $sel) : null;
                break;

            case 'slug':
                $val = trim((string) ($_POST[$name] ?? ''));
                $src = $f['from'] ?? null;
                if ($val === '' && $src) { $val = (string) ($_POST[$src . '_' . cfg('default_locale')] ?? ''); }
                $data[$name] = slugify($val);
                break;

            case 'datetime':
                $val = trim((string) ($_POST[$name] ?? ''));
                $data[$name] = $val !== '' ? str_replace('T', ' ', $val) . ':00' : DB::now();
                break;

            default: // text, textarea, select, time, date, email, url
                $val = trim((string) ($_POST[$name] ?? ''));
                if (!empty($f['required']) && $val === '') { $errors[$name] = 'Bu alan zorunlu.'; }
                if ($val !== '' && ($f['type'] ?? '') === 'email' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    $errors[$name] = 'Geçerli bir e-posta girin.';
                }
                $data[$name] = ($val === '' && !empty($f['nullable'])) ? null : $val;
        }
    }

    return [$data, $errors];
}

/* --------------------------------------------------------------------------
 *  Görsel yükleme
 * ----------------------------------------------------------------------- */

function admin_handle_upload(string $field, string $folder, ?string &$error = null): ?string
{
    $error = null;
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Dosya yüklenemedi (kod ' . $file['error'] . ').';
        return null;
    }
    if ($file['size'] > cfg('upload_max', 6 * 1024 * 1024)) {
        $error = 'Dosya çok büyük (en fazla ' . round(((int) cfg('upload_max')) / 1048576) . ' MB).';
        return null;
    }

    $info = @getimagesize($file['tmp_name']);
    if (!$info) { $error = 'Bu bir görsel dosyası değil.'; return null; }

    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!isset($allowed[$info[2]])) { $error = 'Yalnızca JPG, PNG, WebP ve GIF yüklenebilir.'; return null; }

    $dir = OB_ROOT . '/uploads/' . trim($folder, '/');
    if (!is_dir($dir)) { mkdir($dir, 0775, true); }

    $ext  = $allowed[$info[2]];
    $name = date('Ymd') . '-' . bin2hex(random_bytes(5)) . '.' . $ext;
    $path = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        $error = 'Dosya kaydedilemedi. Klasör izinlerini kontrol edin.';
        return null;
    }

    img_shrink($path, $info[2], 2000);

    /* Duyarlı türevleri hemen üret: img_responsive() bunları bulunca
       <picture> kurar, bulamazsa sade <img>'e düşer. Yani üretim
       başarısız olsa bile yükleme başarılı sayılır. */
    image_variants_make($path);

    return 'uploads/' . trim($folder, '/') . '/' . $name;
}

/** Yalnızca uploads/ altındaki dosyaları siler — yol kaçışına izin vermez. */
function admin_delete_upload(string $rel): void
{
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if (!str_starts_with($rel, 'uploads/')) { return; }
    $full = realpath(OB_ROOT . '/' . $rel);
    $base = realpath(OB_ROOT . '/uploads');
    if ($full && $base && str_starts_with($full, $base) && is_file($full)) {
        // Önce türevler: dosya silinince desen kaybolur
        image_variants_remove($full);
        @unlink($full);
    }
}

/* --------------------------------------------------------------------------
 *  Çalışma saatleri
 * ----------------------------------------------------------------------- */

function admin_hours(): void
{
    if (is_post()) {
        csrf_check();
        for ($d = 1; $d <= 7; $d++) {
            DB::run('UPDATE hours SET open_time = ?, close_time = ?, is_closed = ?, note_tr = ?, note_en = ? WHERE day_no = ?', [
                (string) ($_POST['open'][$d] ?? '08:00'),
                (string) ($_POST['close'][$d] ?? '22:00'),
                empty($_POST['closed'][$d]) ? 0 : 1,
                trim((string) ($_POST['note_tr'][$d] ?? '')),
                trim((string) ($_POST['note_en'][$d] ?? '')),
                $d,
            ]);
        }
        flash('Çalışma saatleri kaydedildi.');
        redirect(admin_url('saatler'));
    }

    admin_render('admin/hours', ['title' => 'Çalışma Saatleri', 'hours' => ob_hours()]);
}

/* --------------------------------------------------------------------------
 *  Mesajlar
 * ----------------------------------------------------------------------- */

function admin_messages(array $seg): void
{
    $action = $seg[0] ?? '';

    if ($action === 'oku' && is_post()) {
        csrf_check();
        DB::run('UPDATE messages SET is_read = 1 WHERE id = ?', [(int) ($seg[1] ?? 0)]);
        redirect(admin_url('mesajlar'));
    }
    if ($action === 'sil' && is_post()) {
        csrf_check();
        DB::delete('messages', 'id = ?', [(int) ($seg[1] ?? 0)]);
        flash('Mesaj silindi.');
        redirect(admin_url('mesajlar'));
    }

    admin_render('admin/messages', [
        'title'    => 'Mesajlar',
        'messages' => DB::all('SELECT * FROM messages ORDER BY is_read, created_at DESC'),
    ]);
}

/* --------------------------------------------------------------------------
 *  Menü kitapçığı — PDF ve sayfaları
 *
 *  İşletme yeni bir menü bastırdığında tek yapması gereken PDF'i yüklemek.
 *  Sunucuda dönüştürücü varsa sayfa görselleri de PDF'ten üretilir; yoksa
 *  PDF yalnızca indirme bağlantısı olur ve sayfalar elle yüklenir.
 * ----------------------------------------------------------------------- */

/** Kitapçığın indirme PDF'i — ayardan, yoksa eski sabit dosyadan. */
function menubook_pdf(): ?string
{
    $rel = trim((string) setting('menu_pdf'));
    if ($rel !== '' && is_file(OB_ROOT . '/' . $rel)) { return $rel; }
    // Panel gelmeden önce elle konmuş dosya
    return is_file(OB_ROOT . '/uploads/menu-book/menu.pdf') ? 'uploads/menu-book/menu.pdf' : null;
}

function admin_menubook(array $seg): void
{
    $action = $seg[0] ?? '';
    $notice = null;   // dönüştürme sonucunu ekranda ayrıntılı göstermek için

    if (is_post()) {
        csrf_check();

        if ($action === 'pdf-sil') {
            $cur = menubook_pdf();
            if ($cur) { admin_delete_upload($cur); }
            setting_put('menu_pdf', '');
            flash('PDF kaldırıldı. Sitedeki indirme bağlantısı da kalktı.');
            redirect(admin_url('kitapcik'));
        }

        if ($action === 'yukle') {
            $err = null;
            $up = admin_handle_pdf_upload('pdf', $err);
            if ($err) {
                flash($err, 'err');
                redirect(admin_url('kitapcik'));
            }
            if ($up) {
                $old = menubook_pdf();
                if ($old && $old !== $up) { admin_delete_upload($old); }
                setting_put('menu_pdf', $up);
                flash('PDF yüklendi.');
            }
            // Sayfalar da istendiyse aynı istekte üret
            if (input_bool('regen')) {
                $_SESSION['book_regen'] = menubook_regenerate();
            }
            redirect(admin_url('kitapcik'));
        }

        if ($action === 'sayfalar') {
            $_SESSION['book_regen'] = menubook_regenerate();
            redirect(admin_url('kitapcik'));
        }

        admin_abort404();
    }

    // Bir önceki istekte üretim yapıldıysa sonucunu göster
    if (isset($_SESSION['book_regen'])) {
        $notice = $_SESSION['book_regen'];
        unset($_SESSION['book_regen']);
    }

    $pdf  = menubook_pdf();
    $full = $pdf ? OB_ROOT . '/' . $pdf : null;

    admin_render('admin/menubook', [
        'title'     => 'Menü Kitapçığı',
        'pages'     => DB::all('SELECT * FROM menu_pages ORDER BY sort, id'),
        'pdf'       => $pdf,
        'pdfSize'   => $full && is_file($full) ? filesize($full) : 0,
        'pdfDate'   => $full && is_file($full) ? filemtime($full) : 0,
        'converter' => PdfPages::converter(),
        'notice'    => $notice,
        'maxMb'     => round(menubook_max_bytes() / 1048576),
    ]);
}

/** PDF için izin verilen boyut — PHP'nin kendi sınırını aşamayız. */
function menubook_max_bytes(): int
{
    $want = (int) cfg('pdf_max', 25 * 1024 * 1024);
    $ini  = min(bytes_ini((string) ini_get('upload_max_filesize')), bytes_ini((string) ini_get('post_max_size')));
    return $ini > 0 ? min($want, $ini) : $want;
}

/** "8M" -> 8388608 */
function bytes_ini(string $v): int
{
    $v = trim($v);
    if ($v === '') { return 0; }
    $n = (int) $v;
    return match (strtolower(substr($v, -1))) {
        'g' => $n * 1024 * 1024 * 1024,
        'm' => $n * 1024 * 1024,
        'k' => $n * 1024,
        default => $n,
    };
}

/** PDF yükler; görsel yükleyicisinden ayrıdır çünkü doğrulama başkadır. */
function admin_handle_pdf_upload(string $field, ?string &$error = null): ?string
{
    $error = null;
    $file = $_FILES[$field] ?? null;

    // POST tamamen boşsa istek PHP'nin post_max_size sınırını aşmış demektir
    if (!$file && !$_POST) {
        $error = 'Dosya sunucu sınırını aştı (post_max_size). Daha küçük bir PDF deneyin.';
        return null;
    }
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return null; }

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $error = 'Dosya çok büyük (en fazla ' . round(menubook_max_bytes() / 1048576) . ' MB).';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Dosya yüklenemedi (kod ' . $file['error'] . ').';
        return null;
    }
    if ($file['size'] > menubook_max_bytes()) {
        $error = 'Dosya çok büyük (en fazla ' . round(menubook_max_bytes() / 1048576) . ' MB).';
        return null;
    }
    if (!PdfPages::isPdf($file['tmp_name'])) {
        $error = 'Bu dosya PDF değil.';
        return null;
    }

    $dir = OB_ROOT . '/uploads/menu-book';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        $error = 'uploads/menu-book klasörü oluşturulamadı. Klasör izinlerini kontrol edin.';
        return null;
    }

    $name = 'menu-' . date('Ymd') . '-' . bin2hex(random_bytes(4)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        $error = 'Dosya kaydedilemedi. Klasör izinlerini kontrol edin.';
        return null;
    }
    return 'uploads/menu-book/' . $name;
}

/**
 * Sayfaları güncel PDF'ten yeniden üretir.
 *
 * Eski sayfalara ancak yenisi elde edildikten SONRA dokunulur; yarım kalan
 * bir dönüştürme yayındaki kitapçığı boş bırakmamalıdır.
 *
 * @return array{ok:bool,msg:string,detail:?string}
 */
function menubook_regenerate(): array
{
    $pdf = menubook_pdf();
    if (!$pdf) {
        return ['ok' => false, 'msg' => 'Önce bir PDF yükleyin.', 'detail' => null];
    }

    $tmp = OB_ROOT . '/uploads/menu-book/.tmp-' . bin2hex(random_bytes(4));
    $res = PdfPages::render(OB_ROOT . '/' . $pdf, $tmp);

    if (!$res['ok']) {
        PdfPages::cleanup($tmp);
        return [
            'ok' => false,
            'msg' => 'Sayfalar PDF’ten üretilemedi. Sayfa görsellerini elle yükleyin.',
            'detail' => $res['error'],
        ];
    }

    // Buradan sonrası geri dönüşsüz: önce eskiyi topla, sonra yeniyi yerleştir
    $old = DB::all('SELECT image FROM menu_pages');
    $dir = OB_ROOT . '/uploads/menu-book';
    $new = [];

    // Tek parti, tek rastgele ek + sıfır dolgulu sayfa numarası: dosya adına göre
    // sıralayan her yer (migrate.php'nin natsort'u dâhil) doğru sırayı bulsun.
    $batch = date('Ymd') . '-' . bin2hex(random_bytes(3));

    foreach ($res['files'] as $i => $src) {
        $name = 'sayfa-' . $batch . '-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '.jpg';
        if (!@rename($src, $dir . '/' . $name)) {
            if (!@copy($src, $dir . '/' . $name)) { continue; }
            @unlink($src);
        }
        img_shrink($dir . '/' . $name, IMAGETYPE_JPEG, 1600);
        image_variants_make($dir . '/' . $name);
        $new[] = 'uploads/menu-book/' . $name;
    }
    PdfPages::cleanup($tmp);

    if (!$new) {
        return ['ok' => false, 'msg' => 'Üretilen sayfalar kaydedilemedi.', 'detail' => 'Klasör izinlerini kontrol edin.'];
    }

    DB::run('DELETE FROM menu_pages');
    foreach ($new as $i => $rel) {
        DB::insert('menu_pages', ['image' => $rel, 'sort' => $i + 1, 'is_active' => 1]);
    }
    foreach ($old as $o) { admin_delete_upload((string) $o['image']); }

    return [
        'ok' => true,
        'msg' => count($new) . ' sayfa PDF’ten üretildi.',
        'detail' => 'Kullanılan dönüştürücü: ' . $res['used'],
    ];
}

/* --------------------------------------------------------------------------
 *  QR kodları
 * ----------------------------------------------------------------------- */

function admin_qr(array $seg): void
{
    $action = $seg[0] ?? 'liste';

    // İndirme: /admin/qr/svg/{id} veya /admin/qr/png/{id}
    if ($action === 'svg' || $action === 'png') {
        $qr = DB::one('SELECT * FROM qr_codes WHERE id = ?', [(int) ($seg[1] ?? 0)]);
        if (!$qr) { admin_abort404(); }
        $url  = admin_qr_url($qr);
        $file = 'babyloon-qr-' . $qr['slug'];

        if ($action === 'svg') {
            header('Content-Type: image/svg+xml; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $file . '.svg"');
            echo QrCode::svg($url, 10, 4, QrCode::M, '#12100C', '#FFFFFF');
        } else {
            try { $png = QrCode::png($url, 14, 4, QrCode::M); }
            catch (Throwable) { flash('PNG için sunucuda GD eklentisi yok; SVG indirin.', 'err'); redirect(admin_url('qr')); }
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="' . $file . '.png"');
            echo $png;
        }
        exit;
    }

    // Baskıya hazır menü kartı
    if ($action === 'kart') {
        $qr = DB::one('SELECT * FROM qr_codes WHERE id = ?', [(int) ($seg[1] ?? 0)]);
        if (!$qr) { admin_abort404(); }
        echo view('admin/qr_card', ['qr' => $qr, 'url' => admin_qr_url($qr)]);
        return;
    }

    if ($action === 'sil' && is_post()) {
        csrf_check();
        $row = DB::one('SELECT * FROM qr_codes WHERE id = ?', [(int) ($seg[1] ?? 0)]);
        if ($row && $row['slug'] === 'menu') {
            // Menünün ana kodu her yere basılıyor; kazara silinmesin
            flash('Menü QR kodu silinemez. Yalnızca ek kodlar kaldırılabilir.', 'err');
        } elseif ($row) {
            DB::delete('qr_codes', 'id = ?', [(int) $row['id']]);
            flash('QR kodu silindi.');
        }
        redirect(admin_url('qr'));
    }

    if ($action === 'sifirla' && is_post()) {
        csrf_check();
        DB::run('UPDATE qr_codes SET scans = 0, last_scan_at = NULL WHERE id = ?', [(int) ($seg[1] ?? 0)]);
        flash('Sayaç sıfırlandı.');
        redirect(admin_url('qr'));
    }

    // Yeni kod
    $errors = [];
    if (is_post()) {
        csrf_check();
        $label = (string) input('label');
        $kind  = (string) input('kind');
        $slug  = slugify((string) input('slug') ?: $label);
        // Hedef, türe göre farklı alandan gelir — JS'e bağlı değil
        $target = match ($kind) {
            'category' => trim((string) input('target_category')),
            'url'      => trim((string) input('target_url')),
            default    => '',
        };

        if ($label === '') { $errors['label'] = 'Etiket zorunlu.'; }
        if (!in_array($kind, ['menu', 'category', 'url'], true)) { $kind = 'menu'; }
        if ($kind === 'url' && !filter_var($target, FILTER_VALIDATE_URL)) {
            $errors['target'] = 'Geçerli bir adres girin (https://…).';
        }
        if (DB::value('SELECT 1 FROM qr_codes WHERE slug = ?', [$slug])) {
            $errors['slug'] = 'Bu kısa ad zaten kullanılıyor.';
        }
        if (!$errors) {
            DB::insert('qr_codes', [
                'label' => $label, 'slug' => $slug, 'kind' => $kind,
                'target' => $kind === 'menu' ? null : $target,
                'is_active' => 1, 'created_at' => DB::now(),
            ]);
            flash('QR kodu oluşturuldu.');
            redirect(admin_url('qr'));
        }
    }

    admin_render('admin/qr', [
        'title'  => 'QR Menü Kodu',
        'rows'   => DB::all('SELECT * FROM qr_codes ORDER BY id DESC'),
        'errors' => $errors,
        'cats'   => DB::all('SELECT slug, name_tr FROM menu_categories WHERE is_active = 1 ORDER BY sort'),
    ]);
}

/** QR'ın işaret ettiği tam adres. */
function admin_qr_url(array $qr): string
{
    if ($qr['kind'] === 'url' && $qr['target']) { return (string) $qr['target']; }
    // Yayın alan adı girildiyse QR her zaman https adresine gider (baskı yerelden alınsa da)
    $scheme = setting('public_host') !== '' ? 'https' : ((($_SERVER['HTTPS'] ?? '') === 'on') ? 'https' : 'http');
    $host   = setting('public_host') ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $scheme . '://' . $host . base() . '/qr/' . $qr['slug'];
}

/* --------------------------------------------------------------------------
 *  Ayarlar
 * ----------------------------------------------------------------------- */

function admin_settings(): void
{
    $groups = admin_setting_groups();

    if (is_post()) {
        csrf_check();
        foreach ($groups as $g) {
            foreach ($g['fields'] as $key => $f) {
                $type = $f['type'] ?? 'text';

                if ($type === 'image') {
                    if (!empty($_POST['__remove_' . $key])) {
                        admin_delete_upload(setting($key));
                        setting_put($key, '');
                    } else {
                        $up = admin_handle_upload($key, $f['folder'] ?? 'misc', $err);
                        if ($up) { admin_delete_upload(setting($key)); setting_put($key, $up); }
                    }
                    continue;
                }

                if (in_array($type, ['i18n', 'i18n_area', 'i18n_html'], true)) {
                    foreach (array_keys(locales()) as $loc) {
                        $col = $key . '_' . $loc;
                        $val = trim((string) ($_POST[$col] ?? ''));
                        if ($type === 'i18n_html') { $val = safe_html($val); }
                        setting_put($col, $val);
                    }
                    continue;
                }

                if ($type === 'checkbox') { setting_put($key, input_bool($key) ? '1' : '0'); continue; }
                if ($type === 'raw_html') { setting_put($key, trim((string) ($_POST[$key] ?? ''))); continue; }

                setting_put($key, trim((string) ($_POST[$key] ?? '')));
            }
        }
        flash('Ayarlar kaydedildi.');
        redirect(admin_url('ayarlar'));
    }

    admin_render('admin/settings', ['title' => 'Site Ayarları', 'groups' => $groups]);
}

/* --------------------------------------------------------------------------
 *  Kullanıcılar
 * ----------------------------------------------------------------------- */

function admin_users(array $seg): void
{
    $me = auth();
    $action = $seg[0] ?? 'liste';

    if ($action === 'sil' && is_post()) {
        csrf_check();
        $id = (int) ($seg[1] ?? 0);
        if ($id === (int) $me['id']) {
            flash('Kendi hesabınızı silemezsiniz.', 'err');
        } elseif ((int) DB::value('SELECT COUNT(*) FROM admin_users', [], 0) <= 1) {
            flash('Son yönetici hesabı silinemez.', 'err');
        } else {
            DB::delete('admin_users', 'id = ?', [$id]);
            flash('Kullanıcı silindi.');
        }
        redirect(admin_url('kullanicilar'));
    }

    $errors = [];
    if (is_post()) {
        csrf_check();
        $mode = (string) input('mode');

        if ($mode === 'password') {
            $cur = (string) input('current_password');
            $new = (string) input('new_password');
            $rep = (string) input('repeat_password');
            if (!password_verify($cur, $me['password_hash'])) { $errors['current_password'] = 'Mevcut şifre hatalı.'; }
            if (mb_strlen($new) < 8) { $errors['new_password'] = 'En az 8 karakter olmalı.'; }
            if ($new !== $rep) { $errors['repeat_password'] = 'Şifreler eşleşmiyor.'; }
            if (!$errors) {
                DB::run('UPDATE admin_users SET password_hash = ? WHERE id = ?',
                        [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
                flash('Şifreniz güncellendi.');
                redirect(admin_url('kullanicilar'));
            }
        }

        if ($mode === 'create') {
            $u = slugify((string) input('username'));
            $n = (string) input('name');
            $p = (string) input('password');
            if ($u === '' || $u === 'kayit') { $errors['username'] = 'Kullanıcı adı zorunlu.'; }
            if (DB::value('SELECT 1 FROM admin_users WHERE username = ?', [$u])) { $errors['username'] = 'Bu kullanıcı adı alınmış.'; }
            if (mb_strlen($p) < 8) { $errors['password'] = 'Şifre en az 8 karakter olmalı.'; }
            if (!$errors) {
                DB::insert('admin_users', [
                    'username' => $u, 'name' => $n ?: $u,
                    'password_hash' => password_hash($p, PASSWORD_DEFAULT),
                    'role' => 'admin', 'is_active' => 1, 'created_at' => DB::now(),
                ]);
                flash('Kullanıcı eklendi.');
                redirect(admin_url('kullanicilar'));
            }
        }
    }

    admin_render('admin/users', [
        'title'  => 'Kullanıcılar',
        'rows'   => DB::all('SELECT id, username, name, role, is_active, last_login_at, created_at FROM admin_users ORDER BY id'),
        'me'     => $me,
        'errors' => $errors,
    ]);
}

/* --------------------------------------------------------------------------
 *  Instagram akışı
 * ----------------------------------------------------------------------- */

function admin_instagram(array $seg): void
{
    $action = $seg[0] ?? '';
    $errors = [];

    if ($action === 'gizle' && is_post()) {
        csrf_check();
        $id = (int) ($seg[1] ?? 0);
        DB::run('UPDATE instagram_posts SET is_active = CASE is_active WHEN 1 THEN 0 ELSE 1 END WHERE id = ?', [$id]);
        redirect(admin_url('instagram'));
    }

    if ($action === 'sil' && is_post()) {
        csrf_check();
        $row = DB::one('SELECT * FROM instagram_posts WHERE id = ?', [(int) ($seg[1] ?? 0)]);
        if ($row) {
            // Yalnızca elle eklenen görseller silinir; API görselleri yeniden indirilebilir
            if ($row['image']) { admin_delete_upload((string) $row['image']); }
            DB::delete('instagram_posts', 'id = ?', [(int) $row['id']]);
            flash('Gönderi kaldırıldı.');
        }
        redirect(admin_url('instagram'));
    }

    if ($action === 'yenile' && is_post()) {
        csrf_check();
        $r = Instagram::sync();
        flash($r['message'], $r['ok'] ? 'ok' : 'err');
        redirect(admin_url('instagram'));
    }

    if ($action === 'jeton' && is_post()) {
        csrf_check();
        $r = Instagram::refreshToken();
        flash($r['message'], $r['ok'] ? 'ok' : 'err');
        redirect(admin_url('instagram'));
    }

    // Ayar kaydı + elle gönderi ekleme
    if (is_post()) {
        csrf_check();
        $mode = (string) input('mode');

        if ($mode === 'settings') {
            setting_put('ig_enabled',  input_bool('ig_enabled') ? '1' : '0');
            setting_put('ig_username', trim((string) input('ig_username')));
            setting_put('ig_user_id',  trim((string) input('ig_user_id')));
            setting_put('ig_limit',    (string) max(1, min(50, input_int('ig_limit', 12))));
            $token = trim((string) input('ig_token'));
            if ($token !== '') { setting_put('ig_token', $token); }
            if (input_bool('ig_clear_token')) { setting_put('ig_token', ''); }
            flash('Instagram ayarları kaydedildi.');
            redirect(admin_url('instagram'));
        }

        if ($mode === 'manual') {
            $link = trim((string) input('permalink'));
            $img  = admin_handle_upload('image', 'instagram', $err);
            if ($err) { $errors['image'] = $err; }
            if (!$img) { $errors['image'] = $errors['image'] ?? 'Bir görsel seçin.'; }
            if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
                $errors['permalink'] = 'Geçerli bir gönderi adresi girin.';
            }
            if (!$errors) {
                DB::insert('instagram_posts', [
                    'ig_id'      => null,
                    'permalink'  => $link ?: setting('instagram'),
                    'media_type' => 'IMAGE',
                    'caption'    => mb_substr((string) input('caption'), 0, 1000),
                    'image'      => $img,
                    'posted_at'  => DB::now(),
                    'sort'       => -1,           // elle eklenenler öne gelsin
                    'is_manual'  => 1,
                    'is_active'  => 1,
                    'created_at' => DB::now(),
                ]);
                flash('Gönderi eklendi.');
                redirect(admin_url('instagram'));
            }
        }
    }

    admin_render('admin/instagram', [
        'title'  => 'Instagram Akışı',
        'rows'   => DB::all('SELECT * FROM instagram_posts ORDER BY sort, posted_at DESC, id DESC'),
        'errors' => $errors,
        'lastSync'  => (int) setting('ig_last_sync', '0'),
        'lastError' => setting('ig_last_error'),
    ]);
}
