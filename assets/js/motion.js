/* Old Babyloon — elle yazılmış devinim motoru. Kütüphane yok.
   Üç iş yapar: görünüme girenleri açar, başlığı yapışkan duruma sokar, hero'yu döndürür. */
(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* --- 1. Görünüme girince açılma -------------------------------------- */
  function revealAll() {
    var nodes = document.querySelectorAll('.reveal');
    for (var i = 0; i < nodes.length; i++) { nodes[i].classList.add('is-in'); }
  }

  if (!('IntersectionObserver' in window) || reduced) {
    revealAll();
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        var el = entry.target;
        // Kardeşler arası kademe — grubun kendisi değil, öğeler gecikir
        var group = el.parentElement;
        if (group && group.hasAttribute('data-stagger')) {
          var idx = Array.prototype.indexOf.call(group.children, el);
          el.style.setProperty('--d', Math.min(idx, 8) * 90 + 'ms');
        }
        el.classList.add('is-in');
        io.unobserve(el);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

    // Gözlemci HER ZAMAN .reveal öğesinin kendisine bağlanır — sarmalayıcıya değil.
    document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });

    // Güvenlik ağı: gözlemci bir öğeyi kaçırırsa (programatik kaydırma,
    // sekme geri gelmesi vb.) kaydırma sırasında da kontrol et.
    var sweeping = false;
    var sweep = function () {
      if (sweeping) { return; }
      sweeping = true;
      requestAnimationFrame(function () {
        var left = document.querySelectorAll('.reveal:not(.is-in)');
        left.forEach(function (el) {
          var r = el.getBoundingClientRect();
          if (r.top < window.innerHeight * 0.95 && r.bottom > 0) { el.classList.add('is-in'); }
        });
        if (!left.length) { window.removeEventListener('scroll', sweep); }
        sweeping = false;
      });
    };
    window.addEventListener('scroll', sweep, { passive: true });
    setTimeout(sweep, 1200);

    // Son çare: gözlemci de kaydırma da çalışmazsa (eski tarayıcı,
    // otomasyon, agresif güç tasarrufu) beş saniye sonra aç.
    //
    // Yalnızca EKRANDA OLAN ve ÜSTTE KALAN öğeler açılır. Önceden
    // sayfadaki her şey açılıyordu; kullanıcı hero'yu beş saniye
    // okuyunca aşağısı o gelmeden açılmış oluyor ve aşağı indiğinde
    // hiçbir hareket görmüyordu.
    setTimeout(function () {
      document.querySelectorAll('.reveal:not(.is-in)').forEach(function (el) {
        if (el.getBoundingClientRect().top < window.innerHeight) { el.classList.add('is-in'); }
      });
    }, 5000);
  }

  /* --- 1b. Kaydırmaya bağlı derinlik ------------------------------------
     Büyük görseller çerçevesinden biraz daha yavaş ilerler.

     Gözlemci KULLANILMIYOR: hero slaytları açılışta görünmez durumda
     olduğu için gözlemci onları "ekran dışı" sayıp bir daha haber
     vermiyordu. Öğe sayısı azdır; her karede ölçmek daha ucuz ve daha
     doğru. Ekran dışındakiler zaten hesaplanmadan atlanıyor. */
  var plx = Array.prototype.slice.call(document.querySelectorAll('[data-parallax]'));
  if (plx.length && !reduced) {
    var plxQueued = false;
    var plxTick = function () {
      if (plxQueued) { return; }
      plxQueued = true;
      requestAnimationFrame(function () {
        plxQueued = false;
        var vh = window.innerHeight;
        for (var i = 0; i < plx.length; i++) {
          var el = plx[i];
          var box = (el.parentElement || el).getBoundingClientRect();
          if (box.bottom < -vh * 0.2 || box.top > vh * 1.2) { continue; }
          // -1 (öğe altta) .. +1 (öğe üstte)
          var p = (vh / 2 - (box.top + box.height / 2)) / (vh / 2 + box.height / 2);
          var amount = parseFloat(el.dataset.parallax) || 0.055;
          el.style.setProperty('--py', (p * amount * box.height).toFixed(1) + 'px');
        }
      });
    };
    window.addEventListener('scroll', plxTick, { passive: true });
    window.addEventListener('resize', plxTick, { passive: true });
    plxTick();
  }

  /* --- 1c. Kaydırmaya bağlı açılma — view() olmayan tarayıcılar için ------
     Firefox (ve eski Safari) CSS'in view() zaman çizelgesini bilmiyor.
     Orada aynı işi burada yapıyoruz: her öğeye 0..1 arası bir ilerleme
     yazılıyor, görünümü CSS ondan türetiyor.

     Tek seferlik açılmadan farkı, geri kaydırınca geri sarması —
     Apple sitelerindeki hissin kaynağı bu. */
  var hasViewTimeline = window.CSS && CSS.supports && CSS.supports('animation-timeline: view()');

  if (!hasViewTimeline && !reduced) {
    document.documentElement.classList.add('no-vt');

    var scrubEls = Array.prototype.slice.call(
      document.querySelectorAll('.reveal, .dmenu__art')
    );

    if (scrubEls.length) {
      var scrubQueued = false;
      var scrubTick = function () {
        if (scrubQueued) { return; }
        scrubQueued = true;
        requestAnimationFrame(function () {
          scrubQueued = false;
          var vh = window.innerHeight;
          for (var i = 0; i < scrubEls.length; i++) {
            var el = scrubEls[i];
            var r = el.getBoundingClientRect();
            if (r.top > vh || r.bottom < 0) {
              // Ekranın altındaysa kapalı, üstünde kaldıysa açık kalsın
              el.style.setProperty('--p', r.top > vh ? '0' : '1');
              continue;
            }
            // Alt kenardan girer (0), üstü ekranın %45'ine gelince biter (1)
            var span = vh * 0.55;
            var p = (vh - r.top) / span;
            el.style.setProperty('--p', Math.max(0, Math.min(1, p)).toFixed(3));
          }
        });
      };
      window.addEventListener('scroll', scrubTick, { passive: true });
      window.addEventListener('resize', scrubTick, { passive: true });
      scrubTick();
    }
  }

  /* --- 1d. Ertelenmiş görseller ------------------------------------------
     Hero'nun görünmeyen slaytları ilk yüklemede indirilmiyordu; sayfa
     yüklendikten sonra sessizce alınıyorlar. Böylece ilk açılışta yalnız
     görünen fotoğraf iniyor, karusel döndüğünde diğerleri hazır oluyor. */
  var ertelenen = function () {
    document.querySelectorAll('picture [data-srcset], img[data-src]').forEach(function (el) {
      if (el.dataset.srcset) { el.srcset = el.dataset.srcset; delete el.dataset.srcset; }
      if (el.dataset.src)    { el.src    = el.dataset.src;    delete el.dataset.src; }
    });
  };
  if (document.readyState === 'complete') { setTimeout(ertelenen, 200); }
  else { window.addEventListener('load', function () { setTimeout(ertelenen, 200); }); }

  /* --- 2. Yapışkan başlık gölgesi -------------------------------------- */
  var hdr = document.querySelector('[data-header]');
  if (hdr) {
    var ticking = false;
    var onScroll = function () {
      if (ticking) { return; }
      ticking = true;
      requestAnimationFrame(function () {
        hdr.classList.toggle('is-stuck', window.scrollY > 12);
        ticking = false;
      });
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* --- 3. Hero döngüsü --------------------------------------------------- */
  var slides = document.querySelectorAll('.hero__slide');
  if (slides.length > 1) {
    var dots = document.querySelectorAll('.hero__dots button');
    var current = 0;
    var timer = null;

    function go(n) {
      slides[current].classList.remove('is-active');
      if (dots[current]) { dots[current].setAttribute('aria-current', 'false'); }
      current = (n + slides.length) % slides.length;
      slides[current].classList.add('is-active');
      if (dots[current]) { dots[current].setAttribute('aria-current', 'true'); }
    }

    function start() {
      if (reduced) { return; }
      stop();
      timer = setInterval(function () { go(current + 1); }, 6500);
    }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }

    slides[0].classList.add('is-active');
    if (dots[0]) { dots[0].setAttribute('aria-current', 'true'); }

    dots.forEach(function (dot, i) {
      dot.addEventListener('click', function () { go(i); start(); });
    });
    /* Ana sayfadaki ok düğmeleri (varsa) */
    var prev = document.querySelector('[data-hero-prev]');
    var next = document.querySelector('[data-hero-next]');
    if (prev) { prev.addEventListener('click', function () { go(current - 1); start(); }); }
    if (next) { next.addEventListener('click', function () { go(current + 1); start(); }); }

    var heroEl = document.querySelector('.hero');
    if (heroEl) {
      heroEl.addEventListener('mouseenter', stop);
      heroEl.addEventListener('mouseleave', start);
    }
    document.addEventListener('visibilitychange', function () {
      document.hidden ? stop() : start();
    });
    start();
  } else if (slides.length === 1) {
    slides[0].classList.add('is-active');
  }
})();
