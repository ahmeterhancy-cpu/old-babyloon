<?php
/**
 * Old Babyloon — şema kurulumu + başlangıç verisi.
 *
 *   php migrate.php          → eksik tabloları oluşturur, veri yoksa doldurur
 *   php migrate.php --fresh  → tüm tabloları silip sıfırdan kurar (VERİ GİDER)
 *
 * Tarayıcıdan da çalıştırılabilir: /migrate.php?key=<setup_key>
 * Sunucuda kurulum bitince bu dosyayı SİLİN.
 */

declare(strict_types=1);

$config = require __DIR__ . '/app/config.php';
$GLOBALS['OB_CONFIG'] = $config;
require __DIR__ . '/app/db.php';
require __DIR__ . '/app/helpers.php';

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    // Web'den çalıştırma yalnızca kurulum anahtarıyla
    $key = $_GET['key'] ?? '';
    $expected = getenv('OB_SETUP_KEY') ?: 'babyloon-kurulum';
    if (!hash_equals($expected, (string) $key)) {
        http_response_code(403);
        exit('Kurulum anahtarı geçersiz.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

$fresh = $isCli
    ? in_array('--fresh', $argv ?? [], true)
    : isset($_GET['fresh']);

DB::boot($config);
$pdo = DB::pdo();

function say(string $msg): void { echo $msg . PHP_EOL; }

/* --------------------------------------------------------------------------
 *  Sürücü farkları
 * ----------------------------------------------------------------------- */
$sqlite = DB::isSqlite();
$PK     = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
$SUFFIX = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
$TXT    = 'TEXT';
$STR    = $sqlite ? 'TEXT' : 'VARCHAR(255)';
$STR64  = $sqlite ? 'TEXT' : 'VARCHAR(64)';
$DEC    = $sqlite ? 'REAL' : 'DECIMAL(10,2)';

$tables = [
    'settings' => "k $STR64 NOT NULL PRIMARY KEY, v $TXT",

    'admin_users' => "id $PK,
        username $STR64 NOT NULL UNIQUE,
        name $STR NOT NULL DEFAULT '',
        password_hash $STR NOT NULL,
        role $STR64 NOT NULL DEFAULT 'admin',
        is_active INTEGER NOT NULL DEFAULT 1,
        last_login_at $STR64 NULL,
        created_at $STR64 NOT NULL",

    'slides' => "id $PK,
        image $STR NULL,
        kicker_tr $STR NULL, kicker_en $STR NULL,
        title_tr $STR NULL, title_en $STR NULL,
        text_tr $TXT NULL, text_en $TXT NULL,
        btn_label_tr $STR NULL, btn_label_en $STR NULL,
        btn_url $STR NULL,
        sort INTEGER NOT NULL DEFAULT 0,
        is_active INTEGER NOT NULL DEFAULT 1",

    'menu_categories' => "id $PK,
        slug $STR64 NOT NULL UNIQUE,
        name_tr $STR NOT NULL, name_en $STR NULL,
        tagline_tr $STR NULL, tagline_en $STR NULL,
        icon $STR64 NULL,
        image $STR NULL,
        sort INTEGER NOT NULL DEFAULT 0,
        is_active INTEGER NOT NULL DEFAULT 1",

    'menu_items' => "id $PK,
        category_id INTEGER NOT NULL,
        name_tr $STR NOT NULL, name_en $STR NULL,
        description_tr $TXT NULL, description_en $TXT NULL,
        price $DEC NOT NULL DEFAULT 0,
        price2 $DEC NULL,
        size1_tr $STR64 NULL, size1_en $STR64 NULL,
        size2_tr $STR64 NULL, size2_en $STR64 NULL,
        image $STR NULL,
        badges $STR NULL,
        group_tr $STR NULL, group_en $STR NULL,
        allergens_tr $STR NULL, allergens_en $STR NULL,
        is_available INTEGER NOT NULL DEFAULT 1,
        is_featured INTEGER NOT NULL DEFAULT 0,
        sort INTEGER NOT NULL DEFAULT 0",

    'gallery' => "id $PK,
        image $STR NOT NULL,
        caption_tr $STR NULL, caption_en $STR NULL,
        sort INTEGER NOT NULL DEFAULT 0,
        is_active INTEGER NOT NULL DEFAULT 1",

    'posts' => "id $PK,
        slug $STR64 NOT NULL UNIQUE,
        title_tr $STR NOT NULL, title_en $STR NULL,
        excerpt_tr $TXT NULL, excerpt_en $TXT NULL,
        body_tr $TXT NULL, body_en $TXT NULL,
        cover $STR NULL,
        author $STR NULL,
        published_at $STR64 NULL,
        is_published INTEGER NOT NULL DEFAULT 1",

    'testimonials' => "id $PK,
        name $STR NOT NULL,
        role_tr $STR NULL, role_en $STR NULL,
        quote_tr $TXT NULL, quote_en $TXT NULL,
        rating INTEGER NOT NULL DEFAULT 5,
        avatar $STR NULL,
        sort INTEGER NOT NULL DEFAULT 0,
        is_active INTEGER NOT NULL DEFAULT 1",

    'features' => "id $PK,
        icon $STR64 NULL,
        title_tr $STR NOT NULL, title_en $STR NULL,
        text_tr $TXT NULL, text_en $TXT NULL,
        sort INTEGER NOT NULL DEFAULT 0,
        is_active INTEGER NOT NULL DEFAULT 1",

    'team' => "id $PK,
        name $STR NOT NULL,
        role_tr $STR NULL, role_en $STR NULL,
        bio_tr $TXT NULL, bio_en $TXT NULL,
        photo $STR NULL,
        sort INTEGER NOT NULL DEFAULT 0,
        is_active INTEGER NOT NULL DEFAULT 1",

    'hours' => "id $PK,
        day_no INTEGER NOT NULL,
        open_time $STR64 NULL,
        close_time $STR64 NULL,
        is_closed INTEGER NOT NULL DEFAULT 0,
        note_tr $STR NULL, note_en $STR NULL",

    'messages' => "id $PK,
        name $STR NOT NULL,
        email $STR NULL,
        phone $STR NULL,
        subject $STR NULL,
        body $TXT NULL,
        ip $STR64 NULL,
        is_read INTEGER NOT NULL DEFAULT 0,
        created_at $STR64 NOT NULL",

    'instagram_posts' => "id $PK,
        ig_id $STR64 NULL,
        permalink $STR NULL,
        media_type $STR64 NULL,
        caption $TXT NULL,
        image $STR NULL,
        posted_at $STR64 NULL,
        sort INTEGER NOT NULL DEFAULT 0,
        is_manual INTEGER NOT NULL DEFAULT 0,
        is_active INTEGER NOT NULL DEFAULT 1,
        created_at $STR64 NOT NULL",
    'menu_pages' => "id $PK,
        image $STR NOT NULL,
        caption_tr $STR NULL, caption_en $STR NULL,
        sort INTEGER NOT NULL DEFAULT 0,
        is_active INTEGER NOT NULL DEFAULT 1",

    'qr_codes' => "id $PK,
        label $STR NOT NULL,
        slug $STR64 NOT NULL UNIQUE,
        kind $STR64 NOT NULL DEFAULT 'menu',
        target $STR NULL,
        scans INTEGER NOT NULL DEFAULT 0,
        last_scan_at $STR64 NULL,
        is_active INTEGER NOT NULL DEFAULT 1,
        created_at $STR64 NOT NULL",
];

if ($fresh) {
    say('--fresh: mevcut tablolar siliniyor…');
    if (!$sqlite) { $pdo->exec('SET FOREIGN_KEY_CHECKS = 0'); }
    foreach (array_reverse(array_keys($tables)) as $t) { $pdo->exec("DROP TABLE IF EXISTS $t"); }
    if (!$sqlite) { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); }
}

foreach ($tables as $name => $cols) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS $name ($cols)$SUFFIX");
}

