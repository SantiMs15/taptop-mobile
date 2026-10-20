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

    function nearestIndex() {
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
      // Al final del recorrido, el último slide cuenta como actual aunque no llegue al borde.
      if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 4) {
        best = items.length - 1;
      }
      return best;
    }

    function update() {
      frame = 0;
      current = nearestIndex();
      items.forEach(function (item, index) {
        item.classList.toggle('is-dim', index !== current);
      });
      dots.forEach(function (dot, index) {
        if (index === current) {
          dot.setAttribute('aria-current', 'true');
        } else {
          dot.removeAttribute('aria-current');
        }
      });
      if (prev) {
        prev.disabled = current === 0;
      }
      if (next) {
        next.disabled = current === items.length - 1;
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
      prev.addEventListener('click', function () { goTo(current - 1); });
    }
    if (next) {
      next.addEventListener('click', function () { goTo(current + 1); });
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
