/* Old Babyloon — menü kitapçığı. Kütüphane yok.

   Kitap mantığı: her yaprak iki yüzlü. Yaprak çevrilince arkası solda,
   bir sonraki yaprağın önü sağda görünür. Dar ekranda yaprak çevirme yerine
   tek sayfa gösterilir (CSS o düzeni kurar, buradaki sayaç ortaktır). */
(function () {
  'use strict';

  var book = document.querySelector('[data-book]');
  if (!book) { return; }

  var stage  = book.querySelector('.book__stage');
  var leaves = Array.prototype.slice.call(book.querySelectorAll('.book__leaf'));
  var total  = parseInt(book.dataset.total, 10) || leaves.length * 2;
  var count  = document.querySelector('[data-book-count]');
  var prevBtns = document.querySelectorAll('[data-book-prev]');
  var nextBtns = document.querySelectorAll('[data-book-next]');
  if (!leaves.length) { return; }

  // Kaç yaprak çevrildi: 0 = kapak açık, leaves.length = kitap bitti
  var flipped = 0;
  var narrow = function () { return window.matchMedia('(max-width: 760px)').matches; };

  /* Sayaç metni — sunucudan gelen kalıbı yeniden kullanır ("Sayfa 3 / 8") */
  var pattern = count ? count.textContent.trim() : '';
  /* Kitap açıkken iki sayfa görünür; sayaç da aralık yazar: "Sayfa 2–3 / 8" */
  var label = function (from, to) {
    var shown = (to && to !== from) ? from + '\u2013' + to : String(from);
    if (!pattern) { return shown + ' / ' + total; }
    return pattern.replace(/\d+/, shown).replace(/\d+(?!.*\d)/, String(total));
  };

  function render() {
    leaves.forEach(function (leaf, i) {
      var isFlipped = i < flipped;
      leaf.classList.toggle('is-flipped', isFlipped);
      // Çevrilmemişler sağda üst üste: ilk sıradaki en üstte
      leaf.style.zIndex = String(isFlipped ? i + 1 : leaves.length - i);

    });

    // Dar ekranda yaprak çevrilmez; o sayfayı taşıyan yaprağın bir yüzü gösterilir
    if (narrow()) {
      var leafIndex = Math.min(Math.floor(pageNo / 2), leaves.length - 1);
      leaves.forEach(function (leaf, i) {
        leaf.classList.toggle('is-current', i === leafIndex);
        leaf.classList.toggle('show-back', i === leafIndex && pageNo % 2 === 1);
      });
    }

    if (count) {
      var span = visiblePages();
      count.textContent = label(span[0], span[1]);
    }

    prevBtns.forEach(function (b) { b.disabled = atStart(); });
    nextBtns.forEach(function (b) { b.disabled = atEnd(); });
  }

  var pageNo = 0; // dar ekran için 0 tabanlı sayfa numarası

  /* O anda görünen sayfa(lar): [ilk, son]. Kapakta ve son yaprakta tek. */
  function visiblePages() {
    if (narrow()) { var n = Math.min(pageNo + 1, total); return [n, n]; }
    if (flipped === 0) { return [1, 1]; }
    var left = Math.min(flipped * 2, total);
    var right = left + 1;
    return right <= total ? [left, right] : [left, left];
  }
  function currentPageNumber() { return visiblePages()[0]; }
  function atStart() { return narrow() ? pageNo === 0 : flipped === 0; }
  function atEnd() {
    return narrow() ? pageNo >= total - 1 : flipped >= leaves.length;
  }

  function next() {
    if (atEnd()) { return; }
    if (narrow()) { pageNo++; } else { flipped++; }
    render();
  }
  function prev() {
    if (atStart()) { return; }
    if (narrow()) { pageNo--; } else { flipped--; }
    render();
  }

  nextBtns.forEach(function (b) { b.addEventListener('click', next); });
  prevBtns.forEach(function (b) { b.addEventListener('click', prev); });

  /* Klavye — kitap görünürken */
  document.addEventListener('keydown', function (ev) {
    if (ev.key !== 'ArrowRight' && ev.key !== 'ArrowLeft') { return; }
    var r = book.getBoundingClientRect();
    if (r.bottom < 0 || r.top > window.innerHeight) { return; }
    if (ev.target.matches('input, textarea, select')) { return; }
    ev.preventDefault();
    ev.key === 'ArrowRight' ? next() : prev();
  });

  /* Dokunmatik kaydırma */
  var x0 = null, y0 = null;
  stage.addEventListener('touchstart', function (ev) {
    x0 = ev.touches[0].clientX; y0 = ev.touches[0].clientY;
  }, { passive: true });
  stage.addEventListener('touchend', function (ev) {
    if (x0 === null) { return; }
    var dx = ev.changedTouches[0].clientX - x0;
    var dy = ev.changedTouches[0].clientY - y0;
    // Yatay hareket dikeyden belirgin şekilde büyükse sayfa çevir
    if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.5) { dx < 0 ? next() : prev(); }
    x0 = y0 = null;
  }, { passive: true });

  /* Ekran genişliği düzen değiştirdiğinde sayaçları eşitle */
  var wasNarrow = narrow();
  window.addEventListener('resize', function () {
    var isNarrow = narrow();
    if (isNarrow === wasNarrow) { return; }
    // Geniş → dar: açık sayfadan devam et. Dar → geniş: o sayfanın yaprağına geç.
    if (isNarrow) { pageNo = Math.max(0, currentPageNumberForSwitch() - 1); }
    else { flipped = Math.min(Math.ceil(pageNo / 2), leaves.length); }
    wasNarrow = isNarrow;
    render();
  });
  function currentPageNumberForSwitch() { return flipped === 0 ? 1 : Math.min(flipped * 2, total); }

  render();
})();
