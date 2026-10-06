<?php
$s = $data['stats'];
$statuses = ['new' => 'Yeni', 'confirmed' => 'Onaylandı', 'declined' => 'Reddedildi', 'done' => 'Tamamlandı'];
?>

<div class="cards">
  <a class="card <?= $s['unread'] ? 'card--alert' : '' ?>" href="<?= e(admin_url('mesajlar')) ?>">
    <b><?= $s['unread'] ?></b><span>okunmamış mesaj</span>
  </a>
  <a class="card" href="<?= e(admin_url('kaynak/menu_items/liste')) ?>">
    <b><?= $s['items'] ?></b><span>menü ürünü</span>
  </a>
  <a class="card <?= $s['outstock'] ? 'card--warn' : '' ?>" href="<?= e(admin_url('kaynak/menu_items/liste')) ?>">
    <b><?= $s['outstock'] ?></b><span>serviste değil</span>
  </a>
  <a class="card" href="<?= e(admin_url('qr')) ?>">
    <b><?= $s['scans'] ?></b><span>QR menü okutması</span>
  </a>
  <a class="card" href="<?= e(admin_url('kaynak/gallery/liste')) ?>">
    <b><?= $s['photos'] ?></b><span>galeri fotoğrafı</span>
  </a>
</div>

<div class="cols">
  <section class="panel">
    <header class="panel__h">
      <h2>Son mesajlar</h2>
      <a href="<?= e(admin_url('mesajlar')) ?>">Tümü</a>
    </header>
    <?php if ($data['messages']): ?>
    <ul class="feed">
      <?php foreach ($data['messages'] as $m): ?>
      <li class="<?= (int) $m['is_read'] === 0 ? 'is-new' : '' ?>">
        <b><?= e($m['name']) ?></b>
        <span class="feed__meta"><?= e(date('d.m.Y H:i', strtotime((string) $m['created_at']))) ?></span>
        <p><?= e(excerpt((string) $m['body'], 110)) ?></p>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="empty">Henüz mesaj yok.</p>
    <?php endif; ?>
  </section>

</div>

<?php if ($data['lowItems']): ?>
<section class="panel">
  <header class="panel__h">
    <h2>Serviste olmayan ürünler</h2>
    <a href="<?= e(admin_url('kaynak/menu_items/liste')) ?>">Menüyü düzenle</a>
  </header>
  <ul class="chips">
    <?php foreach ($data['lowItems'] as $i): ?>
    <li><a href="<?= e(admin_url('kaynak/menu_items/duzenle/' . $i['id'])) ?>">
      <?= e($i['name_tr']) ?> <em><?= e($i['cat']) ?></em>
    </a></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<section class="panel panel--tips">
  <header class="panel__h"><h2>Hızlı işlemler</h2></header>
  <div class="quick">
    <a href="<?= e(admin_url('kaynak/menu_items/yeni')) ?>">Menüye ürün ekle</a>
    <a href="<?= e(admin_url('kaynak/gallery/yeni')) ?>">Galeriye fotoğraf ekle</a>
    <a href="<?= e(admin_url('saatler')) ?>">Çalışma saatlerini değiştir</a>
    <a href="<?= e(admin_url('qr')) ?>">QR menü kodunu aç</a>
    <a href="<?= e(admin_url('ayarlar')) ?>">İletişim bilgilerini güncelle</a>
  </div>
</section>
