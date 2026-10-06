<?php
$rows   = $data['rows'];
$errors = $data['errors'];
$cats   = $data['cats'];
$kinds  = ['menu' => 'QR menünün tamamı', 'category' => 'Belirli bir kategori', 'url' => 'Serbest adres'];

// Genel menü kodu ile ek kodları ayır
$main   = null;
$extras = [];
foreach ($rows as $r) {
    if ($main === null && $r['slug'] === 'menu') { $main = $r; } else { $extras[] = $r; }
}
if ($main === null && $rows) { $main = array_shift($rows); $extras = $rows; }
?>

<?php if ($main): ?>
<section class="panel qrmain">
  <header class="panel__h"><h2>Menü QR kodu</h2></header>
  <p class="hint">
    İşletmenin tek kodu budur. Menü kapağına, tezgâha, vitrine ve masalara aynı kod basılır.
    Okutan kişi telefonunun diline göre Türkçe ya da İngilizce menüye gider.
  </p>

  <div class="qrmain__row">
    <div class="qrmain__code"><?= QrCode::svg(admin_qr_url($main), 6, 2, QrCode::M, '#33231D', '#FFFFFF') ?></div>

    <div class="qrmain__info">
      <p class="qrmain__url">
        <a href="<?= e(admin_qr_url($main)) ?>" target="_blank" rel="noopener"><?= e(admin_qr_url($main)) ?></a>
      </p>
      <p class="qrmain__scans">
        <b><?= (int) $main['scans'] ?></b> okutma
        <?php if ($main['last_scan_at']): ?>
        <span>· son: <?= e(date('d.m.Y H:i', strtotime((string) $main['last_scan_at']))) ?></span>
        <?php endif; ?>
      </p>

      <div class="qrmain__act">
        <a class="btn" href="<?= e(admin_url('qr/kart/' . $main['id'])) ?>" target="_blank" rel="noopener">Baskıya hazır kart</a>
        <a class="btn btn--ghost" href="<?= e(admin_url('qr/svg/' . $main['id'])) ?>">SVG indir</a>
        <a class="btn btn--ghost" href="<?= e(admin_url('qr/png/' . $main['id'])) ?>">PNG indir</a>
      </div>

      <p class="hint" style="margin:1rem 0 0">
        Matbaaya <b>SVG</b> verin — büyütünce bozulmaz. PNG ekran ve sosyal medya için yeterli.
        Kodun en az <b>2,5 cm</b> basılması ve çevresinde boşluk bırakılması gerekir.
      </p>

      <form method="post" action="<?= e(admin_url('qr/sifirla/' . $main['id'])) ?>" style="margin-top:.8rem">
        <?= csrf_field() ?><button class="mini" type="submit">Okutma sayacını sıfırla</button>
      </form>
    </div>
  </div>
</section>
<?php endif; ?>

