/* Old Babyloon — arayüz etkileşimleri. */
(function () {
  'use strict';

  /* --- Mobil çekmece ---------------------------------------------------- */
  var drawer = document.getElementById('drawer');
  var openBtn = document.querySelector('[data-drawer-open]');
  var lastFocus = null;

  function openDrawer() {
    if (!drawer) { return; }
    lastFocus = document.activeElement;
    drawer.hidden = false;
    document.body.style.overflow = 'hidden';
    if (openBtn) { openBtn.setAttribute('aria-expanded', 'true'); }
    var first = drawer.querySelector('a, button');
    if (first) { first.focus(); }
  }

  function closeDrawer() {
    if (!drawer || drawer.hidden) { return; }
    drawer.hidden = true;
    document.body.style.overflow = '';
    if (openBtn) { openBtn.setAttribute('aria-expanded', 'false'); }
    if (lastFocus) { lastFocus.focus(); }
  }

  if (openBtn) { openBtn.addEventListener('click', openDrawer); }
  if (drawer) {
    drawer.addEventListener('click', function (ev) {
      if (ev.target === drawer || ev.target.closest('[data-drawer-close]')) { closeDrawer(); }
    });
  }
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') { closeDrawer(); }
  });

  /* --- Form: çift gönderimi engelle ------------------------------------- */
  document.querySelectorAll('form[data-once]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('[type="submit"]');
      if (!btn) { return; }
      setTimeout(function () {
        btn.disabled = true;
        if (btn.dataset.sending) { btn.textContent = btn.dataset.sending; }
      }, 0);
    });
  });

  /* --- Rezervasyon: bugünden öncesini engelle --------------------------- */
  var dateInput = document.querySelector('input[name="res_date"]');
  if (dateInput && !dateInput.min) {
    var d = new Date();
    dateInput.min = d.getFullYear() + '-' +
      String(d.getMonth() + 1).padStart(2, '0') + '-' +
      String(d.getDate()).padStart(2, '0');
  }

  /* --- QR menü: canlı arama + kategori takibi --------------------------- */
  var search = document.querySelector('[data-menu-search]');
  if (search) {
    var items = Array.prototype.slice.call(document.querySelectorAll('[data-item]'));
    var groups = Array.prototype.slice.call(document.querySelectorAll('[data-group]'));
    var empty = document.querySelector('[data-empty]');

    var normalize = function (s) {
      return (s || '').toLocaleLowerCase('tr')
        .replace(/ı/g, 'i').replace(/İ/g, 'i')
        .replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's')
        .replace(/ö/g, 'o').replace(/ç/g, 'c');
    };

    search.addEventListener('input', function () {
      var q = normalize(search.value.trim());
      var hits = 0;

      items.forEach(function (item) {
        var match = q === '' || normalize(item.dataset.item).indexOf(q) !== -1;
        item.hidden = !match;
        if (match) { hits++; }
      });

      groups.forEach(function (group) {
        var visible = group.querySelectorAll('[data-item]:not([hidden])').length;
        group.hidden = visible === 0;
      });

      if (empty) { empty.hidden = hits !== 0; }
    });
  }

  /* --- QR menü: kategori çipini aktif tut ------------------------------- */
  var chips = document.querySelectorAll('[data-chip]');
  if (chips.length && 'IntersectionObserver' in window) {
    var byId = {};
    chips.forEach(function (chip) { byId[chip.dataset.chip] = chip; });

    var spy = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        chips.forEach(function (c) { c.classList.remove('is-on'); });
        var chip = byId[entry.target.id];
        if (chip) {
          chip.classList.add('is-on');
          var bar = chip.parentElement;
          if (bar && bar.scrollWidth > bar.clientWidth) {
            bar.scrollTo({ left: chip.offsetLeft - 16, behavior: 'smooth' });
          }
        }
      });
    }, { rootMargin: '-25% 0px -65% 0px' });

    document.querySelectorAll('[data-group]').forEach(function (g) { spy.observe(g); });
  }

  /* --- Galeri ışık kutusu ------------------------------------------------
     Bir fotoğrafa tıklanınca büyür; oklarla, düğmelerle ya da parmakla
     diğerlerine geçilir. 112 fotoğraflı galeride tek tek kapatıp açmak
     çekilmezdi.

     Kutu bir kez kurulur, her açılışta yeniden kullanılır. Komşu
     fotoğraflar önden indirilir ki geçiş beklemesin. */
  var galleryLinks = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox]'));

  if (galleryLinks.length) {
    var items = galleryLinks.map(function (a) {
      var im = a.querySelector('img');
      return { src: a.getAttribute('href'), cap: (im && im.getAttribute('alt')) || '' };
    });

    var box = null, img = null, cap = null, sayac = null, prevBtn = null, nextBtn = null;
    var index = 0, oncekiOdak = null;

    function kur() {
      box = document.createElement('div');
      box.className = 'lightbox';
      box.setAttribute('role', 'dialog');
      box.setAttribute('aria-modal', 'true');
      box.hidden = true;
      box.innerHTML =
        '<button class="lightbox__x" type="button" aria-label="Kapat">&times;</button>' +
        '<button class="lightbox__nav lightbox__nav--prev" type="button" aria-label="Önceki fotoğraf"></button>' +
        '<figure class="lightbox__fig">' +
          '<img alt="">' +
          '<figcaption class="lightbox__cap"><span class="lightbox__t"></span>' +
          '<span class="lightbox__n"></span></figcaption>' +
        '</figure>' +
        '<button class="lightbox__nav lightbox__nav--next" type="button" aria-label="Sonraki fotoğraf"></button>';

      img     = box.querySelector('img');
      cap     = box.querySelector('.lightbox__t');
      sayac   = box.querySelector('.lightbox__n');
      prevBtn = box.querySelector('.lightbox__nav--prev');
      nextBtn = box.querySelector('.lightbox__nav--next');

      prevBtn.addEventListener('click', function () { git(-1); });
      nextBtn.addEventListener('click', function () { git(1); });
      box.querySelector('.lightbox__x').addEventListener('click', kapat);

      // Fotoğrafın dışına tıklanınca kapanır
      box.addEventListener('click', function (e) {
        if (e.target === box || e.target.classList.contains('lightbox__fig')) { kapat(); }
      });

      // Parmakla kaydırma
      var x0 = null, y0 = null;
      box.addEventListener('touchstart', function (e) {
        x0 = e.touches[0].clientX; y0 = e.touches[0].clientY;
      }, { passive: true });
      box.addEventListener('touchend', function (e) {
        if (x0 === null) { return; }
        var dx = e.changedTouches[0].clientX - x0;
        var dy = e.changedTouches[0].clientY - y0;
        if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.5) { git(dx < 0 ? 1 : -1); }
        x0 = y0 = null;
      }, { passive: true });

      document.body.appendChild(box);
    }

    /** Komşuları önden indir — geçiş anında beklenmesin. */
    function onYukle(i) {
      [i - 1, i + 1].forEach(function (k) {
        var it = items[(k + items.length) % items.length];
        if (it) { var p = new Image(); p.src = it.src; }
      });
    }

    function ciz() {
      var it = items[index];
      img.src = it.src;
      img.alt = it.cap;
      cap.textContent = it.cap;
      cap.hidden = it.cap === '';
      sayac.textContent = (index + 1) + ' / ' + items.length;
      // Tek fotoğraf varsa gezinmeye gerek yok
      var cok = items.length > 1;
      prevBtn.hidden = nextBtn.hidden = !cok;
      onYukle(index);
    }

    function git(yon) {
      index = (index + yon + items.length) % items.length;
      ciz();
    }

    function ac(i) {
      if (!box) { kur(); }
      oncekiOdak = document.activeElement;
      index = i;
      ciz();
      box.hidden = false;
      document.body.style.overflow = 'hidden';
      box.querySelector('.lightbox__x').focus();
    }

    function kapat() {
      if (!box || box.hidden) { return; }
      box.hidden = true;
      document.body.style.overflow = '';
      if (oncekiOdak && oncekiOdak.focus) { oncekiOdak.focus(); }
    }

    galleryLinks.forEach(function (link, i) {
      link.addEventListener('click', function (ev) {
        ev.preventDefault();
        ac(i);
      });
    });

    document.addEventListener('keydown', function (ev) {
      if (!box || box.hidden) { return; }
      if (ev.key === 'Escape')     { kapat(); }
      if (ev.key === 'ArrowLeft')  { ev.preventDefault(); git(-1); }
      if (ev.key === 'ArrowRight') { ev.preventDefault(); git(1); }
    });
  }
})();
