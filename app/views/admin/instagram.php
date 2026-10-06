<?php
$rows      = $data['rows'];
$errors    = $data['errors'];
$lastSync  = $data['lastSync'];
$lastError = $data['lastError'];
$enabled   = setting('ig_enabled', '0') === '1';
$hasToken  = setting('ig_token') !== '';
$expires   = setting('ig_token_expires');
$visible   = count(array_filter($rows, fn($r) => (int) $r['is_active'] === 1));
?>

<p class="hint">
  Ana sayfanın alt bölümündeki Instagram şeridi buradan beslenir. İki yol var:
  <b>otomatik</b> (Instagram’dan çekilir) veya <b>elle</b> (fotoğrafı siz yüklersiniz).
  Otomatik akış için Meta’dan bir erişim jetonu almanız gerekir — aşağıdaki adımlar bunu anlatıyor.
  Jeton olmadan da elle eklediğiniz gönderiler yayında görünür.
</p>

<?php if ($lastError): ?>
<p class="adm__flash adm__flash--err" role="alert"><b>Son senkronizasyon hatası:</b> <?= e($lastError) ?></p>
<?php endif; ?>

<div class="cards">
  <div class="card"><b><?= count($rows) ?></b><span>kayıtlı gönderi</span></div>
  <div class="card"><b><?= $visible ?></b><span>yayında</span></div>
  <div class="card <?= $enabled && $hasToken ? '' : 'card--warn' ?>">
    <b><?= $enabled && $hasToken ? 'Açık' : 'Kapalı' ?></b><span>otomatik akış</span>
  </div>
  <div class="card">
    <b><?= $lastSync ? e(date('d.m H:i', $lastSync)) : '—' ?></b><span>son güncelleme</span>
  </div>
</div>

<div class="cols">

  <section class="panel">
    <header class="panel__h"><h2>Bağlantı ayarları</h2></header>

    <form method="post" class="admform admform--tight">
      <?= csrf_field() ?>
      <input type="hidden" name="mode" value="settings">

      <div class="field field--check">
        <label class="check">
          <input type="checkbox" name="ig_enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
          <span>Otomatik akışı aç</span>
        </label>
        <span class="field__hint">Kapalıyken yalnızca elle eklenen gönderiler gösterilir.</span>
      </div>

      <div class="field">
        <label class="field__label" for="ig-user">Instagram kullanıcı adı</label>
        <input id="ig-user" name="ig_username" type="text" value="<?= e(setting('ig_username')) ?>" placeholder="kullaniciadiniz">
        <span class="field__hint">Yalnızca şeritteki “@kullanıcı” bağlantısı için kullanılır.</span>
      </div>

      <div class="field">
        <label class="field__label" for="ig-token">Erişim jetonu</label>
        <input id="ig-token" name="ig_token" type="text" autocomplete="off"
               placeholder="<?= $hasToken ? 'Kayıtlı — değiştirmek için yeni jetonu yapıştırın' : 'IGQVJ… ile başlayan uzun metin' ?>">
        <span class="field__hint">
          <?php if ($hasToken): ?>
            Jeton kayıtlı<?= $expires ? ', geçerlilik: ' . e($expires) : '' ?>.
          <?php else: ?>
            Boş. Aşağıdaki adımlarla alabilirsiniz.
          <?php endif; ?>
        </span>
      </div>

      <?php if ($hasToken): ?>
      <div class="field field--check">
        <label class="check">
          <input type="checkbox" name="ig_clear_token" value="1">
          <span>Kayıtlı jetonu sil</span>
        </label>
      </div>
      <?php endif; ?>

      <div class="field">
        <label class="field__label" for="ig-id">Instagram kullanıcı kimliği <span style="font-weight:400">(isteğe bağlı)</span></label>
        <input id="ig-id" name="ig_user_id" type="text" value="<?= e(setting('ig_user_id')) ?>" placeholder="17841400000000000">
        <span class="field__hint">
          Hesap bir Facebook sayfasına bağlı <b>Business</b> hesabıysa buraya IG kimliğini yazın.
          Doğrudan Instagram girişiyle alınan jetonlarda boş bırakın.
        </span>
      </div>

      <div class="field">
        <label class="field__label" for="ig-limit">Kaç gönderi çekilsin?</label>
        <input id="ig-limit" name="ig_limit" type="number" min="1" max="50" value="<?= e(setting('ig_limit', '12')) ?>">
      </div>

      <div class="admform__actions"><button class="btn" type="submit">Ayarları kaydet</button></div>
    </form>

    <div class="toolbar" style="margin-top:1rem">
      <form method="post" action="<?= e(admin_url('instagram/yenile')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn--ghost" type="submit" <?= $hasToken ? '' : 'disabled' ?>>Şimdi güncelle</button>
      </form>
      <form method="post" action="<?= e(admin_url('instagram/jeton')) ?>">
        <?= csrf_field() ?>
        <button class="mini" type="submit" <?= $hasToken ? '' : 'disabled' ?>>Jetonu 60 gün uzat</button>
      </form>
    </div>
    <p class="hint" style="margin-top:.6rem">
      Site kendi kendine de saatte bir günceller. Jetonun süresi dolmadan (60 gün)
      “Jetonu 60 gün uzat” düğmesine basmanız yeterli.
    </p>
  </section>

  <section class="panel">
    <header class="panel__h"><h2>Elle gönderi ekle</h2></header>
    <p class="hint">Jeton beklemeden akışı doldurmak için. Fotoğrafı yükleyin, gönderi bağlantısını yapıştırın.</p>

    <form method="post" class="admform admform--tight" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="mode" value="manual">

      <div class="field<?= isset($errors['image']) ? ' has-err' : '' ?>">
        <label class="field__label" for="ig-img">Fotoğraf</label>
        <div class="upload">
          <input id="ig-img" name="image" type="file" accept="image/jpeg,image/png,image/webp">
        </div>
        <?php if (isset($errors['image'])): ?><span class="field__err"><?= e($errors['image']) ?></span><?php endif; ?>
        <span class="field__hint">Kare fotoğraf en iyi görünür.</span>
      </div>

      <div class="field<?= isset($errors['permalink']) ? ' has-err' : '' ?>">
        <label class="field__label" for="ig-link">Gönderi bağlantısı</label>
        <input id="ig-link" name="permalink" type="url" placeholder="https://www.instagram.com/p/…">
        <?php if (isset($errors['permalink'])): ?><span class="field__err"><?= e($errors['permalink']) ?></span><?php endif; ?>
        <span class="field__hint">Boş bırakılırsa profil sayfasına gider.</span>
      </div>

      <div class="field">
        <label class="field__label" for="ig-cap">Alt yazı</label>
        <textarea id="ig-cap" name="caption" rows="3"></textarea>
      </div>

      <div class="admform__actions"><button class="btn" type="submit">Gönderiyi ekle</button></div>
    </form>
  </section>
