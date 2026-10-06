<?php
$rows   = $data['rows'];
$me     = $data['me'];
$errors = $data['errors'];
?>

<div class="cols">

  <section class="panel">
    <header class="panel__h"><h2>Şifrenizi değiştirin</h2></header>
    <form method="post" class="admform admform--tight">
      <?= csrf_field() ?>
      <input type="hidden" name="mode" value="password">

      <div class="field<?= isset($errors['current_password']) ? ' has-err' : '' ?>">
        <label class="field__label" for="cp">Mevcut şifre</label>
        <input id="cp" name="current_password" type="password" autocomplete="current-password" required>
        <?php if (isset($errors['current_password'])): ?><span class="field__err"><?= e($errors['current_password']) ?></span><?php endif; ?>
      </div>

      <div class="field<?= isset($errors['new_password']) ? ' has-err' : '' ?>">
        <label class="field__label" for="np">Yeni şifre</label>
        <input id="np" name="new_password" type="password" autocomplete="new-password" required minlength="8">
        <?php if (isset($errors['new_password'])): ?><span class="field__err"><?= e($errors['new_password']) ?></span><?php endif; ?>
        <span class="field__hint">En az 8 karakter.</span>
      </div>

      <div class="field<?= isset($errors['repeat_password']) ? ' has-err' : '' ?>">
        <label class="field__label" for="rp">Yeni şifre (tekrar)</label>
        <input id="rp" name="repeat_password" type="password" autocomplete="new-password" required>
        <?php if (isset($errors['repeat_password'])): ?><span class="field__err"><?= e($errors['repeat_password']) ?></span><?php endif; ?>
      </div>

      <div class="admform__actions"><button class="btn" type="submit">Şifreyi güncelle</button></div>
    </form>
  </section>

  <section class="panel">
    <header class="panel__h"><h2>Yeni kullanıcı</h2></header>
    <form method="post" class="admform admform--tight">
      <?= csrf_field() ?>
      <input type="hidden" name="mode" value="create">

      <div class="field<?= isset($errors['username']) ? ' has-err' : '' ?>">
        <label class="field__label" for="nu">Kullanıcı adı</label>
        <input id="nu" name="username" type="text" required>
        <?php if (isset($errors['username'])): ?><span class="field__err"><?= e($errors['username']) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label class="field__label" for="nn">Ad soyad</label>
        <input id="nn" name="name" type="text">
      </div>

      <div class="field<?= isset($errors['password']) ? ' has-err' : '' ?>">
        <label class="field__label" for="npw">Şifre</label>
        <input id="npw" name="password" type="password" required minlength="8" autocomplete="new-password">
        <?php if (isset($errors['password'])): ?><span class="field__err"><?= e($errors['password']) ?></span><?php endif; ?>
      </div>

      <div class="admform__actions"><button class="btn" type="submit">Kullanıcı ekle</button></div>
    </form>
  </section>
</div>

<section class="panel">
  <header class="panel__h"><h2>Kullanıcılar</h2></header>
  <div class="tablewrap">
  <table class="table">
    <thead><tr><th>Kullanıcı</th><th>Ad</th><th>Rol</th><th>Son giriş</th><th class="col-act">İşlem</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $u): ?>
      <tr>
        <td data-label="Kullanıcı"><?= e((string) $u['username']) ?><?= (int) $u['id'] === (int) $me['id'] ? ' <span class="pill pill--on">siz</span>' : '' ?></td>
        <td data-label="Ad"><?= e((string) $u['name']) ?></td>
        <td data-label="Rol"><?= e((string) $u['role']) ?></td>
        <td data-label="Son giriş"><?= $u['last_login_at'] ? e(date('d.m.Y H:i', strtotime((string) $u['last_login_at']))) : '—' ?></td>
        <td class="col-act">
          <?php if ((int) $u['id'] !== (int) $me['id']): ?>
          <form method="post" action="<?= e(admin_url('kullanicilar/sil/' . $u['id'])) ?>"
                onsubmit="return confirm('Bu kullanıcı silinecek. Emin misiniz?')">
            <?= csrf_field() ?><button class="mini mini--danger" type="submit">Sil</button>
          </form>
          <?php else: ?>—<?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</section>
