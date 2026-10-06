<?php
/**
 * Menü kitapçığı yönetimi.
 *
 * İki parça vardır ve ikisi ayrı ayrı çalışır:
 *   1. PDF — sitedeki "PDF indir" bağlantısı
 *   2. Sayfa görselleri — çevrilen kitapçığın kendisi
 * Sunucu PDF'i görsele çevirebiliyorsa ikisi tek adımda halledilir.
 */
$pages = $data['pages'];
$pdf   = $data['pdf'];
$conv  = $data['converter'];
$notice = $data['notice'];

$kb = function (int $b): string {
    return $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.') . ' MB'
                         : number_format(max($b, 1) / 1024, 0, ',', '.') . ' KB';
};
?>

<?php if ($notice): ?>
<p class="adm__flash<?= $notice['ok'] ? '' : ' adm__flash--err' ?> bookflash" role="status">
  <b><?= e($notice['msg']) ?></b>
  <?php if ($notice['detail']): ?><span class="bookflash__d"><?= e($notice['detail']) ?></span><?php endif; ?>
</p>
<?php endif; ?>

<section class="panel">
  <header class="panel__h"><h2>Menü PDF'i</h2></header>
  <p class="hint">
    Sitede <b>“PDF indir”</b> bağlantısı olarak sunulur. Yeni menü bastırdığınızda
    matbaadan gelen PDF'i buraya yükleyin.
  </p>

  <?php if ($pdf): ?>
  <div class="bookpdf">
    <span class="bookpdf__ico" aria-hidden="true">PDF</span>
    <div class="bookpdf__info">
      <a href="<?= e(asset($pdf)) ?>" target="_blank" rel="noopener"><?= e(basename($pdf)) ?></a>
      <p class="hint">
        <?= e($kb((int) $data['pdfSize'])) ?>
        <?php if ($data['pdfDate']): ?> · yüklendi: <?= e(ob_date(date('Y-m-d H:i:s', (int) $data['pdfDate']), 'd.m.Y H:i')) ?><?php endif; ?>
      </p>
    </div>
    <form method="post" action="<?= e(admin_url('kitapcik/pdf-sil')) ?>"
          onsubmit="return confirm('PDF kaldırılsın mı? Sitedeki indirme bağlantısı da kalkar.')">
      <?= csrf_field() ?><button class="mini mini--danger" type="submit">Kaldır</button>
    </form>
  </div>
  <?php else: ?>
  <p class="empty">Henüz PDF yüklenmemiş — sitede indirme bağlantısı görünmüyor.</p>
  <?php endif; ?>

  <form class="bookup" method="post" action="<?= e(admin_url('kitapcik/yukle')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="field">
      <label class="field__label" for="pdf"><?= $pdf ? 'PDF’i değiştir' : 'PDF yükle' ?></label>
      <input type="file" id="pdf" name="pdf" accept="application/pdf,.pdf" required>
      <span class="field__hint">En fazla <?= (int) $data['maxMb'] ?> MB.</span>
    </div>

    <?php if ($conv): ?>
    <label class="bookup__c">
      <input type="checkbox" name="regen" value="1" checked>
      <span>Sayfa görsellerini de bu PDF'ten üret <b>(mevcut sayfaların yerine geçer)</b></span>
    </label>
    <?php endif; ?>

    <div class="bookup__act"><button class="btn" type="submit">Yükle</button></div>
  </form>
</section>

<section class="panel">
  <header class="panel__h">
    <h2>Sayfa görselleri</h2>
    <a class="btn btn--ghost" href="<?= e(admin_url('kaynak/menu_pages/liste')) ?>">Sayfaları düzenle</a>
  </header>

  <p class="hint">
    Kitapçıkta çevrilen sayfalar bunlardır. Sıra soldan sağa çevrilme sırasıdır —
    <b>ilk sayfa kapaktır</b>.
  </p>

  <?php if ($conv): ?>
  <div class="convbox convbox--ok">
    <p><b>Bu sunucu PDF'i görsele çevirebiliyor.</b> <span class="hint"><?= e($conv['label']) ?></span></p>
    <?php if ($pdf): ?>
    <form method="post" action="<?= e(admin_url('kitapcik/sayfalar')) ?>"
          onsubmit="return confirm('Mevcut <?= count($pages) ?> sayfa silinip PDF’ten yeniden üretilecek. Sürdürülsün mü?')">
      <?= csrf_field() ?><button class="btn btn--ghost" type="submit">Sayfaları PDF'ten yeniden üret</button>
    </form>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <div class="convbox">
    <p>
      <b>Bu sunucuda PDF dönüştürücü yok.</b>
      PDF'i yüklemek indirme bağlantısını çalıştırır, ancak sayfa görselleri kendiliğinden
      üretilemez — onları <b>Sayfaları düzenle</b>'den tek tek yüklemeniz gerekir.
    </p>
    <p class="hint">
      PDF'i sayfa sayfa JPG'ye çevirmek için herhangi bir çevrimiçi “PDF to JPG” aracı ya da
      Adobe Acrobat kullanılabilir. Tüm sayfalar aynı oranda ve dikey olmalıdır.
    </p>
  </div>
  <?php endif; ?>

  <?php if ($pages): ?>
  <ol class="bookpages">
    <?php foreach ($pages as $i => $p): ?>
    <li class="bookpages__i<?= (int) $p['is_active'] ? '' : ' is-off' ?>">
      <a href="<?= e(admin_url('kaynak/menu_pages/duzenle/' . $p['id'])) ?>">
        <img src="<?= e(asset((string) $p['image'])) ?>" alt="" loading="lazy">
        <span><?= $i + 1 ?><?= (int) $p['is_active'] ? '' : ' · gizli' ?></span>
      </a>
    </li>
    <?php endforeach; ?>
  </ol>
  <?php else: ?>
  <p class="empty">Henüz sayfa yok — kitapçık sayfası açılmıyor.</p>
  <?php endif; ?>
</section>
