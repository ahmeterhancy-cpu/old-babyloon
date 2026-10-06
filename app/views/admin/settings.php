<?php
$groups = $data['groups'];
$values = settings();
$first  = array_key_first($groups);
?>

<form class="admform" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <nav class="tabs" role="tablist">
    <?php foreach ($groups as $gk => $g): ?>
    <button class="tab<?= $gk === $first ? ' is-on' : '' ?>" type="button"
            role="tab" data-tab="<?= e($gk) ?>"><?= e($g['title']) ?></button>
    <?php endforeach; ?>
  </nav>

  <?php foreach ($groups as $gk => $g): ?>
  <section class="tabpane<?= $gk === $first ? ' is-on' : '' ?>" data-pane="<?= e($gk) ?>">
    <div class="admform__body">
      <?php foreach ($g['fields'] as $name => $f): ?>
        <?= view('admin/_field', ['name' => $name, 'field' => $f, 'values' => $values, 'errors' => []]) ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>

  <div class="admform__actions admform__actions--sticky">
    <button class="btn" type="submit">Tüm ayarları kaydet</button>
    <span class="hint hint--inline">Kaydet, tüm sekmelerdeki değişiklikleri birlikte uygular.</span>
  </div>
</form>
