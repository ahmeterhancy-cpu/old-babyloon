<?php
declare(strict_types=1);

/**
 * Menü PDF'inden kırpılan fotoğraflar (uploads/menu, uploads/gallery, uploads/slider).
 * Özgün fotoğraf dosyaları gelirse panelden değiştirilir.
 * ob_seed(): ürünlere ada göre bağlanır; galeri ve slaytlar yalnız tablo boşsa eklenir.
 */
return [
    'items' => [
        'BBQ Chicken Wings' => 'uploads/menu/bbq-chicken-wings.jpg',
        'Fried Chicken Wings' => 'uploads/menu/fried-chicken-wings.jpg',
        'Babil Adana Dürüm' => 'uploads/menu/babil-adana-durum.jpg',
        'Izgara Tavuk Pirzola' => 'uploads/menu/izgara-tavuk-pirzola.jpg',
        'Özel Babyloon Çıtır Dürüm' => 'uploads/menu/ozel-babyloon-citir-durum.jpg',
        'Cheddarlı Tavuk Dürüm' => 'uploads/menu/cheddarli-tavuk-durum.jpg',
        'Kıymalı Kaşarlı Dürüm' => 'uploads/menu/kiymali-kasarli-durum.jpg',
        'Cheddarlı Köfte Dürüm' => 'uploads/menu/cheddarli-kofte-durum.jpg',
        'Adana Köfte Dürüm' => 'uploads/menu/adana-kofte-durum.jpg',
        'Kıbrıs Köfte Dürüm' => 'uploads/menu/kibris-kofte-durum.jpg',
        'Karışık Tost' => 'uploads/menu/karisik-tost.jpg',
        'Cheese Burger' => 'uploads/menu/cheese-burger.jpg',
        'Kumru Burger' => 'uploads/menu/kumru-burger.jpg',
        'Meksikan Burger' => 'uploads/menu/meksikan-burger.jpg',
        'Cyprus Burger' => 'uploads/menu/cyprus-burger.jpg',
        'Chef Burger' => 'uploads/menu/chef-burger.jpg',
        'Izgara Kasap Köfte' => 'uploads/menu/izgara-kasap-kofte.jpg',
        'Soslu Tavuk Kanat Izgara' => 'uploads/menu/soslu-tavuk-kanat-izgara.jpg',
        'Tavuk Straganof' => 'uploads/menu/tavuk-straganof.jpg',
        'Izgara Tavuk Bonfile' => 'uploads/menu/izgara-tavuk-bonfile.jpg',
        'Ton Balıklı Sandviç' => 'uploads/menu/ton-balikli-sandvic.jpg',
        'Et Fajita Servis' => 'uploads/menu/et-fajita-servis.jpg',
        'Tavuk Fajita Servis' => 'uploads/menu/tavuk-fajita-servis.jpg',
        'Mantı' => 'uploads/menu/manti.jpg',
        'Alfredo Penne Pasta' => 'uploads/menu/alfredo-penne-pasta.jpg',
        'Spagetti Bolognese' => 'uploads/menu/spagetti-bolognese.jpg',
        'Ton Balıklı Salata' => 'uploads/menu/ton-balikli-salata.jpg',
        'Serpme Kahvaltı' => 'uploads/menu/serpme-kahvalti.jpg',
        'Kahvaltı Tabağı' => 'uploads/menu/kahvalti-tabagi.jpg',
        'Sıcak Kahvaltı' => 'uploads/menu/sicak-kahvalti.jpg',
        'Hellim Izgara (4 dilim)' => 'uploads/menu/hellim-izgara-4-dilim.jpg',
        'Sade Yumurta' => 'uploads/menu/sade-yumurta.jpg',
        'Sade Menemen' => 'uploads/menu/sade-menemen.jpg',
        'Sosisli Omlet' => 'uploads/menu/sosisli-omlet.jpg',
        'Dondurmalı Sufle' => 'uploads/menu/dondurmali-sufle.jpg',
        'Sütlaç' => 'uploads/menu/sutlac.jpg',
        'Trileçe' => 'uploads/menu/trilece.jpg',
        'Kazandibi' => 'uploads/menu/kazandibi.jpg',
        'Karamelli Trileçe' => 'uploads/menu/karamelli-trilece.jpg',
        'Tiramisu' => 'uploads/menu/tiramisu.jpg',
        'Limonlu Cheesecake' => 'uploads/menu/limonlu-cheesecake.jpg',
        'Vanilyalı Krep Pasta' => 'uploads/menu/vanilyali-krep-pasta.jpg',
        'Limonlu Molten Kek' => 'uploads/menu/limonlu-molten-kek.jpg',
        'Karamelize Bisküvili Pasta' => 'uploads/menu/karamelize-biskuvili-pasta.jpg',
        'Fıstık Rüyası' => 'uploads/menu/fistik-ruyasi.jpg',
        'Kremalı Havuçlu Kek' => 'uploads/menu/kremali-havuclu-kek.jpg',
        'Kahveli Krep Pasta' => 'uploads/menu/kahveli-krep-pasta.jpg',
        'Meyveli Magnolya' => 'uploads/menu/meyveli-magnolya.jpg',
        'Muzlu Magnolya' => 'uploads/menu/muzlu-magnolya.jpg',
        'Meyveli Waffle' => 'uploads/menu/meyveli-waffle.jpg',
        'Mozaik Pasta' => 'uploads/menu/mozaik-pasta.jpg',
        'Dondurma' => 'uploads/menu/dondurma.jpg',
        'Dondurmalı Çilek Kasesi' => 'uploads/menu/dondurmali-cilek-kasesi.jpg',
        'Çikolatalı Milkshake' => 'uploads/menu/cikolatali-milkshake.jpg',
        'Lemon Chillers' => 'uploads/menu/lemon-chillers.jpg',
        'Karadut Smoothie' => 'uploads/menu/karadut-smoothie.jpg',
        'Latte Bubble' => 'uploads/menu/latte-bubble.jpg',
        'İtalyan Soda Mavi Melek' => 'uploads/menu/italyan-soda-mavi-melek.jpg',
        'Soya Soslu Tavuk' => 'uploads/menu/soya-soslu-tavuk.jpg',
        'Limon Soslu Tavuk' => 'uploads/menu/limon-soslu-tavuk.jpg',
        'Limon Soslu Et' => 'uploads/menu/limon-soslu-et.jpg',
        'Coconut Cream Chicken' => 'uploads/menu/coconut-cream-chicken.jpg',
        'Grilled Chicken Breast' => 'uploads/menu/grilled-chicken-breast.jpg',
        'Fried Chicken Goujons' => 'uploads/menu/fried-chicken-goujons.jpg',
        'Beef Wrap' => 'uploads/menu/beef-wrap.jpg',
        'Chicken Wrap' => 'uploads/menu/chicken-wrap.jpg',
        'Cream Garlic Chicken Penne' => 'uploads/menu/cream-garlic-chicken-penne.jpg',
        'Cream Garlic Chicken' => 'uploads/menu/cream-garlic-chicken.jpg',
        'BBQ Beef Pizza' => 'uploads/menu/bbq-beef-pizza.jpg',
        'BBQ Chicken Pizza' => 'uploads/menu/bbq-chicken-pizza.jpg',
        'Babil İskender' => 'uploads/menu/babil-iskender.jpg',
        'Babil Acılı Et' => 'uploads/menu/babil-acili-et.jpg',
        'Soya Soslu Et' => 'uploads/menu/soya-soslu-et.jpg',
        // İçecekler — Unsplash (unsplash.com/license: ücretsiz, atıf gerekmez); temsilîdir.
        // Kaynak: https://unsplash.com/photos/<kimlik>
        'Strawberry Chillers' => 'uploads/menu/icecek-strawberry-chillers.jpg', // jF4irn8mxhQ
        'Melon Chillers' => 'uploads/menu/icecek-melon-chillers.jpg', // KUT4HK3-Z5s
        'Black Berry Chillers' => 'uploads/menu/icecek-black-berry-chillers.jpg', // GPcoF-s6_DI
        'Muzlu Milkshake' => 'uploads/menu/icecek-muzlu-milkshake.jpg', // JfWhrxbmF-U
        'Çilekli Milkshake' => 'uploads/menu/icecek-cilekli-milkshake.jpg', // DlhfsnrX2es
        'Vanilyalı Milkshake' => 'uploads/menu/icecek-vanilyali-milkshake.jpg', // OaGUHIjCdCs
        'Karamelli Milkshake' => 'uploads/menu/icecek-karamelli-milkshake.jpg', // v1ngQt4Z0cY
        'Çilekli Smoothie' => 'uploads/menu/icecek-cilekli-smoothie.jpg', // rwBJaJdesGg
        'Karamel Smoothie' => 'uploads/menu/icecek-karamel-smoothie.jpg', // DoVbQbclR1s
        'Limonatalı Bubble' => 'uploads/menu/icecek-limonatali-bubble.jpg', // cLmCH3aygHk
        'Portakallı Bubble' => 'uploads/menu/icecek-portakalli-bubble.jpg', // G7k32lcgbQI
        'Milkshake Bubble' => 'uploads/menu/icecek-milkshake-bubble.jpg', // QN66qNwc1n8
        'Enerji İçecekli Bubble' => 'uploads/menu/icecek-enerji-icecekli-bubble.jpg', // dHQQv-BKTjo
        'Coca Cola' => 'uploads/menu/icecek-coca-cola.jpg', // Qvnohn4GyJA
        'Coca Cola Zero' => 'uploads/menu/icecek-coca-cola-zero.jpg', // 5Es8l0nyRrI
        'Fanta' => 'uploads/menu/icecek-fanta.jpg', // nJguJaHo5dg
        'Sprite' => 'uploads/menu/icecek-sprite.jpg', // oaE6Zllcc6Y
        'Cappy' => 'uploads/menu/icecek-cappy.jpg', // AjG1BkDH4Zs
        'Fuse Tea' => 'uploads/menu/icecek-fuse-tea.jpg', // CCowelQ2pLw
        'Soda' => 'uploads/menu/icecek-soda.jpg', // TWIRIAizZFU
        'Meyveli Soda' => 'uploads/menu/icecek-meyveli-soda.jpg', // n90TWXJN4Uk
        'İtalyan Soda' => 'uploads/menu/icecek-italyan-soda.jpg', // Aeo9rdPnvXQ
        'İtalyan Soda Çilekli' => 'uploads/menu/icecek-italyan-soda-cilekli.jpg', // bIG6LAWRA68
        'İtalyan Soda Karpuzlu' => 'uploads/menu/icecek-italyan-soda-karpuzlu.jpg', // 21QZGQKpOYE
        'İtalyan Soda Karadutlu' => 'uploads/menu/icecek-italyan-soda-karadutlu.jpg', // VsAV_HjJpDc
        'Küçük Su' => 'uploads/menu/icecek-kucuk-su.jpg', // edBR3b2JAuA
        'Büyük Su' => 'uploads/menu/icecek-buyuk-su.jpg', // N-MqWXXZvNY
        'Ballı Muzlu Süt' => 'uploads/menu/icecek-balli-muzlu-sut.jpg', // dj0Np1jooWk
        'Limonata' => 'uploads/menu/icecek-limonata.jpg', // TecD-1MTMiE
        'Çilekli Limonata' => 'uploads/menu/icecek-cilekli-limonata.jpg', // RXP4tr0isG0
        'Red Bull' => 'uploads/menu/icecek-red-bull.jpg', // UuogLBRwG7A
        'Meksikan Red Bull' => 'uploads/menu/icecek-meksikan-red-bull.jpg', // KZQcIuo5sFU
        'Çorcil' => 'uploads/menu/icecek-corcil.jpg', // mZT5bMpmDGQ
        'Taze Portakal Suyu' => 'uploads/menu/icecek-taze-portakal-suyu.jpg', // IgGFNg-xPJs
        'Ayran' => 'uploads/menu/icecek-ayran.jpg', // 0sz-sfC_ekc
        'Strawberry Iced Chocolate' => 'uploads/menu/icecek-strawberry-iced-chocolate.jpg', // L3okRM9e5AE
        'Karamel Iced Chocolate' => 'uploads/menu/icecek-karamel-iced-chocolate.jpg', // OSYt_g-EoPE
        'Coconut Iced Chocolate' => 'uploads/menu/icecek-coconut-iced-chocolate.jpg', // TaCNAGSC9g4
        "Cookie's Iced Chocolate" => 'uploads/menu/icecek-cookie-s-iced-chocolate.jpg', // 07lv4mlyHjA
        'Orjinal Iced Chocolate' => 'uploads/menu/icecek-orjinal-iced-chocolate.jpg', // BpcTCHoruSo
        'Iced Latte' => 'uploads/menu/icecek-iced-latte.jpg', // vZOZJH_xkUk
        'Iced Vanilyalı Latte' => 'uploads/menu/icecek-iced-vanilyali-latte.jpg', // _tSgUmeYMm8
        'Iced Karamelli Latte' => 'uploads/menu/icecek-iced-karamelli-latte.jpg', // 1e5V69AQjgA
        'Iced Mocha' => 'uploads/menu/icecek-iced-mocha.jpg', // F0Wd4djYvSA
        'Iced Karamel Mocha' => 'uploads/menu/icecek-iced-karamel-mocha.jpg', // 5pb87TNugV0
        'Iced Americano' => 'uploads/menu/icecek-iced-americano.jpg', // vP9TjdIm9fE
        'Iced Cappuccino' => 'uploads/menu/icecek-iced-cappuccino.jpg', // ZwnAW9dFAuI
        'Iced Nescafe' => 'uploads/menu/icecek-iced-nescafe.jpg', // L-sm1B4L1Ns
        'Iced Frappe Latte' => 'uploads/menu/icecek-iced-frappe-latte.jpg', // mMI5sdLFoHM
        "Iced Cookie's Frappe Latte" => 'uploads/menu/icecek-iced-cookie-s-frappe-latte.jpg', // 4FujjkcI40g
        'Frappuccino' => 'uploads/menu/icecek-frappuccino.jpg', // 8wESIey6sYQ
        'Affogato' => 'uploads/menu/icecek-affogato.jpg', // kRS7qyKfVhY
        'Kowa Mint' => 'uploads/menu/icecek-kowa-mint.jpg', // aM3LR10BeAc
        'Choco - Coco' => 'uploads/menu/icecek-choco-coco.jpg', // e0cEh3LURnI
        'Demleme Çay Cam Bardak' => 'uploads/menu/icecek-demleme-cay-cam-bardak.jpg', // _tLxpvQtrDE
        'Demleme Çay Fincan' => 'uploads/menu/icecek-demleme-cay-fincan.jpg', // wn6CTPy5E6Q
        'Lipton Bitki Çayları' => 'uploads/menu/icecek-lipton-bitki-caylari.jpg', // qEcWgrTG578
        'Türk Kahvesi' => 'uploads/menu/icecek-turk-kahvesi.jpg', // qdFxL5PoQhE
        'Double Türk Kahvesi' => 'uploads/menu/icecek-double-turk-kahvesi.jpg', // dgmaFq0dorU
        'Con Türk Kahvesi' => 'uploads/menu/icecek-con-turk-kahvesi.jpg', // k5noya9O1Ss
        'Oza Türk Kahvesi' => 'uploads/menu/icecek-oza-turk-kahvesi.jpg', // 5QA1oWxgBIE
        'Damla Sakızlı Türk Kahvesi' => 'uploads/menu/icecek-damla-sakizli-turk-kahvesi.jpg', // vnSsWtWd8po
        'Sütlü Türk Kahvesi' => 'uploads/menu/icecek-sutlu-turk-kahvesi.jpg', // 4EGqBvjPllQ
        'Double Espresso' => 'uploads/menu/icecek-double-espresso.jpg', // ncqD9jpQqAk
        'Espresso Macchiato' => 'uploads/menu/icecek-espresso-macchiato.jpg', // IhqDpFz7I8Q
        'Sütlü Nescafe' => 'uploads/menu/icecek-sutlu-nescafe.jpg', // WZGdx3K3_tw
        'Sade Nescafe' => 'uploads/menu/icecek-sade-nescafe.jpg', // LWvmO-F3Fr0
        'Filtre Kahve' => 'uploads/menu/icecek-filtre-kahve.jpg', // WbdkFHDFbTg
        'Americano' => 'uploads/menu/icecek-americano.jpg', // dAYJfrtVjh0
        'Cafe Latte' => 'uploads/menu/icecek-cafe-latte.jpg', // kewGXkvZFE4
        'Karamelli Latte' => 'uploads/menu/icecek-karamelli-latte.jpg', // VkUP6wWqSvw
        'Vanilyalı Latte' => 'uploads/menu/icecek-vanilyali-latte.jpg', // hmLY7GiNFyE
        'Chai Tea Latte' => 'uploads/menu/icecek-chai-tea-latte.jpg', // vfiA7rRtjWo
        'Sıcak Çikolata' => 'uploads/menu/icecek-sicak-cikolata.jpg', // NtmNhrdfs-o
        'Karamelli Sıcak Çikolata' => 'uploads/menu/icecek-karamelli-sicak-cikolata.jpg', // 3w2AuRZeeSU
        'Cafe Mocha' => 'uploads/menu/icecek-cafe-mocha.jpg', // gXtvTOs4tzg
        'Karamelli Cafe Mocha' => 'uploads/menu/icecek-karamelli-cafe-mocha.jpg', // e-8xxuZfSMw
        'Nutella Mocha' => 'uploads/menu/icecek-nutella-mocha.jpg', // RtHw0PWCLhw
        'White Chocolate Mocha' => 'uploads/menu/icecek-white-chocolate-mocha.jpg', // P1QAEVLRldE
        'Sahlep' => 'uploads/menu/icecek-sahlep.jpg', // QYjW3EFToBQ
        'Ballı Süt' => 'uploads/menu/icecek-balli-sut.jpg', // thQVDGQFvDw
        'Süt' => 'uploads/menu/icecek-sut.jpg', // c6TKtsi8C1k
        'Demleme Organik Yeşil Çay' => 'uploads/menu/icecek-demleme-organik-yesil-cay.jpg', // 3hRRT4qztzs
        'Demleme Ada Çayı' => 'uploads/menu/icecek-demleme-ada-cayi.jpg', // G3Y8KVjpl1M
    ],
    'gallery' => [
        ['uploads/gallery/p08-1.jpg', 'Babil İskender'],
        ['uploads/gallery/p17-1.jpg', 'Kumru Burger'],
        ['uploads/gallery/p02-1.jpg', 'Soya Soslu Tavuk'],
        ['uploads/gallery/p25-1.jpg', 'Mantı'],
        ['uploads/gallery/p20-1.jpg', 'Izgara Kasap Köfte'],
        ['uploads/gallery/p05-1.jpg', 'Beef Wrap'],
        ['uploads/gallery/p24-1.jpg', 'Et Fajita Servis'],
        ['uploads/gallery/p27-1.jpg', 'Serpme Kahvaltı'],
        ['uploads/gallery/p16-1.jpg', 'Cheese Burger'],
        ['uploads/gallery/p26-2.jpg', 'Spagetti Bolognese'],
        ['uploads/gallery/p28-3.jpg', 'Hellim Izgara (4 dilim)'],
        ['uploads/gallery/p33-2.jpg', 'Dondurmalı Çilek Kasesi'],
        ['uploads/gallery/p07-1.jpg', 'BBQ Beef Pizza'],
        ['uploads/gallery/p30-1.jpg', 'Dondurmalı Sufle'],
        ['uploads/gallery/p11-1.jpg', 'Babil Adana Dürüm'],
        ['uploads/gallery/p32-1.jpg', 'Meyveli Magnolya'],
        ['uploads/gallery/p12-1.jpg', 'Özel Babyloon Çıtır Dürüm'],
        ['uploads/gallery/p35-1.jpg', 'Latte Bubble'],
        ['uploads/gallery/p37-1.jpg', null],
        ['uploads/gallery/p38-1.jpg', null],
    ],
    'slides' => ['uploads/slider/hero-1.jpg', 'uploads/slider/hero-2.jpg'],
];