// İndeksler (iki sürücüde de aynı sözdizimi)
foreach ([
    'CREATE INDEX IF NOT EXISTS idx_items_cat  ON menu_items (category_id)',
    'CREATE INDEX IF NOT EXISTS idx_items_sort ON menu_items (sort)',
    'CREATE INDEX IF NOT EXISTS idx_posts_pub  ON posts (is_published, published_at)',
    'CREATE INDEX IF NOT EXISTS idx_msg_read   ON messages (is_read, created_at)',
    'CREATE UNIQUE INDEX IF NOT EXISTS idx_ig_id ON instagram_posts (ig_id)',
    'CREATE INDEX IF NOT EXISTS idx_ig_show    ON instagram_posts (is_active, sort)',
] as $sql) {
    try { $pdo->exec($sql); } catch (Throwable) { /* MySQL 5.x IF NOT EXISTS desteklemez */ }
}

say('Tablolar hazır (' . DB::driver() . ').');

/* --------------------------------------------------------------------------
 *  Başlangıç verisi — yalnızca tablo boşsa
 * ----------------------------------------------------------------------- */

require __DIR__ . '/app/seed_data.php';
ob_seed();

say('Bitti.');
if (!$isCli) { say(''); say('Bu dosyayı sunucudan silmeyi unutmayın.'); }
