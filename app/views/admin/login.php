<?php $error = $data['error'] ?? ''; ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Giriş — Old Babyloon Yönetim</title>
<link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="adm adm--login">

<main class="login">
  <div class="login__card">
    <div class="login__logo"><?= view('layout/logo') ?></div>
    <h1>Yönetim paneli</h1>
    <p class="login__sub">Devam etmek için giriş yapın.</p>

    <?php if ($error !== ''): ?>
    <p class="login__err" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" class="login__form">
      <?= csrf_field() ?>
      <label for="u">Kullanıcı adı</label>
      <input id="u" name="username" type="text" autocomplete="username" required autofocus>

      <label for="p">Şifre</label>
      <input id="p" name="password" type="password" autocomplete="current-password" required>

      <button class="btn" type="submit">Giriş yap</button>
    </form>
  </div>
  <p class="login__back"><a href="<?= e(url('')) ?>">← Siteye dön</a></p>
</main>

</body>
</html>
