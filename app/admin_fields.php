<?php
declare(strict_types=1);

/* ==========================================================================
 *  Panelin veri tanımları.
 *  Yeni bir içerik türü eklemek için buraya bir kayıt yazmak yeterli;
 *  liste, form, doğrulama ve silme genel motordan gelir.
 * ======================================================================= */

/** Menü ürünlerinde kullanılabilen rozetler */
function admin_badge_options(): array
{
    return [
        'vegan'      => 'Vegan',
        'vejetaryen' => 'Vejetaryen',
        'glutensiz'  => 'Glutensiz',
        'kafeinsiz'  => 'Kafeinsiz',
        'aci'        => 'Acı',
        'yeni'       => 'Yeni',
        'sef'        => 'Şefin seçimi',
    ];
}

/** Kategori ikonu seçenekleri (app/views/layout/icon.php ile aynı adlar) */
function admin_icon_options(): array
{
    return [
        'cup' => 'Fincan', 'dripper' => 'Demleme', 'ice' => 'Buzlu bardak',
        'leaf' => 'Yaprak', 'cake' => 'Pasta', 'plate' => 'Tabak',
        'roaster' => 'Kavurma', 'bean' => 'Çekirdek', 'wifi' => 'Wi-Fi',
        'clock' => 'Saat', 'pin' => 'Konum', 'star' => 'Yıldız',
        'dice' => 'Zar (oyun)', 'hookah' => 'Nargile',
    ];
}

