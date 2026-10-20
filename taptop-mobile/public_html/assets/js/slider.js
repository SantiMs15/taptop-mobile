/**
 * Slides de descripción del detalle (6.2). El desplazamiento es nativo (scroll-snap), así el
 * swipe funciona sin JavaScript; aquí se agregan flechas, puntos y el titular del slide
 * visible a plena opacidad (los demás quedan tenues).
 */
(function () {
  'use strict';

  var doc = document;
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  function toArray(list) {
    return Array.prototype.slice.call(list);
  }

  function initSlider(root) {
    var track = root.querySelector('[data-slider-track]');
    var items = toArray(root.querySelectorAll('[data-slider-item]'));
    var dots = toArray(root.querySelectorAll('[data-slider-dot]'));
    var prev = root.querySelector('[data-slider-prev]');
    var next = root.querySelector('[data-slider-next]');
    var controls = root.querySelector('[data-slider-controls]');
    var dotBar = root.querySelector('[data-slider-dots]');
    if (!track || items.length < 2) {
      return;
    }
    if (controls) {
      controls.hidden = false;
    }
    if (dotBar) {
      dotBar.hidden = false;
    }

    var current = 0;
    var frame = 0;

    function startOffset() {
      return parseFloat(window.getComputedStyle(track).paddingLeft) || 0;
    }

    /** Slide alineado al inicio del recorrido: desde aquí se mueven las flechas. */
    function leadingIndex() {
      var left = track.scrollLeft + startOffset();
      var best = 0;
      var bestDistance = Infinity;
      items.forEach(function (item, index) {
        var distance = Math.abs(item.offsetLeft - left);
        if (distance < bestDistance) {
          bestDistance = distance;
          best = index;
        }
      });
      return best;
    }

    function atEnd() {
      return track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
    }

    /** En pantallas anchas caben varios slides: solo se atenúan los que quedan cortados en el borde. */
    function fullyVisible(item) {
      var pad = startOffset();
      var left = track.scrollLeft + pad;
      var right = track.scrollLeft + track.clientWidth - pad;
      return item.offsetLeft >= left - 4 && item.offsetLeft + item.offsetWidth <= right + 4;
    }

    function update() {
      frame = 0;
      var leading = leadingIndex();
      // Al final del recorrido, el último slide cuenta como actual aunque no llegue al borde.
      current = atEnd() ? items.length - 1 : leading;
      items.forEach(function (item, index) {
        item.classList.toggle('is-dim', index !== current && !fullyVisible(item));
      });
      dots.forEach(function (dot, index) {
        if (index === current) {
          dot.setAttribute('aria-current', 'true');
        } else {
          dot.removeAttribute('aria-current');
        }
      });
      // Las flechas siguen el desplazamiento real: en pantallas anchas casi todo cabe y el
      // recorrido termina antes de que el último slide llegue al borde.
      if (prev) {
        prev.disabled = track.scrollLeft <= 4;
      }
      if (next) {
        next.disabled = atEnd();
      }
    }

    function goTo(index) {
      var target = Math.max(0, Math.min(items.length - 1, index));
      track.scrollTo({
        left: items[target].offsetLeft - startOffset(),
        behavior: reduceMotion.matches ? 'auto' : 'smooth'
      });
    }

    track.style.position = 'relative';
    track.addEventListener('scroll', function () {
      if (!frame) {
        frame = window.requestAnimationFrame(update);
      }
    }, { passive: true });
    if (prev) {
      prev.addEventListener('click', function () { goTo(leadingIndex() - 1); });
    }
    if (next) {
      next.addEventListener('click', function () { goTo(leadingIndex() + 1); });
    }
    dots.forEach(function (dot, index) {
      dot.addEventListener('click', function () { goTo(index); });
    });
    window.addEventListener('resize', function () {
      if (!frame) {
        frame = window.requestAnimationFrame(update);
      }
    });
    update();
  }

  toArray(doc.querySelectorAll('[data-slider]')).forEach(initSlider);
})();
