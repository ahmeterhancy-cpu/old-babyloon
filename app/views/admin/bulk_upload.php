<?php
$key    = $data['resKey'];
$res    = $data['res'];
$limits = $data['limits'];
$mb     = round($limits['file'] / 1048576, 1);
?>

<p class="crumb"><a href="<?= e(admin_url("kaynak/$key/liste")) ?>">← <?= e($res['title']) ?></a></p>

<form class="admform bulkup" method="post" enctype="multipart/form-data" data-bulk-upload
      action="<?= e(admin_url("kaynak/$key/toplu-yukle")) ?>"
      data-done="<?= e(admin_url("kaynak/$key/liste")) ?>" data-max="<?= (int) $limits['file'] ?>">
  <?= csrf_field() ?>

  <div class="admform__body">
    <label class="bulkup__drop" data-bulk-drop>
      <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required data-bulk-input>
      <b>Fotoğrafları seçin</b>
      <span>ya da buraya sürükleyip bırakın</span>
    </label>
    <p class="field__hint">
      JPG, PNG, WebP ya da GIF · dosya başına en fazla <?= e((string) $mb) ?> MB.
      Her fotoğraf ayrı kayıt olarak listenin sonuna eklenir ve yayında açılır;
      açıklamaları sonra “Düzenle” ile yazabilirsiniz.
    </p>

    <ul class="bulkup__list" data-bulk-list hidden></ul>
    <p class="bulkup__status" data-bulk-status role="status" aria-live="polite"></p>
  </div>

  <div class="admform__actions">
    <button class="btn" type="submit" data-bulk-submit>Yükle</button>
    <a class="btn btn--ghost" href="<?= e(admin_url("kaynak/$key/liste")) ?>">Vazgeç</a>
  </div>
</form>
