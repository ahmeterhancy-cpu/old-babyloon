<?php
/** Baskıya hazır masa kartı — A6 boyutunda, kesim payı ile. */
$qr  = $data['qr'];
$url = $data['url'];
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Menü kartı — <?= e((string) $qr['label']) ?></title>
<style>
  :root { --terra: #FDF001; --cream: #F2EEE3; --espresso: #12100C; }
  * { box-sizing: border-box; }
  /* Bu sayfa panel CSS'ini yüklemez — ekran okuyucu metni gizli kalsın */
  .sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }
  body {
    margin: 0; background: #EFE9E1; color: var(--espresso);
    font-family: 'Karla', system-ui, sans-serif;
    display: grid; place-items: center; min-height: 100vh; padding: 2rem 1rem;
  }
  .bar { display: flex; gap: .6rem; justify-content: center; margin-bottom: 1.5rem; }
  .bar a, .bar button {
    font: 600 .82rem/1 'Karla', sans-serif; padding: .7rem 1.2rem; border-radius: 100px;
    background: var(--espresso); color: #fff; border: 1px solid var(--espresso);
    text-decoration: none; cursor: pointer;
  }
  .bar .ghost { background: transparent; color: var(--espresso); }

  .card {
    width: 105mm; height: 148mm;                 /* A6 */
    background: var(--espresso); color: #fff; border-radius: 6mm;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
    padding: 12mm 10mm; text-align: center;
    display: flex; flex-direction: column; align-items: center; justify-content: space-between;
    box-shadow: 0 20px 50px -30px rgba(51,35,29,.6);
    position: relative; overflow: hidden;
  }
  /* Markanın mozaik dokusu — baskıda hafif bir zemin deseni */
  .brandwrap { width: 70mm; position: relative; color: var(--terra); }
  .brandwrap svg { width: 100%; height: auto; display: block; }

  h1 { font: 600 6.5mm/1.2 'Playfair Display', Georgia, serif; margin: 4mm 0 1.5mm; position: relative; }
  .lead { font-size: 3.4mm; color: rgba(255,255,255,.75); margin: 0; position: relative; }

  .qr { width: 52mm; padding: 3mm; background: #fff; border: 1px solid rgba(51,35,29,.12); border-radius: 3mm; }
  .qr svg { width: 100%; height: auto; display: block; }

  .label { font: 700 3.2mm/1 'Karla', sans-serif; letter-spacing: .18em; text-transform: uppercase; color: var(--terra); }
  .foot { font-size: 2.9mm; color: rgba(255,255,255,.6); }

  @media print {
    body { background: #fff; padding: 0; display: block; }
    .bar { display: none; }
    .card { box-shadow: none; border-radius: 0; margin: 0 auto; page-break-inside: avoid; }
    @page { size: A6; margin: 0; }
  }
</style>
</head>
<body>

<div>
  <div class="bar">
    <button type="button" onclick="window.print()">Yazdır</button>
    <a class="ghost" href="<?= e(admin_url('qr')) ?>">← QR kodlarına dön</a>
  </div>

  <div class="card">
    <div class="brandwrap"><?= view('layout/logo') ?></div>

    <div>
      <h1>Menü telefonunuzda</h1>
      <p class="lead">Kamerayı kodun üzerine tutmanız yeterli.</p>
    </div>

    <div class="qr"><?= QrCode::svg($url, 8, 1, QrCode::M, '#33231D', '#FFFFFF') ?></div>

    <div>
      <p class="label"><?= e((string) $qr['label']) ?></p>
      <p class="foot"><?= e(preg_replace('~^https?://~', '', $url)) ?></p>
    </div>
  </div>
</div>

</body>
</html>
