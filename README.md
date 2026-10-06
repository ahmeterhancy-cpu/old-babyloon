# Old Babyloon

Kıbrıs'taki Old Babyloon için iki dilli (TR/EN) tanıtım sitesi, yönetim paneli ve
telefondan okunan QR menü.

Çerçeve kullanılmadı: düz PHP 8, tek giriş noktası, sıfır JavaScript kütüphanesi.
Turhost gibi paylaşımlı bir sunucuya klasörü kopyalayıp bir dosya oluşturmak yeterli.

> **Bu proje Dura Coffee'nin kod tabanından türetildi.** Devralınan şey altyapı:
> yönlendirme, veritabanı katmanı, yönetim paneli, elle yazılmış QR üreteci,
> devinim motoru ve menü kitapçığı. Dura'ya ait olan hiçbir şey taşınmadı —
> ne logo, ne palet, ne de içerik. (Dura'nın örnek veri dosyası da depodan
> çıkarıldı; bkz. DEPLOY.md → depo geçmişi.)

---

## İçindekiler

- [Hızlı başlangıç (yerel)](#hızlı-başlangıç-yerel)
- [Sunucuya kurulum (cPanel + Git)](#sunucuya-kurulum-cpanel--git)
- [Yönetim paneli](#yönetim-paneli)
- [QR menü](#qr-menü)
- [Menü kitapçığı](#menü-kitapçığı)
- [Instagram akışı](#instagram-akışı)
- [Marka](#marka)
- [Dosya düzeni](#dosya-düzeni)
- [Testler](#testler)
- [Devralan için notlar](#devralan-için-notlar)

---

## Hızlı başlangıç (yerel)

```bash
php migrate.php
php -S localhost:8145 -t . router-dev.php
```

- Site: <http://localhost:8145>
- Panel: <http://localhost:8145/admin> — `admin` / `babyloon2026` (yalnız yerel;
  canlıda şifre sunucudaki `babyloon-config.php` → `admin_password`)

Yerelde veritabanı `storage/database.sqlite` dosyasıdır; ayrı bir sunucu gerekmez.

Sıfırdan kurmak (**tüm veriyi siler**):

```bash
php migrate.php --fresh
```

---

## Sunucuya kurulum (cPanel + Git)

Ayrıntılı adımlar: **[DEPLOY.md](DEPLOY.md)**. Özet:

1. cPanel'de MySQL veritabanı + kullanıcı.
2. `public_html/babyloon-config.php` elle oluşturulur (depoda yok): veritabanı
   bilgileri, `'env' => 'production'` ve ilk kurulum için `'admin_password'`.
3. Git Version Control ile depo klonlanır; **Update from Remote → Deploy HEAD
   Commit**. `.cpanel.yml` dosyaları `public_html`'e kopyalar, `migrate.php`'yi
   (güvenli, veri silmez) çalıştırır, eksik görsel türevlerini üretir.
4. Günlük: `~/deploy-son.log`.

### Kurulumdan sonra kontrol listesi

- [ ] `migrate.php`, `babyloon-config.php` tarayıcıdan 404 veriyor (dağıtım `migrate.php`'yi korur, `.htaccess` kapatır)
- [ ] Panele `admin_password` ile girildi; istenirse Kullanıcılar'dan değiştirildi
- [ ] Site Ayarları → **Yayın alan adı** `oldbabyloon.com` (kurulumda dolu gelir; QR kodları buraya işaret eder)
- [ ] Gerçek adres, telefon, WhatsApp, e-posta girildi
      (telefon başlıktaki butonda görünür — boşsa buton çıkmaz)
- [ ] Menü ve fiyatlar güncel mi (kurulumdaki menü müşterinin 03.08.2026 tarihli PDF menüsünden alındı)
- [ ] Çalışma saatleri düzeltildi ve Site Ayarları → İletişim → “Çalışma saatleri doğrulandı” işaretlendi
- [ ] `uploads/` klasörü yazılabilir (755 ya da 775)
- [ ] SSL kurulu (`.htaccess` https'e ve www'suz adrese zorlar; sertifika yoksa site açılmaz)

### Sunucu gereksinimleri

| Gereksinim | Neden |
|---|---|
| PHP 8.1+ | `match`, isimli argümanlar, `str_contains` |
| PDO (mysql veya sqlite) | veritabanı |
| GD eklentisi | görsel küçültme, QR PNG (yoksa QR yalnızca SVG olarak iner) |
| mbstring | Türkçe karakterler |
| cURL *(isteğe bağlı)* | yalnızca Instagram akışı için |
| `mod_rewrite` | temiz adresler |

> **Not:** `mbstring` kapalıysa site Türkçe karakterlerde bozulur. Turhost'ta
> genelde açıktır; değilse cPanel → PHP Seçicisi'nden etkinleştirin.

---

## Yönetim paneli

`/admin` adresinden girilir. Sol menüdeki her başlık aynı düzeni izler:
listele → düzenle → kaydet.

| Bölüm | Ne işe yarar |
|---|---|
| **Ana Sayfa Slaytları** | En üstteki dönen görseller |
| **Menü Kategorileri** | Espresso Bar, Tatlılar… sırası ve ikonları |
| **Menü Ürünleri** | Ürün, fiyat, fotoğraf, etiket, "serviste mi" |
| **Galeri** | Galeri sayfası ve Instagram şeridinin yedeği |
| **Günlük (Blog)** | Yazılar |
| **Öne Çıkanlar / Yorumlar / Ekip** | Ana sayfa ve Hakkımızda bölümleri |
| **Çalışma Saatleri** | "Şu an açık/kapalı" göstergesini besler |
| **Mesajlar** | İletişim formundan gelen mesajlar |
| **QR Menü Kodu** | Baskıya hazır kart, SVG/PNG indirme, okutma sayısı |
| **Instagram Akışı** | Ana sayfadaki Instagram şeridi |
| **Site Ayarları** | İletişim, metinler, SEO |

Birkaç davranış bilinçli seçildi:

- **Tükenen ürünü silmeyin.** "Serviste" kutusunun işaretini kaldırın; menüde üstü
  çizili ve "Tükendi" etiketiyle görünür, fiyat geçmişi korunur.
- **Sıralama** liste ekranındaki numaralarla yapılır (küçük numara üstte).
  Sürükle-bırak yok — çünkü telefondan da çalışması gerekiyor.
- **Görseller** yüklenirken 2000 pikselin altına indirilir; 4 MB'lık telefon
  fotoğrafı yüklemek sorun değil.
- **Silme** yalnızca düzenleme ekranının altındaki kırmızı bölümden yapılır ve onay ister.

### İki dil

Her metin alanı TR ve EN kutusu olarak gelir. **EN boş bırakılırsa İngilizce
sayfada Türkçe metin görünür** — yani hiçbir yer boş kalmaz, ama çeviriyi
zamanla tamamlamak gerekir.

---

## QR menü

İşletmenin **tek bir menü kodu** vardır: `https://oldbabyloon.com/qr/menu`. Aynı kod menü
kapağına, tezgâha, vitrine ve masalara basılır. Masa başına ayrı kod üretilmez.

- Müşteri kodu okutur → telefon diline göre TR ya da EN menüye yönlendirilir.
- QR menü sitenin geri kalanından ayrı, hafif bir sayfadır: arama kutusu,
  yapışkan kategori şeridi, karanlık ortamda otomatik koyu tema.
- Toplam okutma sayısı panelde görünür.

Panelden **Baskıya hazır kart** bağlantısı A6 boyutunda bir kart açar
(tarayıcıdan Yazdır → A6). SVG ve PNG olarak da indirilebilir.
**Matbaaya SVG verin** — büyütünce bozulmaz. Kod en az 2,5 cm basılmalı ve
çevresinde boşluk bırakılmalıdır.

Menü kodu panelden **silinemez** (her yere basıldığı için kazara kaldırılmasın).
Nereden okunduğunu ayrı saymak isterseniz — örneğin vitrindeki afiş — ya da kodun
doğrudan bir kategoriye gitmesini isterseniz, aynı sayfadaki **Ek kodlar**
bölümünden ikinci bir kod oluşturabilirsiniz. Çoğu işletmenin buna ihtiyacı olmaz.

> QR kodları kendi kodumuzla üretilir (`app/lib/qrcode.php`), dış servis yok.
> Kodlar internet olmadan da üretilir ve hiçbir veri dışarı çıkmaz.

**Kod bastıktan sonra kısa adı değiştirmeyin** — basılı kartlar çalışmaz hâle gelir.

---

## Dijital menü

Sitede ürün ürün listelenen bir menü sayfası **yoktur**. Basılı menünün
kendisi gösterilir: `/tr/menu` (EN: `/en/menu`) adresinde sayfa çevrilerek
okunur. Aynı menüye telefonda hızlı arama için QR menü (`/qr-menu`) vardır.

- Gerçek kitap mantığı: her yaprak iki yüzlü, sağdan sola çevrilir.
  Masaüstünde iki sayfa yan yana, dar ekranda tek sayfa.
- Kenara tıklama, alttaki düğmeler, klavye ok tuşları ve dokunmatik kaydırma.
- **JS kapalıysa** sayfalar alt alta listelenir (`<noscript>`), menü yine okunur.

### Panelden güncelleme

**Dijital Menü** ekranı ikisini bir arada yönetir:

- **PDF yükle** — sitedeki "PDF olarak indir" bağlantısı bunu sunar.
  Dosya uzantısına değil içeriğine bakılarak doğrulanır.
- **Sayfa görsellerini PDF'ten üret** — sunucuda dönüştürücü varsa aynı
  adımda yapılır. Sayfalar önce geçici klasöre üretilir; yayındaki menüye
  ancak iş bitince dokunulur, yarım kalan dönüştürme menüyü boş bırakmaz.

Dönüştürücü olarak sırayla Imagick, Poppler (`pdftoppm`/`pdftocairo`) ve
Ghostscript aranır. **Paylaşımlı barındırmada hiçbiri olmayabilir** — o
durumda PDF yine yüklenir, yalnızca sayfa görselleri elle yüklenir. Panel
hangisinin bulunduğunu (ya da bulunmadığını) ekranda yazar.

Sayfa sırası çevrilme sırasıdır; ilk sayfa kapaktır. Tek tek düzenlemek
için ekrandaki **Sayfaları düzenle** bağlantısı kullanılır.

Sayfaları yerelde üretmek gerekirse:

```bash
pdftoppm -jpeg -r 300 "menu.pdf" uploads/menu-book/sayfa
```

---

## Instagram akışı

Ana sayfanın altındaki şerit iki şekilde beslenir:

1. **Otomatik** — Meta'dan alınan bir erişim jetonuyla gönderiler çekilir.
   Görseller sunucuya indirilir (Instagram'ın adresleri birkaç saatte geçersizleşir).
2. **Elle** — panelden fotoğraf yükleyip gönderi bağlantısı yapıştırırsınız.

Jeton yoksa şerit galeri fotoğraflarını gösterir; site hiçbir zaman boş kalmaz.

Jeton alma adımları panelin **Instagram Akışı** sayfasında yazılıdır. Jeton 60 gün
geçerlidir; süresi dolmadan aynı sayfadaki **"Jetonu 60 gün uzat"** düğmesine basın.

> Paylaşımlı sunucularda cURL bazen kök sertifika bulamaz ("cURL 60" hatası).
> Bu durumda barındırıcıdan `curl.cainfo` ayarını isteyin ya da
> [cacert.pem](https://curl.se/ca/cacert.pem) dosyasını kök dizine
> `babyloon-cacert.pem` adıyla koyun.

---

## Marka

Kaynak: müşterinin gönderdiği logo (siyah zemin, sarı yazı, iyon başlıklı
sütun + "B" işareti). Ayrı bir kurumsal kimlik kılavuzu yok; aşağıdakiler
logodan türetildi.

| Ne | Nerede | Not |
|---|---|---|
| Logo | `assets/img/logo.svg` | Logodan vektöre çevrildi. `fill="currentColor"` — rengi CSS verir (başlıkta sarı, açık zeminde siyah) |
| Logo, sabit sarı | `assets/img/logo-white.svg` | Altbilgi ve siyah zeminler için |
| İşaret (B + sütun) | `assets/img/emblem.svg` | Küçük alanlar için |
| Favicon | `assets/img/favicon.svg` | Siyah kare üstünde sarı işaret |
| Sosyal paylaşım kapağı | `assets/img/og-cover.jpg`, `og-cover.svg` | Siyah zemin, ortada logo |

### Renkler

| Jeton | HEX | Kural |
|---|---|---|
| `--yellow` | `#FDF001` | Logonun sarısı. **Yalnızca siyah zeminde ya da üstünde siyah yazı olan dolguda** (ana düğme, etkin dil, "Yeni" etiketi). Beyaz üstünde sarı yazı/ikon kullanılmaz — 1,1:1. |
| `--black` | `#12100C` | Başlık çubuğu, altbilgi, açık zemindeki vurgu ve ikonlar |
| `--cream` | `#F2EEE3` | Sıcak panel zemini, görsel yer tutucu |
| `--sand` | `#F7F5EE` | Yumuşak bölüm zemini |
| `--muted` | `#625D51` | İkincil metin |

`--terra*` adları Dura iskeletinden kaldı; değerleri artık siyah/sarı
rollerindedir (`app.css` başındaki açıklamaya bakın). QR menünün koyu
temasında vurgu sarıya döner.

### Tipografi

Başlıklar **Playfair Display** (referans Tastyc'in başlık yazı tipi; Bodoni kalın
kesimde kılcal kaldığı için bırakıldı), gövde **Montserrat**.
İkisi de `assets/fonts/` içinden, `latin` + `latin-ext` alt kümeleriyle
servis ediliyor; Google Fonts'a bağlanılmıyor.


### Tasarım dili (sayfa düzeni)

Referans: Tastyc (bslthemes, restoran teması) — müşterinin isteğiyle.

- **Zemin ve kart:** koyu, hafif dokulu zemin (`.is-cards` gövde sınıfı)
  üstünde beyaz, yuvarlak köşeli kartlar (`.hcard`); bölümler noktalı
  çizgiyle (`.hdiv`) ayrılır.
- **Başlık:** her sayfada sabit, yüzen beyaz kart (`.hdr__row`). İç sayfaların
  başı koyu bant (`.phead`); hata sayfaları da (`.perr`). QR menüde aynı
  kart, altında yapışkan kategori şeridi.
- **Ana sayfa:** tam ekran açılış → tanıtım → dört madde → saat kartı →
  QR bandı → menü haritası → basılı menü →
  galeri.
- **İçerik kuralı:** yorum, sayaç, slogan uydurulmaz; metinler menüde
  yazanlardan türetilir.
- **Ortak parçalar:** `views/site/_hours_card.php` (saat kartı),
  `site_menu_cats()` (menü haritası verisi).
- Saatler panelde **Site Ayarları → İletişim → “Çalışma saatleri doğrulandı”**
  işaretlenmeden yapılandırılmış veriye (Google) yazılmaz.
- Blog yazısı yokken Blog bağlantıları (menü, alt bilgi, site haritası)
  gizlenir; ilk yazı yayımlanınca kendiliğinden görünür.

## Dosya düzeni

```
app/
  config.php        yapılandırma (babyloon-config.php varsa onu kullanır)
  db.php            ince PDO sarmalayıcı (sqlite + mysql)
  helpers.php       çeviri, ayarlar, görsel, oturum, CSRF, ortak veri
  routes.php        yönlendirme, dil önekleri, sitemap, robots
  site.php          genel sayfaların denetleyicileri
  admin.php         panel: giriş, CRUD motoru, QR, Instagram, ayarlar
  admin_fields.php  panelin veri tanımları — yeni içerik türü buraya eklenir
  seed_data.php     ilk kurulum içeriği
  seed_menu.php     menü (PDF'ten yazıya dökülen 251 ürün, TR/EN)
  seed_photos.php   PDF'ten kırpılan ürün/galeri/slayt fotoğrafları
  seed_about.php    Hakkımızda metni, slogan, SEO başlık/açıklama
  lang/             tr.php, en.php
  lib/
    qrcode.php      bağımlılıksız QR üreteci (ISO/IEC 18004)
    instagram.php   Instagram Graph API istemcisi + yerel önbellek
  views/            şablonlar (layout / site / menu / admin)
assets/             css, js, ikonlar, logo
uploads/            yüklenen görseller
storage/            SQLite veritabanı (yerel)
tests/              qrcode_test.php, smoke.php
```

Yeni bir içerik türü eklemek için `admin_fields.php` içine bir kayıt yazmak
yeterlidir; liste, form, doğrulama, görsel yükleme ve silme genel motordan gelir.

---

## Testler

```bash
php tests/qrcode_test.php   # QR üreteci — sunucu gerekmez
php tests/assets_test.php   # CSS/JS bütünlüğü, marka varlıkları — sunucu gerekmez
php tests/smoke.php         # panel + genel sayfalar, 404, JSON-LD — çalışan sunucu (:8145) gerekir
```

`assets_test.php` gerçek bir olaydan doğdu: bir CSS bloğu düzenlenirken başıboş
bir `}` kaldı ve tarayıcı o noktadan sonraki bütün kuralları sessizce atladı.
Sayfa hatasız görünüyordu, yalnızca stiller uygulanmıyordu.

`qrcode_test.php` beklenen değerleri bağımsız bir kodlayıcıyla doğrulanmıştır;
QR tablolarına dokunulursa test kırılır. Üretilen kodlar geliştirme sırasında
203 farklı metin/sürüm/hata-düzeltme kombinasyonunda gerçek bir okuyucuyla
(jsQR) okunarak teyit edildi.

---

## Devralan için notlar

**Fotoğraflar menü PDF'inden kırpıldı.** Sayfalar PDF'e tam sayfa görsel
olarak gömülü; ürün fotoğrafları tek tek kırpıldı (73 ürün, 20 galeri,
2 slayt). Çözünürlükleri sınırlı — özgün dosyalar gelirse panelden değiştirin.
Kategori kapak görseli (Menü Kategorileri → görsel) menü haritasında ürün
fotoğrafının önüne geçer; Pizzalar'ın kapağı böyle verildi.

**Menü PDF'i** (`uploads/menu-book/menu.pdf`) 1400 piksellik sayfa
görsellerinden yeniden üretildi: 32 MB → 7 MB. Panelin PDF sınırı 25 MB.

**Lighthouse (yerel, mobil, 2026-10-01):** erişilebilirlik / en iyi
uygulamalar / SEO 100; performans 75. Kalan kaybın çoğu yerel PHP
sunucusunun sıkıştırma ve önbellek başlığı göndermemesi — canlıda
`.htaccess` ikisini de açıyor. QR menünün SEO puanı bilerek düşük:
sayfa `noindex` (menü kitapçık sayfasında dizinleniyor).

**Menü gerçek.** 13 kategori, 251 ürün ve fiyatlar müşterinin 40 sayfalık PDF
menüsünden (`babyloon-03.08.2026-1.pdf`) yazıya döküldü: `app/seed_menu.php`.
PDF'teki 30'dan fazla bölüm QR menüde okunabilsin diye 13 kategoride
toplandı; asıl bölümler `group_tr/group_en` alt başlıkları olarak duruyor.
Yalnızca bariz yazım hataları düzeltildi (Spirite → Sprite, Blognese →
Bolognese…); fiyatlara dokunulmadı. Ürün adlarının İngilizcesi girildi
(Türk mutfağına özgü adlar korunup açıklandı: "Mantı (Turkish Dumplings)")
ve 87 ürün açıklamasının tamamı İngilizceye çevrildi ("cips" = chips). Fiyatı basılmamış kalemlerde (Jibiar nargileleri) rakam
yerine ince çizgi görünür. Aroma listesi ve oyunlar kategori açıklamasında.

**Görsel kararlar logoya bağlı.** Renk, font ya da logo kullanımını
değiştirmeden önce yukarıdaki [Marka](#marka) bölümünü okuyun.

**Zaman dilimi `Asia/Famagusta`.** KKTC yaz/kış saatini Türkiye'den ayrı uygular;
`Europe/Istanbul` ya da `Europe/Nicosia` kullanmayın (`app/config.php`).

**Bilinçli olarak yapılmayanlar:**

- Online satış / sepet yok — site tanıtım + menü odaklı.
- **Rezervasyon yok** — müşteri istemedi. İletişim formu ve telefon yeterli.
- QR menüden sipariş verilmiyor; menü yalnızca görüntülenir.
- Blog yorumları, üyelik, bülten aboneliği yok.

**Dikkat edilecek yerler:**

- `safe_html()` yönetici metinlerini sınırlı bir HTML alt kümesine indirger.
  Yeni bir etiket gerekiyorsa listeyi oradan genişletin.
- `uploads/` içinde PHP çalıştırma `.htaccess` ile kapatılmıştır; bu dosyayı silmeyin.
- Oturum çerezi `oldbabyloon`; aynı alan adında başka bir PHP uygulaması varsa
  çerez adını değiştirin.
