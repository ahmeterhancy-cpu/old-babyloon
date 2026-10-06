<?php
$key     = $data['resKey'];
$res     = $data['res'];
$rows    = $data['rows'];
$lookups = $data['lookups'];
$hasSort = isset($res['fields']['sort']);
$groupBy = $res['group_by'] ?? null;

/** Bir sütun hücresini biçimlendirir. */
$cell = function (array $row, string $col) use ($lookups, $res): string {
    $val = $row[$col] ?? '';

    if (in_array($col, ['image', 'cover', 'photo', 'avatar'], true)) {
        return has_img((string) $val)
            ? '<img class="thumb" src="' . e(asset((string) $val)) . '" alt="">'
            : '<span class="thumb thumb--none">—</span>';
    }
    if (isset($lookups[$col])) {
        return e((string) ($lookups[$col][(string) $val] ?? $val));
    }
    if (in_array($col, ['is_active', 'is_published', 'is_available', 'is_featured'], true)) {
        return ((int) $val === 1)
            ? '<span class="pill pill--on">Evet</span>'
            : '<span class="pill">Hayır</span>';
    }
    if ($col === 'price') { return e(money($val)); }
    if ($col === 'icon')  { return e((string) (admin_icon_options()[(string) $val] ?? $val)); }
    if ($col === 'published_at' && $val) { return e(date('d.m.Y', strtotime((string) $val))); }
    if ($col === 'rating') { return str_repeat('★', max(0, min(5, (int) $val))); }
    if ($col === 'sort')  { return e((string) $val); }

    return e(excerpt((string) $val, 70));
};

/** Onay penceresinde kaydı tanıtacak kısa bir ad. */
$rowLabel = function (array $row) use ($res): string {
    foreach (['title_tr', 'name_tr', 'name', 'label', 'caption_tr', 'username'] as $c) {
        if (!empty($row[$c])) { return excerpt((string) $row[$c], 40); }
    }
    return 'Bu kayıt';
};

// Gruplu liste (menü ürünleri kategoriye göre)
$grouped = [];
if ($groupBy) {
    foreach ($rows as $r) { $grouped[(string) $r[$groupBy]][] = $r; }
} else {
    $grouped[''] = $rows;
}
?>

<div class="toolbar">
  <a class="btn" href="<?= e(admin_url("kaynak/$key/yeni")) ?>">+ Yeni ekle</a>
  <span class="toolbar__count"><?= count($rows) ?> kayıt</span>
</div>

<?php if (!empty($res['hint'])): ?>
<p class="hint"><?= $res['hint'] ?></p>
<?php endif; ?>

<?php if (!$rows): ?>
<p class="empty">Henüz kayıt yok. “Yeni ekle” ile başlayın.</p>
<?php else: ?>

<form method="post" action="<?= e(admin_url("kaynak/$key/sirala")) ?>">
<?= csrf_field() ?>

<?php foreach ($grouped as $gid => $groupRows): ?>
  <?php if ($groupBy && $gid !== ''): ?>
  <h2 class="grouphead"><?= e((string) ($lookups[$groupBy][(string) $gid] ?? 'Kategori')) ?></h2>
  <?php endif; ?>

  <div class="tablewrap">
  <table class="table">
    <thead>
      <tr>
        <th class="col-pick">
          <input type="checkbox" data-pick-all aria-label="Tümünü seç">
        </th>
        <?php foreach ($res['columns'] as $col => $label): ?>
        <th<?= $col === 'sort' ? ' class="col-sort"' : '' ?>><?= e($label) ?></th>
        <?php endforeach; ?>
        <th class="col-act">İşlem</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($groupRows as $row): ?>
      <tr>
        <td class="col-pick" data-label="Seç">
          <input type="checkbox" name="ids[]" value="<?= (int) $row['id'] ?>" data-pick
                 aria-label="<?= e($rowLabel($row)) ?> seç">
        </td>
        <?php foreach ($res['columns'] as $col => $label): ?>
          <?php if ($col === 'sort' && $hasSort): ?>
          <td class="col-sort">
            <input type="number" name="sort[<?= (int) $row['id'] ?>]" value="<?= (int) $row['sort'] ?>"
                   aria-label="Sıra numarası">
          </td>
          <?php else: ?>
          <td data-label="<?= e($label) ?>"><?= $cell($row, $col) ?></td>
          <?php endif; ?>
        <?php endforeach; ?>
        <td class="col-act">
          <a class="mini" href="<?= e(admin_url("kaynak/$key/duzenle/" . $row['id'])) ?>">Düzenle</a>
          <?php /* Tablo sıralama formunun içinde; form içine form konamaz.
                   Bu yüzden düğme aşağıdaki paylaşılan silme formuna bağlanıp
                   hedefi formaction ile değiştiriyor. */ ?>
          <button class="mini mini--danger" type="submit"
                  form="delform-<?= e($key) ?>"
                  formaction="<?= e(admin_url("kaynak/$key/sil/" . $row['id'])) ?>"
                  onclick="return confirm('<?= e($rowLabel($row)) ?> silinsin mi? Bu işlem geri alınamaz.')">Sil</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endforeach; ?>

<?php if ($hasSort): ?>
<div class="toolbar toolbar--end">
  <button class="btn btn--ghost" type="submit">Sıralamayı kaydet</button>
  <span class="hint hint--inline">Küçük numara üstte görünür.</span>
</div>
<?php endif; ?>

</form>

<?php /* Seçim çubuğu sıralama formunun DIŞINDA, kendi formunda duruyor.
         Aynı formda hem "sırayı kaydet" hem "seçilenleri sil" düğmesi
         olması tehlikeli: Enter tuşu, tarayıcının form tekrar gönderimi
         ya da otomasyon yanlışlıkla silmeyi tetikleyebiliyor.
         Seçili kimlikleri JS gönderim anında buraya kopyalıyor. */ ?>
<form class="pickbar" method="post" action="<?= e(admin_url("kaynak/$key/toplu-sil")) ?>"
      data-pickbar hidden>
  <?= csrf_field() ?>
  <input type="hidden" name="onay" value="1">
  <span class="pickbar__n"><b data-pick-count>0</b> kayıt seçildi</span>
  <button class="mini" type="button" data-pick-clear>Seçimi bırak</button>
  <button class="btn btn--danger btn--sm" type="submit" data-pick-delete>Seçilenleri sil</button>
</form>

<?php /* Satırlardaki Sil düğmelerinin bağlandığı form. Boş; hedefi
         her düğme kendi formaction'ıyla veriyor. */ ?>
<form id="delform-<?= e($key) ?>" method="post" hidden><?= csrf_field() ?></form>

<?php endif; ?>
