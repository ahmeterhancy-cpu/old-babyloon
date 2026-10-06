<?php $rows = $data['messages']; ?>

<?php if (!$rows): ?>
<p class="empty">Henüz mesaj yok. İletişim sayfasından gelen mesajlar burada listelenir.</p>
<?php else: ?>

<div class="msglist">
  <?php foreach ($rows as $m): ?>
  <article class="msg<?= (int) $m['is_read'] === 0 ? ' is-new' : '' ?>">
    <header class="msg__h">
      <div>
        <b><?= e($m['name']) ?></b>
        <?php if ((int) $m['is_read'] === 0): ?><span class="pill pill--on">Yeni</span><?php endif; ?>
        <p class="msg__meta">
          <?= e(date('d.m.Y H:i', strtotime((string) $m['created_at']))) ?>
          <?php if ($m['email']): ?> · <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a><?php endif; ?>
          <?php if ($m['phone']): ?> · <a href="tel:<?= e($m['phone']) ?>"><?= e($m['phone']) ?></a><?php endif; ?>
        </p>
      </div>
      <div class="msg__act">
        <?php if ((int) $m['is_read'] === 0): ?>
        <form method="post" action="<?= e(admin_url('mesajlar/oku/' . $m['id'])) ?>">
          <?= csrf_field() ?><button class="mini" type="submit">Okundu</button>
        </form>
        <?php endif; ?>
        <form method="post" action="<?= e(admin_url('mesajlar/sil/' . $m['id'])) ?>"
              onsubmit="return confirm('Bu mesaj silinecek. Emin misiniz?')">
          <?= csrf_field() ?><button class="mini mini--danger" type="submit">Sil</button>
        </form>
      </div>
    </header>
    <?php if ($m['subject']): ?><p class="msg__subject"><?= e($m['subject']) ?></p><?php endif; ?>
    <p class="msg__body"><?= nl2br(e((string) $m['body'])) ?></p>
  </article>
  <?php endforeach; ?>
</div>

<?php endif; ?>
