<?php
declare(strict_types=1);

/**
 * Old Babyloon — başlangıç verisi.
 *
 * Her blok yalnızca ilgili tablo BOŞSA çalışır, böylece migrate.php canlıda
 * tekrar çalıştırılsa da veriyi bozmaz.
 *
 * Yüklenenler müşteriden gelen ya da onun PDF menüsünden doğrulanan
 * bilgilerdir: menü (seed_menu.php), fotoğraflar (seed_photos.php),
 * Hakkımızda/SEO metinleri (seed_about.php), telefon, adres, alan adı.
 * Doğrulanmamış hiçbir bilgi (saat, e-posta, sosyal hesap) yazılmaz; o
 * anahtarlar panelde görünsün diye boş oluşturulur.
 *
 * Yönetici şifresi canlıda sunucudaki babyloon-config.php'den gelir
 * ('admin_password'); depoda şifre yoktur.
 */
function ob_seed(): void
{
    /* ---- Yönetici ------------------------------------------------------- */
    /* Canlıda şifre depodan değil, sunucudaki babyloon-config.php'den gelir
       ('admin_password'). Depo herkese açık olabilir; varsayılan şifreyle
       açılan bir panel kurulumla ilk girişin arasında savunmasız kalırdı.
       Canlıda bu değer yoksa yönetici HİÇ oluşturulmaz. */
    if (!DB::value('SELECT COUNT(*) FROM admin_users')) {
        $pass = (string) cfg('admin_password', '');
        if ($pass === '' && cfg('env') !== 'production') { $pass = 'babyloon2026'; }   // yalnız yerel
        if ($pass === '') {
            echo "UYARI: babyloon-config.php içinde 'admin_password' yok — yönetici oluşturulmadı.\n";
        } else {
            DB::insert('admin_users', [
                'username'      => 'admin',
                'name'          => 'Old Babyloon Yönetim',
                'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            echo "Yönetici oluşturuldu: admin\n";
        }
    }

    /* ---- Ayarlar --------------------------------------------------------
     * Boş bırakılanlar müşteriden teyit bekliyor. Yalnızca yapısal olanlar
     * (para birimi, Instagram sayaçları) dolu geliyor.
     */
    if (!DB::value('SELECT COUNT(*) FROM settings')) {
        $bos = [
            // Kimlik ve slogan
            'tagline_tr', 'tagline_en',

            // Hakkında
            'about_title_tr', 'about_title_en', 'about_text_tr', 'about_text_en',
            'about_image',

            // Menü
            'menu_intro_tr', 'menu_intro_en', 'menu_note_tr', 'menu_note_en',

            // İletişim — DOĞRULANMADAN DOLDURULMAZ
            'address', 'phone', 'whatsapp', 'email',
            'map_embed', 'map_lat', 'map_lng',
            'instagram', 'facebook', 'instagram_tag', 'ig_username',
            'ig_token', 'ig_user_id',
            'legal_entity',

            // SEO
            'seo_title_tr', 'seo_title_en', 'seo_desc_tr', 'seo_desc_en',
        ];

        $defaults = ['site_name' => 'Old Babyloon'];
        foreach ($bos as $k) { $defaults[$k] = ''; }

        // Müşterinin verdiği bilgiler (2026-09-30)
        $defaults = array_merge($defaults, [
            'phone'   => '+90 533 830 50 90',
            'address' => 'Dereboyu, Lefkoşa',
            // Yayın alan adı — QR kodları buraya işaret eder (2026-10-06)
            'public_host' => 'oldbabyloon.com',
        ]);

        // Hakkımızda metni ve slogan (menüden doğrulanan bilgilerle): app/seed_about.php
        $defaults = array_merge($defaults, require __DIR__ . '/seed_about.php');

        $defaults += [
            'currency'          => '₺',
            'currency_position' => 'after',
            'ig_enabled'        => '0',
            'hours_confirmed'   => '0',   // saatler yer tutucu; doğrulanınca panelden işaretlenir
            'ig_limit'          => '12',
        ];

        foreach ($defaults as $k => $v) { DB::insert('settings', ['k' => $k, 'v' => $v]); }
    }

    /* ---- Çalışma saatleri -----------------------------------------------
     * YER TUTUCU: gerçek saatler teyit edilip panelden girilmeli.
     * Yedi satır burada oluşuyor ki panel ekranı boş açılmasın.
     */
    if (!DB::value('SELECT COUNT(*) FROM hours')) {
        for ($d = 1; $d <= 7; $d++) {
            DB::insert('hours', [
                'day_no'     => $d,
                'open_time'  => '09:00',
                'close_time' => '23:00',
                'is_closed'  => 0,
            ]);
        }
    }

    /* ---- Menü (QR menü) -------------------------------------------------
     * Müşterinin PDF menüsünden yazıya dökülen veri: app/seed_menu.php
     */
    if (!DB::value('SELECT COUNT(*) FROM menu_categories')) {
        foreach (require __DIR__ . '/seed_menu.php' as $ci => [$slug, $tr, $en, $ttr, $ten, $icon, $items]) {
            $catId = DB::insert('menu_categories', [
                'slug' => $slug, 'name_tr' => $tr, 'name_en' => $en,
                'tagline_tr' => $ttr, 'tagline_en' => $ten,
                'icon' => $icon, 'sort' => $ci + 1, 'is_active' => 1,
            ]);
            foreach ($items as $ii => $it) {
                DB::insert('menu_items', $it + [
                    'category_id' => $catId, 'sort' => $ii + 1, 'is_available' => 1,
                ]);
            }
        }
    }

    /* ---- Fotoğraflar (menü PDF'inden kırpıldı): app/seed_photos.php -------
     * Ürün fotoğrafı yalnızca boş olan ürüne yazılır; panelde değiştirilen
     * görsel ezilmez. Galeri ve slaytlar yalnızca tablo boşsa eklenir.
     */
    $photos = require __DIR__ . '/seed_photos.php';
    foreach ($photos['items'] as $name => $path) {
        if (!is_file(OB_ROOT . '/' . $path)) { continue; }
        DB::run("UPDATE menu_items SET image = ? WHERE name_tr = ? AND (image IS NULL OR image = '')", [$path, $name]);
    }
    // Kendi ürün fotoğrafı olmayan kategoriye kapak (pizza fotoğrafları "Yeni Lezzetler"de)
    foreach (['pizza' => 'uploads/menu/bbq-chicken-pizza.jpg'] as $slug => $path) {
        if (is_file(OB_ROOT . '/' . $path)) {
            DB::run("UPDATE menu_categories SET image = ? WHERE slug = ? AND (image IS NULL OR image = '')", [$path, $slug]);
        }
    }

    if (!DB::value('SELECT COUNT(*) FROM gallery')) {
        $enAd = [];
        foreach (require __DIR__ . '/seed_menu.php' as [, , , , , , $items]) {
            foreach ($items as $it) { $enAd[$it['name_tr']] = $it['name_en'] ?? null; }
        }
        foreach ($photos['gallery'] as $i => [$path, $caption]) {
            DB::insert('gallery', [
                'image' => $path, 'caption_tr' => $caption, 'caption_en' => $caption !== null ? ($enAd[$caption] ?? null) : null,
                'sort' => $i + 1, 'is_active' => 1,
            ]);
        }
    }
    if (!DB::value('SELECT COUNT(*) FROM slides')) {
        // Kasap köfte fotoğrafı en yüksek çözünürlüklü kırpım: açılış onunla
        [$iskender, $kofte] = $photos['slides'];
        DB::insert('slides', [
            'image' => $kofte,
            'kicker_tr' => 'Dereboyu · Lefkoşa', 'kicker_en' => 'Dereboyu · Nicosia',
            'title_tr' => 'Old Babyloon', 'title_en' => 'Old Babyloon',
            'text_tr' => 'Cafe & Restaurant', 'text_en' => 'Cafe & Restaurant',
            'btn_label_tr' => 'Menüye göz atın', 'btn_label_en' => 'Browse the menu', 'btn_url' => '',
            'sort' => 1, 'is_active' => 1,
        ]);
        DB::insert('slides', [
            'image' => $iskender,
            'kicker_tr' => 'Mutfağın imzası', 'kicker_en' => 'House signature',
            'title_tr' => 'Babil İskender', 'title_en' => 'Babil İskender Kebab',
            'text_tr' => 'İskender özel ekmeği, ızgara kasap köfte, iskender sos, ızgara biber ve yoğurt.',
            'text_en' => 'Special İskender bread, grilled butcher’s meatballs, İskender sauce, grilled pepper and yoghurt.',
            'btn_label_tr' => 'Menüye göz atın', 'btn_label_en' => 'Browse the menu', 'btn_url' => '',
            'sort' => 2, 'is_active' => 1,
        ]);
    }

    /* ---- Ana sayfada öne çıkan ürünler ----------------------------------
     * Fotoğrafı net olanlardan, her bölümden biri. Hiç öne çıkan yoksa yazılır;
     * panelde "Ana sayfada öne çıkar" kutusuyla değiştirilir.
     */
    if (!DB::value('SELECT COUNT(*) FROM menu_items WHERE is_featured = 1')) {
        foreach (['Babil İskender', 'Et Fajita Servis', 'Kumru Burger', 'Izgara Kasap Köfte',
                  'Serpme Kahvaltı', 'Mantı', 'Dondurmalı Sufle', 'Latte Bubble'] as $name) {
            DB::run('UPDATE menu_items SET is_featured = 1 WHERE name_tr = ?', [$name]);
        }
    }

    /* ---- "Kahvaltıdan nargileye" maddeleri -------------------------------
     * Yalnızca menüde yazanlardan türetildi; iddia yok.
     */
    if (!DB::value('SELECT COUNT(*) FROM features')) {
        $features = [
            ['cup', 'Güne kahvaltıyla', 'Start with breakfast',
             'Serpme kahvaltıdan sahanda yumurtaya, menemenden omlete. Serpme kahvaltıda çay sınırsız.',
             'From the full Turkish breakfast spread to eggs, menemen and omelettes. Tea is unlimited with the spread.'],
            ['plate', 'Izgara, burger, dürüm', 'Grill, burgers, wraps',
             'Kasap köfte, ev yapımı burgerler, fajitalar, pizzalar ve Babil’in kendi tabakları.',
             'Butcher’s meatballs, homemade burgers, fajitas, pizzas and Babil’s own plates.'],
            ['cake', 'Tatlı ve kahve', 'Desserts & coffee',
             'Künefe, trileçe, pastalar ve dondurma; Türk kahvesinden bubble tea’ye.',
             'Künefe, trileçe, cakes and ice cream; from Turkish coffee to bubble tea.'],
            ['dice', 'Nargile ve oyun', 'Hookah & games',
             'Nargile karışımları; masada tavla, okey, Monopoly, Scrabble, Jenga ve satranç.',
             'Hookah blends; at the table: backgammon, okey, Monopoly, Scrabble, Jenga and chess.'],
        ];
        foreach ($features as $i => [$icon, $ttr, $ten, $xtr, $xen]) {
            DB::insert('features', [
                'icon' => $icon, 'title_tr' => $ttr, 'title_en' => $ten,
                'text_tr' => $xtr, 'text_en' => $xen, 'sort' => $i + 1, 'is_active' => 1,
            ]);
        }
    }

    /* ---- Menü kitapçığı -------------------------------------------------
     * Dosya güdümlü: uploads/menu-book içine PDF ya da sayfa görselleri
     * konulunca kendiliğinden bağlanır. Panelden de yüklenebilir.
     */
    if (setting('menu_pdf') === '') {
        $pdfs = array_values(glob(OB_ROOT . '/uploads/menu-book/*.pdf') ?: []);
        if ($pdfs) { setting_put('menu_pdf', 'uploads/menu-book/' . basename($pdfs[0])); }
    }

    if (!DB::value('SELECT COUNT(*) FROM menu_pages')) {
        // "@" içerenler duyarlı türevlerdir, kaynak değil
        $files = array_filter(glob(OB_ROOT . '/uploads/menu-book/sayfa-*.jpg') ?: [],
                              fn($f) => !str_contains(basename($f), '@'));
        natsort($files);
        $i = 0;
        foreach ($files as $path) {
            DB::insert('menu_pages', [
                'image' => 'uploads/menu-book/' . basename($path),
                'sort'  => $i++, 'is_active' => 1,
            ]);
        }
    }

    /* ---- QR kodları ------------------------------------------------------ */
    if (!DB::value('SELECT COUNT(*) FROM qr_codes')) {
        DB::insert('qr_codes', [
            'label' => 'Menü QR Kodu', 'slug' => 'menu',
            'kind' => 'menu', 'target' => null, 'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
