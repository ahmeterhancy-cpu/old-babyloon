<?php
$key    = $data['resKey'];
$res    = $data['res'];
$row    = $data['row'];
$errors = $data['errors'];

// POST başarısız olduysa girilen değerler geri gelsin
$values = $row ?? [];
if (is_post()) {
    foreach ($_POST as $k => $v) { if (is_string($v)) { $values[$k] = $v; } }
    foreach ($res['fields'] as $n => $f) {
        if (($f['type'] ?? '') === 'badges') {
            $values[$n] = implode(',', array_map('strval', (array) ($_POST[$n] ?? [])));
        }
        if (($f['type'] ?? '') === 'checkbox') { $values[$n] = isset($_POST[$n]) ? 1 : 0; }
    }
}
?>

<p class="crumb"><a href="<?= e(admin_url("kaynak/$key/liste")) ?>">← <?= e($res['title']) ?></a></p>

<?php if ($errors): ?>
<p class="adm__flash adm__flash--err" role="alert">Lütfen işaretli alanları düzeltin.</p>
<?php endif; ?>

<form class="admform" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="admform__body">
    <?php foreach ($res['fields'] as $name => $f): ?>
      <?= view('admin/_field', ['name' => $name, 'field' => $f, 'values' => $values, 'errors' => $errors]) ?>
    <?php endforeach; ?>
  </div>

  <div class="admform__actions">
    <button class="btn" type="submit"><?= $row ? 'Değişiklikleri kaydet' : 'Kaydet' ?></button>
    <a class="btn btn--ghost" href="<?= e(admin_url("kaynak/$key/liste")) ?>">Vazgeç</a>
  </div>
</form>

<?php if ($row): ?>
<form class="dangerzone" method="post" action="<?= e(admin_url("kaynak/$key/sil/" . $row['id'])) ?>"
      onsubmit="return confirm('Bu kayıt kalıcı olarak silinecek. Emin misiniz?')">
  <?= csrf_field() ?>
  <div>
    <h2>Kaydı sil</h2>
    <p>Silinen kayıt geri getirilemez. Ürünü geçici olarak gizlemek için “Serviste / Yayında” kutusunun işaretini kaldırmanız yeterli.</p>
  </div>
  <button class="btn btn--danger" type="submit">Sil</button>
</form>
<?php endif; ?>