function admin_resources(): array
{
    return [

        /* ---- Ana sayfa slaytları ---------------------------------------- */
        'slides' => [
            'title' => 'Ana Sayfa Slaytları',
            'table' => 'slides',
            'order' => 'sort, id',
            'hint'  => 'Ana sayfanın en üstünde dönen görseller. Görsel yüklemezseniz koyu zemin kullanılır. En iyi sonuç için 2000×1150 piksel.',
            'columns' => ['image' => 'Görsel', 'title_tr' => 'Başlık', 'is_active' => 'Yayında', 'sort' => 'Sıra'],
            'fields' => [
                'image'     => ['type' => 'image', 'label' => 'Arka plan görseli', 'folder' => 'slider'],
                'kicker'    => ['type' => 'i18n', 'label' => 'Üst yazı', 'hint' => 'Başlığın üstündeki küçük satır'],
                'title'     => ['type' => 'i18n', 'label' => 'Başlık', 'required' => true],
                'text'      => ['type' => 'i18n_area', 'label' => 'Açıklama'],
                'btn_label' => ['type' => 'i18n', 'label' => 'Buton yazısı'],
                'btn_url'   => ['type' => 'text', 'label' => 'Buton bağlantısı', 'nullable' => true,
                                'hint' => 'Site içi yol yazın: menu, menu/tatlilar, iletisim'],
                'is_active' => ['type' => 'checkbox', 'label' => 'Yayında', 'default' => 1],
                'sort'      => ['type' => 'number', 'label' => 'Sıra'],
            ],
        ],

        /* ---- Menü kategorileri ------------------------------------------ */
        'menu_categories' => [
            'title' => 'Menü Kategorileri',
            'table' => 'menu_categories',
            'order' => 'sort, id',
            'hint'  => 'Kategoriler hem menü sayfasında hem QR menüde bu sırayla görünür.',
            'columns' => ['name_tr' => 'Kategori', 'slug' => 'Kısa ad', 'icon' => 'İkon', 'is_active' => 'Yayında', 'sort' => 'Sıra'],
            'fields' => [
                'name'      => ['type' => 'i18n', 'label' => 'Kategori adı', 'required' => true],
                'slug'      => ['type' => 'slug', 'label' => 'Kısa ad (adres)', 'from' => 'name',
                                'hint' => 'Boş bırakırsanız addan üretilir. Yayına girdikten sonra değiştirmeyin.'],
                'tagline'   => ['type' => 'i18n', 'label' => 'Alt başlık'],
                'icon'      => ['type' => 'select', 'label' => 'İkon', 'options' => 'admin_icon_options'],
                'is_active' => ['type' => 'checkbox', 'label' => 'Yayında', 'default' => 1],
                'sort'      => ['type' => 'number', 'label' => 'Sıra'],
            ],
        ],

        /* ---- Menü ürünleri ---------------------------------------------- */
        'menu_items' => [
            'title' => 'Menü Ürünleri',
            'table' => 'menu_items',
            'order' => 'category_id, sort, id',
            'hint'  => 'Tükenen ürünü silmeyin — "Serviste" kutusunun işaretini kaldırın; menüde üstü çizili görünür.',
            'group_by' => 'category_id',
            'columns' => ['image' => 'Görsel', 'name_tr' => 'Ürün', 'price' => 'Fiyat',
                          'is_available' => 'Serviste', 'is_featured' => 'Öne çıkan', 'sort' => 'Sıra'],
            'fields' => [
                'category_id'  => ['type' => 'select', 'label' => 'Kategori', 'required' => true,
                                   'options' => 'admin_category_options'],
                'name'         => ['type' => 'i18n', 'label' => 'Ürün adı', 'required' => true],
                'group'        => ['type' => 'i18n', 'label' => 'Alt başlık',
                                   'hint' => 'Kategori içinde ara başlık — örn. VİSKİ, CİN, MATCHA. Boş bırakılabilir.'],
                'description'  => ['type' => 'i18n_area', 'label' => 'Açıklama'],
                'price'        => ['type' => 'price', 'label' => 'Fiyat'],
                'size1'        => ['type' => 'i18n', 'label' => '1. boy adı', 'hint' => 'Örn. Küçük / Tek'],
                'price2'       => ['type' => 'price', 'label' => '2. fiyat', 'nullable' => true,
                                   'hint' => 'İki boy satılıyorsa doldurun; yoksa boş bırakın.'],
                'size2'        => ['type' => 'i18n', 'label' => '2. boy adı', 'hint' => 'Örn. Büyük / Duble'],
                'image'        => ['type' => 'image', 'label' => 'Ürün fotoğrafı', 'folder' => 'menu',
                                   'hint' => 'Kare fotoğraf en iyi görünür (800×800).'],
                'badges'       => ['type' => 'badges', 'label' => 'Etiketler', 'options' => 'admin_badge_options'],
                'is_available' => ['type' => 'checkbox', 'label' => 'Serviste', 'default' => 1],
                'is_featured'  => ['type' => 'checkbox', 'label' => 'Ana sayfada öne çıkar'],
                'sort'         => ['type' => 'number', 'label' => 'Sıra'],
            ],
        ],

        /* ---- Menü kitapçığı --------------------------------------------- */
        'menu_pages' => [
            'title' => 'Menü Kitapçığı',
            'table' => 'menu_pages',
            'order' => 'sort, id',
            'hint'  => 'Basılı menünün sayfa görselleri. Sıra, kitapçıkta çevrilme sırasıdır — '
                     . 'ilk sayfa kapaktır. Yeni bir menü bastırdığınızda sayfaları buradan değiştirin.',
            'columns' => ['image' => 'Sayfa', 'caption_tr' => 'Açıklama', 'is_active' => 'Yayında', 'sort' => 'Sıra'],
            'fields' => [
                'image'     => ['type' => 'image', 'label' => 'Sayfa görseli', 'folder' => 'menu-book', 'required' => true,
                                'hint' => 'Dikey sayfa. Tüm sayfalar aynı oranda olmalı.'],
                'caption'   => ['type' => 'i18n', 'label' => 'Açıklama', 'hint' => 'Yalnızca erişilebilirlik için; ekranda görünmez.'],
                'is_active' => ['type' => 'checkbox', 'label' => 'Yayında', 'default' => 1],
                'sort'      => ['type' => 'number', 'label' => 'Sıra'],
            ],
        ],

        /* ---- Galeri ------------------------------------------------------ */
        'gallery' => [
            'title' => 'Galeri',
            'table' => 'gallery',
            'order' => 'sort, id',
            'hint'  => 'Galeri sayfasında ve ana sayfanın alt şeridinde görünür.',
            'bulk_upload' => 'image',   // "Toplu yükle": alan adı, açıklamalar sonradan
            'columns' => ['image' => 'Fotoğraf', 'caption_tr' => 'Açıklama', 'is_active' => 'Yayında', 'sort' => 'Sıra'],
            'fields' => [
                'image'     => ['type' => 'image', 'label' => 'Fotoğraf', 'folder' => 'gallery', 'required' => true],
                'caption'   => ['type' => 'i18n', 'label' => 'Açıklama'],
                'is_active' => ['type' => 'checkbox', 'label' => 'Yayında', 'default' => 1],
                'sort'      => ['type' => 'number', 'label' => 'Sıra'],
            ],
        ],

        /* ---- Blog -------------------------------------------------------- */
        'posts' => [
            'title' => 'Blog',
            'table' => 'posts',
            'order' => 'published_at DESC',
            'columns' => ['cover' => 'Kapak', 'title_tr' => 'Başlık', 'published_at' => 'Tarih', 'is_published' => 'Yayında'],
            'fields' => [
                'title'        => ['type' => 'i18n', 'label' => 'Başlık', 'required' => true],
                'slug'         => ['type' => 'slug', 'label' => 'Kısa ad (adres)', 'from' => 'title'],
                'cover'        => ['type' => 'image', 'label' => 'Kapak görseli', 'folder' => 'blog',
                                   'hint' => 'Yatay fotoğraf (1400×900).'],
                'excerpt'      => ['type' => 'i18n_area', 'label' => 'Özet', 'hint' => 'Listelerde ve paylaşımlarda görünen kısa metin.'],
                'body'         => ['type' => 'i18n_html', 'label' => 'İçerik',
                                   'hint' => 'Basit HTML kullanabilirsiniz: &lt;p&gt;, &lt;h3&gt;, &lt;strong&gt;, &lt;ul&gt;&lt;li&gt;, &lt;a href&gt;'],
                'author'       => ['type' => 'text', 'label' => 'Yazar', 'nullable' => true],
                'published_at' => ['type' => 'datetime', 'label' => 'Yayın tarihi'],
                'is_published' => ['type' => 'checkbox', 'label' => 'Yayında', 'default' => 1],
            ],
        ],

        /* ---- Öne çıkanlar ------------------------------------------------ */
        'features' => [
            'title' => 'Öne Çıkanlar',
            'table' => 'features',
            'order' => 'sort, id',
            'hint'  => 'Ana sayfadaki koyu şeritte görünen dört madde.',
            'columns' => ['icon' => 'İkon', 'title_tr' => 'Başlık', 'is_active' => 'Yayında', 'sort' => 'Sıra'],
            'fields' => [
                'icon'      => ['type' => 'select', 'label' => 'İkon', 'options' => 'admin_icon_options'],
                'title'     => ['type' => 'i18n', 'label' => 'Başlık', 'required' => true],
                'text'      => ['type' => 'i18n_area', 'label' => 'Açıklama'],
                'is_active' => ['type' => 'checkbox', 'label' => 'Yayında', 'default' => 1],
                'sort'      => ['type' => 'number', 'label' => 'Sıra'],
            ],
        ],

        /* ---- Yorumlar ---------------------------------------------------- */
        'testimonials' => [
            'title' => 'Müşteri Yorumları',
            'table' => 'testimonials',
            'order' => 'sort, id',
            'hint'  => 'Yalnızca gerçekten alınmış yorumları girin.',
            'columns' => ['name' => 'Kişi', 'role_tr' => 'Sıfat', 'rating' => 'Puan', 'is_active' => 'Yayında', 'sort' => 'Sıra'],
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Ad', 'required' => true],
                'role'      => ['type' => 'i18n', 'label' => 'Sıfat', 'hint' => 'Örn. Müdavim'],
                'quote'     => ['type' => 'i18n_area', 'label' => 'Yorum', 'required' => true],
                'rating'    => ['type' => 'number', 'label' => 'Puan (1–5)', 'default' => 5],
                'avatar'    => ['type' => 'image', 'label' => 'Fotoğraf', 'folder' => 'misc'],
                'is_active' => ['type' => 'checkbox', 'label' => 'Yayında', 'default' => 1],
                'sort'      => ['type' => 'number', 'label' => 'Sıra'],
            ],
        ],

        /* ---- Ekip -------------------------------------------------------- */
        'team' => [
            'title' => 'Ekip',
            'table' => 'team',
            'order' => 'sort, id',
            'columns' => ['photo' => 'Fotoğraf', 'name' => 'Ad', 'role_tr' => 'Görev', 'is_active' => 'Yayında', 'sort' => 'Sıra'],
            'fields' => [
                'name'      => ['type' => 'text', 'label' => 'Ad', 'required' => true],
                'role'      => ['type' => 'i18n', 'label' => 'Görev'],
                'bio'       => ['type' => 'i18n_area', 'label' => 'Kısa tanıtım'],
                'photo'     => ['type' => 'image', 'label' => 'Fotoğraf', 'folder' => 'misc'],
                'is_active' => ['type' => 'checkbox', 'label' => 'Yayında', 'default' => 1],
                'sort'      => ['type' => 'number', 'label' => 'Sıra'],
            ],
        ],
    ];
}

