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
  /* --- Galeri: toplu yükleme --------------------------------------------
     Dosyalar tek tek gönderilir: sunucunun POST boyutu ve dosya sayısı
     sınırına takılmaz, her fotoğrafın durumu ayrı görünür. JS kapalıysa
     form hepsini birden normal yoldan gönderir. */
  var bulk = document.querySelector('[data-bulk-upload]');
  if (bulk) {
    var input  = bulk.querySelector('[data-bulk-input]');
    var drop   = bulk.querySelector('[data-bulk-drop]');
    var list   = bulk.querySelector('[data-bulk-list]');
    var status = bulk.querySelector('[data-bulk-status]');
    var submit = bulk.querySelector('[data-bulk-submit]');
    var max    = parseInt(bulk.dataset.max, 10) || 0;
    var picked = [];
    drop.classList.add('is-enhanced'); // yerel dosya düğmesi gizlenir, kutunun kendisi seçtirir

    var mb = function (n) { return (n / 1048576).toFixed(1).replace('.', ',') + ' MB'; };

    var render = function () {
      list.innerHTML = '';
      picked.forEach(function (f) {
        var li = document.createElement('li');
        li.textContent = f.name + ' · ' + mb(f.size);
        if (max && f.size > max) { li.className = 'is-err'; li.textContent += ' — çok büyük, atlanacak'; }
        f._li = li;
        list.appendChild(li);
      });
      list.hidden = !picked.length;
      status.textContent = picked.length ? picked.length + ' fotoğraf seçildi.' : '';
    };

    var take = function (files) {
      picked = Array.prototype.filter.call(files, function (f) { return /^image\//.test(f.type); });
      render();
    };

    input.addEventListener('change', function () { take(input.files); });
    ['dragenter', 'dragover'].forEach(function (ev) {
      drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-over'); });
    });
    drop.addEventListener('drop', function (e) { if (e.dataTransfer) { take(e.dataTransfer.files); } });

    bulk.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!picked.length) { status.textContent = 'Önce fotoğraf seçin.'; return; }
      submit.disabled = true;
      input.disabled = true;
      var token = bulk.querySelector('input[name="_token"]').value;
      var url = bulk.getAttribute('action') + (bulk.getAttribute('action').indexOf('?') < 0 ? '?' : '&') + 'json=1';
      var ok = 0, fail = 0, i = 0;

      var next = function () {
        if (i >= picked.length) {
          status.textContent = ok + ' fotoğraf eklendi' + (fail ? ', ' + fail + ' eklenemedi.' : '.');
          if (!fail) { window.location.href = bulk.dataset.done; return; }
          submit.disabled = false;
          input.disabled = false;
          var back = document.createElement('a');
          back.className = 'btn btn--ghost';
          back.href = bulk.dataset.done;
          back.textContent = 'Galeriye dön';
          status.appendChild(document.createTextNode(' '));
          status.appendChild(back);
          return;
        }
        var f = picked[i++];
        status.textContent = i + ' / ' + picked.length + ' yükleniyor…';
        if (max && f.size > max) { fail++; next(); return; }
        f._li.className = 'is-busy';
        var fd = new FormData();
        fd.append('_token', token);
        fd.append('images[]', f, f.name);
        fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json().catch(function () { return { ok: false, errors: ['Sunucu hatası (' + r.status + ')'] }; }); })
          .then(function (j) {
            if (j.ok && j.added) { ok++; f._li.className = 'is-ok'; f._li.textContent = f.name + ' — eklendi'; }
            else { fail++; f._li.className = 'is-err'; f._li.textContent = (j.errors && j.errors[0]) || (f.name + ' — eklenemedi'); }
          })
          .catch(function () { fail++; f._li.className = 'is-err'; f._li.textContent = f.name + ' — bağlantı hatası'; })
          .then(next);
      };
      next();
    });
  }
})();
