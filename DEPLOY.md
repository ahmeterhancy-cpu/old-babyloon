# Old Babyloon — cPanel'e Git ile kurulum (oldbabyloon.com)

Genel tarif: `F:\Yazılımlar\CPANEL-GIT-DEPLOY.md` (Laravel için yazıldı). Bu
proje çerçevesiz PHP olduğu için çok daha sade: composer, vendor, artisan,
`.env`, `APP_DIR` yok. Site doğrudan `public_html` içinde çalışır.

```
~/repositories/old-babyloon/   ← cPanel'in klonladığı depo (dokunma)
~/public_html/                 ← site (dağıtım buraya kopyalar)
├── index.php  .htaccess  migrate.php
├── app/        (web'e kapalı — app/.htaccess)
├── assets/  uploads/  tools/ (tools web'e kapalı)
└── babyloon-config.php        ← ELLE oluşturulur, depoda YOK, dağıtım dokunmaz
~/deploy-son.log               ← her dağıtımın günlüğü
```

---

## 0. Önce öğren

| Soru | Neden |
|---|---|
| PHP sürümü (MultiPHP **ve** PHP Selector) | En az 8.1 gerekir |
| PHP eklentileri: `pdo_mysql`, `gd` (+ WebP), `mbstring` | GD yoksa görsel türevleri üretilmez (site yine çalışır) |
| `public_html` boş mu? | Hosting'in varsayılan `index.html`'i varsa silinmeli |
| Alan adı bu hesaba bağlı mı, SSL kurulu mu? | `.htaccess` https'e zorlar; SSL yoksa site açılmaz |

---

## 1. Depo (GitHub)

- cPanel klon adresinde parola kabul etmiyor → depo pratikte **public**.
- Public yapmadan önce kontrol edildi (2026-10-06):
  - Veritabanı şifresi, yönetici şifresi depoda **yok** (`babyloon-config.php`
    `.gitignore`'da; yönetici şifresi o dosyadan okunur).
  - Yerel varsayılan şifre `babyloon2026` README'de ve testte geçer — canlıda
    **kullanılmaz** (`env=production` iken varsayılan şifreyle yönetici açılmaz).
  - ⚠️ **Git geçmişinde Dura Coffee'nin gerçek verisi var**
    (`app/seed_data_dura_ornek.php`: adres, telefon, ekip adları, yorumlar).
    Dosya silindi ama ilk commit'lerde duruyor. Public depoya **geçmişsiz** gönder
    (tek commit'lik yeni dal) — bkz. aşağıdaki komutlar.

```bash
# Geçmişsiz, tek commit'lik yayın dalı
git checkout --orphan yayin
git commit -m "Old Babyloon — ilk yayın"
git remote add origin https://github.com/ahmeterhancy-cpu/old-babyloon.git
git push -u origin yayin:main
```

---

## 2. Sunucuda bir kez

1. **MySQL** → cPanel → MySQL Veritabanları: veritabanı + kullanıcı, tüm yetkiler.
2. **`public_html/babyloon-config.php`** (Dosya Yöneticisi → Show Hidden Files):

   ```php
   <?php return [
       'env'            => 'production',
       'debug'          => false,
       'admin_password' => 'GÜÇLÜ-BİR-ŞİFRE',   // ilk kurulumda yönetici bununla açılır
       'db'    => [
           'driver'  => 'mysql',
           'host'    => 'localhost',            // 127.0.0.1 DEĞİL
           'name'    => 'kullanici_babyloon',
           'user'    => 'kullanici_babyloon',
           'pass'    => 'veritabanı-şifresi',
           'charset' => 'utf8mb4',
       ],
   ];
   ```

3. **Git Version Control → Create** → Clone URL
   `https://github.com/ahmeterhancy-cpu/old-babyloon.git`,
   Repository Path `repositories/old-babyloon`.
4. **Manage → Update from Remote → Deploy HEAD Commit.**
5. `~/deploy-son.log` sonunda `Yönetici oluşturuldu` ve `=== DEPLOY BITTI ===`.
6. Panele gir (`/admin`), Kullanıcılar'dan şifreyi istersen değiştir.
   `admin_password` satırı sonradan silinebilir (yönetici zaten var).

---

## 3. Dağıtım ne yapar (`.cpanel.yml`)

1. `index.php`, `migrate.php`, `.htaccess`, `app/`, `assets/`, `tools/`,
   `uploads/` → `public_html` (üstüne yazar; paneldan yüklenenleri silmez).
2. `babyloon-config.php` varsa `php migrate.php` — **güvenli**: eksik tabloyu
   kurar, veriyi yalnız tablo boşsa doldurur, hiçbir şeyi silmez. `--fresh`
   ASLA eklenmez.
3. Görsel türevleri (`@700.jpg` … `.webp`) yalnız **eksik** olanlar için üretilir
   (`--eksik`); depoda yoklar, sunucuda bir kez üretilir.

**Kurallar** (tarifteki gibi): her görev tek satır; metinde `iki nokta + boşluk`
yok (YAML bozulur, cPanel sessizce düşürür, "Last Deployed" donar); mutlak yol.

---

## 4. Yayın döngüsü

1. Yerelde değiştir, test et (`php tests/smoke.php`), commit.
2. `git push` (onaylı).
3. cPanel → Git Version Control → **Manage** → **Update from Remote** →
   **Deploy HEAD Commit**. İki düğme iki ayrı iş.
4. "Last Deployed SHA" son commit mi? `~/deploy-son.log` → `=== DEPLOY BITTI ===`?

---

## 5. Kurulumdan sonra kontrol

- [ ] `https://oldbabyloon.com/babyloon-config.php` → **404**
- [ ] `https://oldbabyloon.com/app/config.php` → **403/404**
- [ ] `https://oldbabyloon.com/migrate.php` → **404**
- [ ] `http://` ve `www.` → `https://oldbabyloon.com` (301)
- [ ] `/qr/menu` → QR menüye gider; panelden QR kartını indir, telefonla okut
- [ ] Çalışma saatleri girildi → Site Ayarları → İletişim → “Çalışma saatleri doğrulandı”
- [ ] `uploads/` yazılabilir (panelden görsel yüklemeyi dene)

## 6. Sorun tablosu

| Belirti | Sebep |
|---|---|
| "Last Deployed" değişmiyor | `.cpanel.yml`'de `: ` / çok satırlı görev, ya da yalnız ikinci düğmeye basıldı |
| Günlükte `MIGRATE ATLANDI` | `public_html/babyloon-config.php` yok |
| Günlükte `UYARI ... admin_password yok` | Config'e `admin_password` ekle, tekrar dağıt |
| "Veritabanına bağlanılamadı" | `host` = `localhost`, kullanıcı veritabanına eklenmemiş |
| Site açılmıyor, tarayıcı SSL hatası | Sertifika yok — AutoSSL / Let's Encrypt (www dahil) |
| Görseller büyük iniyor | GD/WebP yok → türev üretilmedi; günlükte `0 türev` |