/** Menü ürünü formundaki kategori açılır listesi */
function admin_category_options(): array
{
    $out = [];
    foreach (DB::all('SELECT id, name_tr FROM menu_categories ORDER BY sort, id') as $c) {
        $out[(string) $c['id']] = $c['name_tr'];
    }
    return $out;
}

/* --------------------------------------------------------------------------
 *  Site ayarları — sekmeler hâlinde
 * ----------------------------------------------------------------------- */

function admin_setting_groups(): array
{
    return [
        'genel' => [
            'title' => 'Genel',
            'fields' => [
                'site_name'    => ['type' => 'text', 'label' => 'İşletme adı'],
                'tagline'      => ['type' => 'i18n', 'label' => 'Slogan'],
                'currency'     => ['type' => 'text', 'label' => 'Para birimi simgesi', 'hint' => 'Örn. ₺'],
                'currency_position' => ['type' => 'select', 'label' => 'Simge konumu',
                                        'options' => fn() => ['after' => 'Fiyattan sonra (120 ₺)', 'before' => 'Fiyattan önce (₺ 120)']],
                'public_host'  => ['type' => 'text', 'label' => 'Yayın alan adı',
                                   'hint' => 'QR kodlarının işaret edeceği adres, örn. oldbabyloon.com (https:// ve www olmadan). Boşsa tarayıcıdaki adres kullanılır.'],
            ],
        ],

        'iletisim' => [
            'title' => 'İletişim',
            'fields' => [
                'address'   => ['type' => 'text', 'label' => 'Adres'],
                'hours_confirmed' => ['type' => 'checkbox', 'label' => 'Çalışma saatleri doğrulandı',
                                      'hint' => 'İşaretlenene kadar saatler Google’a (yapılandırılmış veri) gönderilmez. Saatleri “Çalışma Saatleri” ekranında düzeltip sonra işaretleyin.'],
                'phone'     => ['type' => 'text', 'label' => 'Telefon'],
                'whatsapp'  => ['type' => 'text', 'label' => 'WhatsApp numarası'],
                'email'     => ['type' => 'email', 'label' => 'E-posta'],
                'instagram' => ['type' => 'text', 'label' => 'Instagram adresi'],
                'facebook'  => ['type' => 'text', 'label' => 'Facebook adresi'],
                'instagram_tag' => ['type' => 'text', 'label' => 'Instagram etiketi', 'hint' => 'Örn. #etiketiniz'],
                'map_embed' => ['type' => 'raw_html', 'label' => 'Harita gömme kodu',
                                'hint' => 'Google Haritalar → Paylaş → Harita yerleştir → &lt;iframe&gt; kodunu buraya yapıştırın.'],
            ],
        ],

        'icerik' => [
            'title' => 'Sayfa Metinleri',
            'fields' => [
                'about_title' => ['type' => 'i18n', 'label' => 'Hakkımızda başlığı'],
                'about_text'  => ['type' => 'i18n_html', 'label' => 'Hakkımızda metni',
                                  'hint' => 'Paragraflar için &lt;p&gt;…&lt;/p&gt; kullanın.'],
                'about_image' => ['type' => 'image', 'label' => 'Hakkımızda görseli', 'folder' => 'misc',
                                  'hint' => 'Hakkımızda sayfasındaki dikey fotoğraf. Boşsa menüden bir yemek fotoğrafı kullanılır.'],
                'menu_intro'  => ['type' => 'i18n_area', 'label' => 'Menü sayfası açıklaması'],
                'menu_note'   => ['type' => 'i18n_area', 'label' => 'Menü altı notu',
                                  'hint' => 'Alerjen uyarısı, KDV notu vb. QR menüde de görünür.'],
            ],
        ],

        'seo' => [
            'title' => 'SEO',
            'fields' => [
                'seo_title' => ['type' => 'i18n', 'label' => 'Arama motoru başlığı', 'hint' => 'En fazla 60 karakter önerilir.'],
                'seo_desc'  => ['type' => 'i18n_area', 'label' => 'Arama motoru açıklaması', 'hint' => 'En fazla 155 karakter önerilir.'],
            ],
        ],
    ];
}