</div>

<section class="panel">
  <header class="panel__h">
    <h2>Gönderiler</h2>
    <?php if (setting('instagram')): ?>
    <a href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener">Instagram’da aç ↗</a>
    <?php endif; ?>
  </header>

  <?php if (!$rows): ?>
  <p class="empty">Henüz gönderi yok. Jetonu girip “Şimdi güncelle” deyin ya da elle bir gönderi ekleyin.</p>
  <?php else: ?>
  <div class="iggrid">
    <?php foreach ($rows as $p): ?>
    <figure class="igcard<?= (int) $p['is_active'] === 0 ? ' is-off' : '' ?>">
      <img src="<?= e(img($p['image'])) ?>" alt="" loading="lazy">
      <figcaption>
        <p class="igcard__cap"><?= e(Instagram::shortCaption($p['caption'], 70)) ?: '—' ?></p>
        <p class="igcard__meta">
          <?= (int) $p['is_manual'] === 1 ? 'elle' : 'otomatik' ?>
          <?php if ($p['posted_at']): ?> · <?= e(date('d.m.Y', strtotime((string) $p['posted_at']))) ?><?php endif; ?>
        </p>
        <div class="igcard__act">
          <form method="post" action="<?= e(admin_url('instagram/gizle/' . $p['id'])) ?>">
            <?= csrf_field() ?>
            <button class="mini" type="submit"><?= (int) $p['is_active'] === 1 ? 'Gizle' : 'Göster' ?></button>
          </form>
          <form method="post" action="<?= e(admin_url('instagram/sil/' . $p['id'])) ?>"
                onsubmit="return confirm('Bu gönderi kaldırılacak. Emin misiniz?')">
            <?= csrf_field() ?>
            <button class="mini mini--danger" type="submit">Sil</button>
          </form>
        </div>
      </figcaption>
    </figure>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<section class="panel">
  <header class="panel__h"><h2>Erişim jetonu nasıl alınır?</h2></header>
  <ol class="steps">
    <li>Instagram hesabınızın <b>Business</b> veya <b>Creator</b> hesabı olduğundan emin olun
        (Instagram → Ayarlar → Hesap türü).</li>
    <li><a href="https://developers.facebook.com/apps" target="_blank" rel="noopener">developers.facebook.com/apps</a>
        adresinden bir uygulama oluşturun ve <b>Instagram</b> ürününü ekleyin.</li>
    <li>Uygulamada <b>Instagram → API setup with Instagram login</b> bölümüne girin,
        hesabınızı bağlayın ve <b>Generate token</b> ile jetonu oluşturun.</li>
    <li>Oluşan uzun metni kopyalayıp yukarıdaki <b>Erişim jetonu</b> alanına yapıştırın,
        “Otomatik akışı aç” kutusunu işaretleyin ve kaydedin.</li>
    <li><b>Şimdi güncelle</b> düğmesine basın. Gönderiler birkaç saniye içinde listelenir.</li>
  </ol>
  <p class="hint">
    Jeton 60 gün geçerlidir; süresi dolmadan “Jetonu 60 gün uzat” düğmesine basın.
    Süre dolarsa akış durur, site çalışmaya devam eder — o sırada elle eklenen gönderiler görünür.
  </p>
</section>
