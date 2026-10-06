/* Old Babyloon — yönetim paneli etkileşimleri. JS kapalıyken panel yine çalışır. */
(function () {
  'use strict';

  /* --- Ayarlar sekmeleri ------------------------------------------------ */
  var tabs = document.querySelectorAll('[data-tab]');
  if (tabs.length) {
    var panes = document.querySelectorAll('[data-pane]');
    var show = function (key) {
      tabs.forEach(function (t) { t.classList.toggle('is-on', t.dataset.tab === key); });
      panes.forEach(function (p) { p.classList.toggle('is-on', p.dataset.pane === key); });
      try { sessionStorage.setItem('ob-tab', key); } catch (e) { /* özel pencere */ }
    };
    tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.tab); }); });

    var saved = null;
    try { saved = sessionStorage.getItem('ob-tab'); } catch (e) { /* yoksay */ }
    if (saved && document.querySelector('[data-pane="' + saved + '"]')) { show(saved); }
  }

  /* --- QR: türe göre hedef alanını göster ------------------------------- */
  var kind = document.querySelector('[data-qr-kind]');
  if (kind) {
    var sync = function () {
      document.querySelectorAll('[data-qr-target]').forEach(function (box) {
        box.hidden = box.dataset.qrTarget !== kind.value;
      });
    };
    kind.addEventListener('change', sync);
    sync();
  }

  /* --- Kısa ad önerisi: başlıktan üret ---------------------------------- */
  var slugInput = document.querySelector('input[name="slug"]');
  if (slugInput && slugInput.value === '') {
    var source = document.querySelector('input[name="name_tr"], input[name="title_tr"], input[name="label"]');
    if (source) {
      var touched = false;
      slugInput.addEventListener('input', function () { touched = true; });
      source.addEventListener('input', function () {
        if (touched) { return; }
        slugInput.value = source.value
          .toLocaleLowerCase('tr')
          .replace(/ı/g, 'i').replace(/ğ/g, 'g').replace(/ü/g, 'u')
          .replace(/ş/g, 's').replace(/ö/g, 'o').replace(/ç/g, 'c')
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-+|-+$/g, '');
      });
    }
  }

  /* --- Görsel seçilince önizleme ---------------------------------------- */
  document.querySelectorAll('input[type="file"]').forEach(function (input) {
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file || !file.type.startsWith('image/')) { return; }
      var box = input.closest('.upload');
      if (!box) { return; }
      var prev = box.querySelector('.upload__preview');
      if (!prev) {
        prev = document.createElement('img');
        prev.className = 'upload__preview';
        prev.style.cssText = 'width:110px;height:110px;object-fit:cover;border-radius:10px;border:1px solid #E3DACF';
        box.appendChild(prev);
      }
      prev.src = URL.createObjectURL(file);
    });
  });

  /* --- Kaydedilmemiş değişiklik uyarısı --------------------------------- */
  var form = document.querySelector('.admform');
  if (form) {
    var dirty = false;
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (ev) {
      if (!dirty) { return; }
      ev.preventDefault();
      ev.returnValue = '';
    });
  }

  /* --- Toplu seçim ve silme ---------------------------------------------
     Kutular sıralama formunun içinde duruyor; buradaki iş yalnızca
     "tümünü seç"i yönetmek, sayacı güncellemek ve silmeden önce onay
     istemek. Silmeyi sunucu yapıyor. */
  var picks = Array.prototype.slice.call(document.querySelectorAll('[data-pick]'));
  var bar   = document.querySelector('[data-pickbar]');

  if (picks.length && bar) {
    var all   = document.querySelector('[data-pick-all]');
    var count = bar.querySelector('[data-pick-count]');
    var clear = bar.querySelector('[data-pick-clear]');

    var selected = function () { return picks.filter(function (c) { return c.checked; }); };

    var sync = function () {
      var n = selected().length;
      count.textContent = String(n);
      bar.hidden = n === 0;
      if (all) {
        all.checked = n > 0 && n === picks.length;
        all.indeterminate = n > 0 && n < picks.length;
      }
    };

    picks.forEach(function (c) { c.addEventListener('change', sync); });

    if (all) {
      all.addEventListener('change', function () {
        picks.forEach(function (c) { c.checked = all.checked; });
        sync();
      });
    }

    clear.addEventListener('click', function () {
      picks.forEach(function (c) { c.checked = false; });
      sync();
    });

    /* Silme formu kutuları içermez (ayrı formda). Seçili kimlikler
       gönderim anında gizli alan olarak kopyalanır — seçim yoksa ya da
       onay verilmezse form hiç gönderilmez. */
    bar.addEventListener('submit', function (ev) {
      bar.querySelectorAll('input[name="ids[]"]').forEach(function (el) { el.remove(); });

      var sel = selected();
      if (!sel.length) { ev.preventDefault(); return; }
      if (!confirm(sel.length + ' kayıt silinecek. Bu işlem geri alınamaz. Sürdürülsün mü?')) {
        ev.preventDefault();
        return;
      }
      sel.forEach(function (c) {
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = 'ids[]'; h.value = c.value;
        bar.appendChild(h);
      });
    });

    // Shift ile aralık seçme — uzun listelerde tek tek tıklamaktan kurtarır
    var last = null;
    picks.forEach(function (c, i) {
      c.addEventListener('click', function (ev) {
        if (ev.shiftKey && last !== null) {
          var a = Math.min(last, i), b = Math.max(last, i);
          for (var k = a; k <= b; k++) { picks[k].checked = c.checked; }
          sync();
        }
        last = i;
      });
    });

    sync();
  }
})();
