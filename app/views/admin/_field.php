<?php
/**
 * Tek bir form alanını çizer. Hem kaynak formları hem ayarlar sayfası kullanır.
 *
 * $data: name, field, values (sütun => değer), errors
 */
$name   = $data['name'];
$f      = $data['field'];
$values = $data['values'] ?? [];
$errors = $data['errors'] ?? [];
$type   = $f['type'] ?? 'text';
$label  = $f['label'] ?? $name;
$hint   = $f['hint'] ?? '';
$req    = !empty($f['required']);

$val = fn(string $col, string $def = ''): string => (string) ($values[$col] ?? $def);
$err = fn(string $col): string => (string) ($errors[$col] ?? '');
?>

<?php if (in_array($type, ['i18n', 'i18n_area', 'i18n_html'], true)): ?>
  <div class="field field--i18n<?= $err($name . '_' . cfg('default_locale')) ? ' has-err' : '' ?>">
    <span class="field__label"><?= e($label) ?><?= $req ? ' <i>*</i>' : '' ?></span>
    <div class="i18n">
      <?php foreach (locales() as $loc => $locLabel): ?>
      <?php $col = $name . '_' . $loc; ?>
      <div class="i18n__one">
        <label class="i18n__lang" for="f-<?= e($col) ?>"><?= e(strtoupper($loc)) ?></label>
        <?php if ($type === 'i18n'): ?>
          <input id="f-<?= e($col) ?>" name="<?= e($col) ?>" type="text" value="<?= e($val($col)) ?>"
                 <?= $req && $loc === cfg('default_locale') ? 'required' : '' ?>>
        <?php else: ?>
          <textarea id="f-<?= e($col) ?>" name="<?= e($col) ?>"
                    rows="<?= $type === 'i18n_html' ? 12 : 4 ?>"
                    <?= $type === 'i18n_html' ? 'class="mono"' : '' ?>><?= e($val($col)) ?></textarea>
        <?php endif; ?>
        <?php if ($err($col)): ?><span class="field__err"><?= e($err($col)) ?></span><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if ($hint): ?><span class="field__hint"><?= $hint ?></span><?php endif; ?>
  </div>

<?php elseif ($type === 'image'): ?>
  <div class="field<?= $err($name) ? ' has-err' : '' ?>">
    <span class="field__label"><?= e($label) ?></span>
    <div class="upload">
      <?php if (has_img($val($name))): ?>
      <div class="upload__cur">
        <img src="<?= e(asset($val($name))) ?>" alt="">
        <label class="upload__rm">
          <input type="checkbox" name="__remove_<?= e($name) ?>" value="1"> Bu görseli kaldır
        </label>
      </div>
      <?php endif; ?>
      <input id="f-<?= e($name) ?>" name="<?= e($name) ?>" type="file"
             accept="image/jpeg,image/png,image/webp,image/gif">
    </div>
    <?php if ($err($name)): ?><span class="field__err"><?= e($err($name)) ?></span><?php endif; ?>
    <span class="field__hint"><?= $hint ?: 'JPG, PNG veya WebP. En fazla 6 MB; büyük fotoğraflar otomatik küçültülür.' ?></span>
  </div>

<?php elseif ($type === 'checkbox'): ?>
  <div class="field field--check">
    <label class="check">
      <input type="checkbox" name="<?= e($name) ?>" value="1"
             <?= ((string) $val($name, (string) ($f['default'] ?? 0)) === '1') ? 'checked' : '' ?>>
      <span><?= e($label) ?></span>
    </label>
    <?php if ($hint): ?><span class="field__hint"><?= $hint ?></span><?php endif; ?>
  </div>

<?php elseif ($type === 'badges'): ?>
  <?php $selected = array_filter(array_map('trim', explode(',', $val($name)))); ?>
  <div class="field">
    <span class="field__label"><?= e($label) ?></span>
    <div class="badgepick">
      <?php foreach ((($f['options'])()) as $k => $lbl): ?>
      <label class="check check--inline">
        <input type="checkbox" name="<?= e($name) ?>[]" value="<?= e($k) ?>"
               <?= in_array($k, $selected, true) ? 'checked' : '' ?>>
        <span><?= e($lbl) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
    <?php if ($hint): ?><span class="field__hint"><?= $hint ?></span><?php endif; ?>
  </div>

<?php elseif ($type === 'select'): ?>
  <div class="field<?= $err($name) ? ' has-err' : '' ?>">
    <label class="field__label" for="f-<?= e($name) ?>"><?= e($label) ?><?= $req ? ' <i>*</i>' : '' ?></label>
    <select id="f-<?= e($name) ?>" name="<?= e($name) ?>" <?= $req ? 'required' : '' ?>>
      <?php if (!$req): ?><option value="">—</option><?php endif; ?>
      <?php foreach ((($f['options'])()) as $k => $lbl): ?>
      <option value="<?= e((string) $k) ?>" <?= (string) $val($name) === (string) $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($err($name)): ?><span class="field__err"><?= e($err($name)) ?></span><?php endif; ?>
    <?php if ($hint): ?><span class="field__hint"><?= $hint ?></span><?php endif; ?>
  </div>

<?php elseif ($type === 'raw_html'): ?>
  <div class="field">
    <label class="field__label" for="f-<?= e($name) ?>"><?= e($label) ?></label>
    <textarea id="f-<?= e($name) ?>" name="<?= e($name) ?>" rows="5" class="mono"><?= e($val($name)) ?></textarea>
    <?php if ($hint): ?><span class="field__hint"><?= $hint ?></span><?php endif; ?>
  </div>

<?php elseif ($type === 'datetime'): ?>
  <?php $dt = $val($name); $dt = $dt ? date('Y-m-d\TH:i', strtotime($dt)) : date('Y-m-d\TH:i'); ?>
  <div class="field">
    <label class="field__label" for="f-<?= e($name) ?>"><?= e($label) ?></label>
    <input id="f-<?= e($name) ?>" name="<?= e($name) ?>" type="datetime-local" value="<?= e($dt) ?>">
    <?php if ($hint): ?><span class="field__hint"><?= $hint ?></span><?php endif; ?>
  </div>

<?php else: ?>
  <?php
    $inputType = match ($type) {
        'number' => 'number', 'price' => 'text', 'email' => 'email',
        'time' => 'time', 'date' => 'date', default => 'text',
    };
    $current = $val($name, (string) ($f['default'] ?? ''));
    if ($type === 'price' && $current !== '') { $current = rtrim(rtrim(number_format((float) $current, 2, ',', ''), '0'), ','); }
  ?>
  <div class="field<?= $err($name) ? ' has-err' : '' ?>">
    <label class="field__label" for="f-<?= e($name) ?>"><?= e($label) ?><?= $req ? ' <i>*</i>' : '' ?></label>
    <?php if ($type === 'textarea'): ?>
      <textarea id="f-<?= e($name) ?>" name="<?= e($name) ?>" rows="4"><?= e($current) ?></textarea>
    <?php else: ?>
      <input id="f-<?= e($name) ?>" name="<?= e($name) ?>" type="<?= $inputType ?>"
             value="<?= e($current) ?>" <?= $req ? 'required' : '' ?>
             <?= $type === 'price' ? 'inputmode="decimal"' : '' ?>>
    <?php endif; ?>
    <?php if ($err($name)): ?><span class="field__err"><?= e($err($name)) ?></span><?php endif; ?>
    <?php if ($hint): ?><span class="field__hint"><?= $hint ?></span><?php endif; ?>
  </div>
<?php endif; ?>
