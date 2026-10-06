<?php
$title = $data['title'] ?? 'Yönetim';
$flash = flash();
$me    = auth();
$here  = current_path();
$unread  = (int) DB::value('SELECT COUNT(*) FROM messages WHERE is_read = 0', [], 0);
?>
<!doctype html>
<html lang="tr" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — Old Babyloon Yönetim</title>
<link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<script>document.documentElement.className="js";</script>
<body class="adm">

<a class="sr" href="#adm-main">İçeriğe geç</a>

<aside class="adm__side" id="adm-side">
  <a class="adm__brand" href="<?= e(admin_url()) ?>">
    <?= view('layout/logo') ?>
    <span>Yönetim</span>
  </a>

  <nav class="adm__nav">
    <?php foreach (admin_nav() as [$path, $label, $icon]): ?>
      <?php if ($path === '---'): ?>
        <p class="adm__navgroup"><?= e($label) ?></p>
      <?php else:
        $href = admin_url($path);
        $target = trim(base() . '/admin' . ($path !== '' ? '/' . $path : ''), '/');
        $isOn = $path === ''
            ? ($here === 'admin')
            : str_starts_with($here, trim('admin/' . $path, '/'));
        $badge = $path === 'mesajlar' ? $unread : 0;
      ?>
        <a class="adm__navlink<?= $isOn ? ' is-on' : '' ?>" href="<?= e($href) ?>">
          <?= view('layout/icon', ['name' => $icon ?: 'cup', 'class' => 'ico']) ?>
          <span><?= e($label) ?></span>
          <?php if ($badge > 0): ?><b class="adm__badge"><?= $badge ?></b><?php endif; ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>

  <div class="adm__foot">
    <a class="adm__site" href="<?= e(url('')) ?>" target="_blank" rel="noopener">Siteyi aç ↗</a>
    <div class="adm__me">
      <span><?= e($me['name'] ?: $me['username']) ?></span>
      <a href="<?= e(admin_url('cikis')) ?>">Çıkış</a>
    </div>
  </div>
</aside>

<div class="adm__body">
  <header class="adm__top">
    <button class="adm__burger" type="button" aria-label="Menü" onclick="document.body.classList.toggle('side-open')">☰</button>
    <h1 class="adm__title"><?= e($title) ?></h1>
  </header>

  <?php if ($flash): ?>
  <div class="adm__flash adm__flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <main class="adm__main" id="adm-main"><?= $data['content'] ?? '' ?></main>
</div>

<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
</body>
</html>
