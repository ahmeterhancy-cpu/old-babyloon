<?php $hours = $data['hours']; ?>

<p class="hint">Burada girdiğiniz saatler ana sayfada, alt bilgide, iletişim sayfasında ve QR menüde
“şu an açık / kapalı” göstergesini besler. Gece yarısını aşan kapanışlar (örn. 08:00 – 01:00) desteklenir.</p>

<form method="post" class="admform">
  <?= csrf_field() ?>
  <div class="tablewrap">
  <table class="table table--hours">
    <thead>
      <tr><th>Gün</th><th>Açılış</th><th>Kapanış</th><th>Kapalı</th><th>Not (TR)</th><th>Not (EN)</th></tr>
    </thead>
    <tbody>
      <?php for ($d = 1; $d <= 7; $d++): $h = $hours[$d] ?? []; ?>
      <tr>
        <th scope="row"><?= e(t('day.' . $d)) ?></th>
        <td><input type="time" name="open[<?= $d ?>]" value="<?= e((string) ($h['open_time'] ?? '08:00')) ?>"></td>
        <td><input type="time" name="close[<?= $d ?>]" value="<?= e((string) ($h['close_time'] ?? '22:00')) ?>"></td>
        <td class="center">
          <label class="check">
            <input type="checkbox" name="closed[<?= $d ?>]" value="1" <?= (int) ($h['is_closed'] ?? 0) === 1 ? 'checked' : '' ?>>
            <span class="sr">Kapalı</span>
          </label>
        </td>
        <td><input type="text" name="note_tr[<?= $d ?>]" value="<?= e((string) ($h['note_tr'] ?? '')) ?>" placeholder="örn. mutfak 22:00’de kapanır"></td>
        <td><input type="text" name="note_en[<?= $d ?>]" value="<?= e((string) ($h['note_en'] ?? '')) ?>"></td>
      </tr>
      <?php endfor; ?>
    </tbody>
  </table>
  </div>

  <div class="admform__actions">
    <button class="btn" type="submit">Saatleri kaydet</button>
  </div>
</form>