<details class="panel extras"<?= $extras || $errors ? ' open' : '' ?>>
  <summary>
    <h2>Ek kodlar</h2>
    <span>Kampanya afişi, vitrin ya da doğrudan bir kategoriye gitmesi için ayrı kod<?= $extras ? ' — ' . count($extras) . ' tane' : '' ?></span>
  </summary>

  <div class="extras__body">
    <p class="hint">
      Çoğu işletmenin buna ihtiyacı olmaz. Yalnızca <b>nereden okunduğunu ayrı saymak</b>
      istediğinizde (örneğin vitrine astığınız afiş) ya da kodun doğrudan bir kategoriye
      gitmesini istediğinizde ek kod oluşturun.
    </p>

    <div class="cols cols--qr">
      <form method="post" class="admform admform--tight">
        <?= csrf_field() ?>

        <div class="field<?= isset($errors['label']) ? ' has-err' : '' ?>">
          <label class="field__label" for="q-label">Etiket <i>*</i></label>
          <input id="q-label" name="label" type="text" required
                 value="<?= e((string) ($_POST['label'] ?? '')) ?>" placeholder="örn. Vitrin afişi / Kapı girişi">
          <?php if (isset($errors['label'])): ?><span class="field__err"><?= e($errors['label']) ?></span><?php endif; ?>
          <span class="field__hint">Yalnızca sizin görmeniz için; müşteriye görünmez.</span>
        </div>

        <div class="field<?= isset($errors['slug']) ? ' has-err' : '' ?>">
          <label class="field__label" for="q-slug">Kısa ad</label>
          <input id="q-slug" name="slug" type="text" value="<?= e((string) ($_POST['slug'] ?? '')) ?>" placeholder="vitrin">
          <?php if (isset($errors['slug'])): ?><span class="field__err"><?= e($errors['slug']) ?></span><?php endif; ?>
          <span class="field__hint">Adresin sonuna eklenir. Kod basıldıktan sonra değiştirmeyin.</span>
        </div>

        <div class="field">
          <label class="field__label" for="q-kind">Nereye gitsin?</label>
          <select id="q-kind" name="kind" data-qr-kind>
            <?php foreach ($kinds as $k => $lbl): ?>
            <option value="<?= e($k) ?>" <?= ($_POST['kind'] ?? '') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field" data-qr-target="category" hidden>
          <label class="field__label" for="q-cat">Kategori</label>
          <select id="q-cat" name="target_category">
            <?php foreach ($cats as $c): ?>
            <option value="<?= e((string) $c['slug']) ?>"><?= e((string) $c['name_tr']) ?></option>
            <?php endforeach; ?>
          </select>
          <span class="field__hint">Kod okutulduğunda menü doğrudan o kategoriye kayar.</span>
        </div>

        <div class="field<?= isset($errors['target']) ? ' has-err' : '' ?>" data-qr-target="url" hidden>
          <label class="field__label" for="q-url">Adres</label>
          <input id="q-url" name="target_url" type="url" placeholder="https://www.instagram.com/kullaniciadiniz/"
                 value="<?= e((string) ($_POST['target_url'] ?? '')) ?>">
          <?php if (isset($errors['target'])): ?><span class="field__err"><?= e($errors['target']) ?></span><?php endif; ?>
        </div>

        <div class="admform__actions">
          <button class="btn btn--ghost" type="submit">Ek kod oluştur</button>
        </div>
      </form>

      <div>
        <?php if (!$extras): ?>
        <p class="empty">Ek kod yok — menü kodu tek başına yeterli.</p>
        <?php else: ?>
        <ul class="qrlist">
          <?php foreach ($extras as $q): $url = admin_qr_url($q); ?>
          <li class="qrrow">
            <div class="qrrow__code"><?= QrCode::svg($url, 4, 2, QrCode::M, '#33231D', '#FFFFFF') ?></div>

            <div class="qrrow__info">
              <b><?= e((string) $q['label']) ?></b>
              <p class="qrrow__meta">
                <?= e($kinds[$q['kind']] ?? $q['kind']) ?>
                <?php if ($q['target']): ?> · <?= e((string) $q['target']) ?><?php endif; ?>
              </p>
              <p class="qrrow__url"><a href="<?= e($url) ?>" target="_blank" rel="noopener"><?= e($url) ?></a></p>
              <p class="qrrow__scans"><b><?= (int) $q['scans'] ?></b> okutma</p>
            </div>

            <div class="qrrow__act">
              <a class="mini" href="<?= e(admin_url('qr/kart/' . $q['id'])) ?>" target="_blank" rel="noopener">Kart</a>
              <a class="mini" href="<?= e(admin_url('qr/svg/' . $q['id'])) ?>">SVG</a>
              <a class="mini" href="<?= e(admin_url('qr/png/' . $q['id'])) ?>">PNG</a>
              <form method="post" action="<?= e(admin_url('qr/sil/' . $q['id'])) ?>"
                    onsubmit="return confirm('Bu kod silinecek. Basılı kopyalar çalışmaz hâle gelir. Emin misiniz?')">
                <?= csrf_field() ?><button class="mini mini--danger" type="submit">Sil</button>
              </form>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</details>
