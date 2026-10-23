/* Shokher Kenakata theme scripts (no dependencies). */
(function () {
  'use strict';
  var d = document;

  /* Drawer */
  var drawer = d.getElementById('sk-drawer');
  function openDrawer() { if (!drawer) return; drawer.classList.add('is-open'); drawer.setAttribute('aria-hidden', 'false'); d.body.classList.add('sk-lock'); }
  function closeDrawer() { if (!drawer) return; drawer.classList.remove('is-open'); drawer.setAttribute('aria-hidden', 'true'); d.body.classList.remove('sk-lock'); }
  d.addEventListener('click', function (e) {
    if (e.target.closest('[data-sk-open="sk-drawer"]')) { e.preventDefault(); openDrawer(); }
    if (e.target.closest('[data-sk-close]')) { e.preventDefault(); closeDrawer(); }
    if (e.target.closest('[data-sk-search]')) {
      e.preventDefault();
      var inp = d.querySelector('.sk-search-mobile input[type=search]');
      if (inp) { window.scrollTo({ top: 0, behavior: 'smooth' }); setTimeout(function () { inp.focus(); }, 250); }
    }
  });
  d.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });

  /* Drawer sub-menu toggles */
  if (drawer) {
    drawer.querySelectorAll('.menu-item-has-children').forEach(function (li) {
      var b = d.createElement('button');
      b.type = 'button'; b.className = 'sk-sub-toggle'; b.setAttribute('aria-label', 'Toggle sub menu');
      b.innerHTML = '<svg class="sk-ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>';
      b.addEventListener('click', function () { li.classList.toggle('is-open'); });
      li.insertBefore(b, li.querySelector('.sub-menu'));
    });
  }

  /* Hero slider */
  d.querySelectorAll('[data-sk-slider]').forEach(function (sl) {
    var slides = sl.querySelectorAll('.sk-slide'), dots = sl.querySelectorAll('.sk-dots button'), i = 0, t;
    if (slides.length < 2) return;
    function go(n) {
      slides[i].classList.remove('is-active'); if (dots[i]) dots[i].classList.remove('is-active');
      i = (n + slides.length) % slides.length;
      slides[i].classList.add('is-active'); if (dots[i]) dots[i].classList.add('is-active');
    }
    function play() { clearInterval(t); t = setInterval(function () { go(i + 1); }, 5000); }
    var p = sl.querySelector('.sk-sl-prev'), n = sl.querySelector('.sk-sl-next');
    if (p) p.addEventListener('click', function () { go(i - 1); play(); });
    if (n) n.addEventListener('click', function () { go(i + 1); play(); });
    dots.forEach(function (dot, k) { dot.addEventListener('click', function () { go(k); play(); }); });
    var x0 = null;
    sl.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
    sl.addEventListener('touchend', function (e) {
      if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0;
      if (Math.abs(dx) > 40) { go(dx < 0 ? i + 1 : i - 1); play(); } x0 = null;
    });
    play();
  });

  /* Product rails */
  d.querySelectorAll('[data-sk-rail]').forEach(function (rail) {
    var row = rail.querySelector('.sk-row'), prev = rail.querySelector('.sk-rail-prev'), next = rail.querySelector('.sk-rail-next');
    if (!row) return;
    function step() { var c = row.querySelector('li'); return c ? (c.getBoundingClientRect().width + 14) * 2 : 300; }
    function upd() {
      if (prev) prev.disabled = row.scrollLeft < 5;
      if (next) next.disabled = row.scrollLeft + row.clientWidth >= row.scrollWidth - 5;
    }
    if (prev) prev.addEventListener('click', function () { row.scrollBy({ left: -step(), behavior: 'smooth' }); });
    if (next) next.addEventListener('click', function () { row.scrollBy({ left: step(), behavior: 'smooth' }); });
    row.addEventListener('scroll', upd, { passive: true });
    window.addEventListener('resize', upd);
    upd();
  });

  /* Back to top */
  var top = d.querySelector('.sk-totop');
  if (top) {
    window.addEventListener('scroll', function () { top.classList.toggle('is-show', window.scrollY > 600); }, { passive: true });
    top.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  }
})();
