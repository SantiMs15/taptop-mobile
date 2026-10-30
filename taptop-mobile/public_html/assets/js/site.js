/**
 * Taptop Mobile: comportamiento común.
 * - Header: transparente sobre el escenario del Inicio, vidrio al hacer scroll; menú móvil.
 * - Interruptor de vidrio (.switch): la cápsula sigue al hover o al foco y vuelve a la
 *   opción activa. La posición va en variables CSS (--cap-x, --cap-w), nunca en left/width
 *   en línea, para que los estados por clase sigan mandando.
 * - Brillo del vidrio que sigue al puntero (--glass-x, --glass-y).
 */
(function () {
  'use strict';

  var doc = document;

  function debounce(fn, wait) {
    var timer = null;
    return function () {
      clearTimeout(timer);
      timer = setTimeout(fn, wait);
    };
  }

  function onMediaChange(query, handler) {
    if (query.addEventListener) {
      query.addEventListener('change', handler);
    } else if (query.addListener) {
      query.addListener(handler);
    }
  }

  // --- Header -----------------------------------------------------------------
  function initHeader() {
    var header = doc.querySelector('[data-header]');
    if (!header) {
      return;
    }
    var stage = doc.querySelector('[data-carousel]');

    if (header.classList.contains('site-header--overlay') && 'IntersectionObserver' in window) {
      var sentinel = doc.createElement('div');
      sentinel.setAttribute('aria-hidden', 'true');
      sentinel.style.cssText = 'position:absolute;top:0;left:0;width:1px;height:48px;pointer-events:none;';
      doc.body.insertBefore(sentinel, doc.body.firstChild);
      new IntersectionObserver(function (entries) {
        header.classList.toggle('is-scrolled', !entries[0].isIntersecting);
      }).observe(sentinel);
    } else if (header.classList.contains('site-header--overlay')) {
      header.classList.add('is-scrolled');
    }

    if (stage && 'IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        doc.body.classList.toggle('hero-in-view', entries[0].isIntersecting);
      }, { threshold: 0.4 }).observe(stage);
    }

    var toggle = header.querySelector('[data-menu-toggle]');
    var nav = doc.getElementById('site-nav');
    if (!toggle || !nav) {
      return;
    }
    function setOpen(open) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      header.classList.toggle('is-open', open);
    }
    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });
    doc.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && header.classList.contains('is-open')) {
        setOpen(false);
        toggle.focus();
      }
    });
    var desktop = window.matchMedia('(min-width: 900px)');
    onMediaChange(desktop, function () {
      if (desktop.matches) {
        setOpen(false);
      }
    });
  }

  // --- Brillo del vidrio que sigue al puntero ------------------------------------
  function trackGlass(element) {
    var frame = 0;
    var lastEvent = null;
    element.addEventListener('pointermove', function (event) {
      lastEvent = event;
      if (frame) {
        return;
      }
      frame = window.requestAnimationFrame(function () {
        frame = 0;
        var rect = element.getBoundingClientRect();
        if (!rect.width || !lastEvent) {
          return;
        }
        var x = ((lastEvent.clientX - rect.left) / rect.width) * 100;
        var y = ((lastEvent.clientY - rect.top) / rect.height) * 100;
        element.style.setProperty('--glass-x', x.toFixed(1) + '%');
        element.style.setProperty('--glass-y', y.toFixed(1) + '%');
      });
    });
  }

  // --- Interruptor de vidrio --------------------------------------------------------
  function initSwitch(element) {
    var capsule = element.querySelector('.switch__capsule');
    var items = Array.prototype.slice.call(element.querySelectorAll('.switch__item'));
    if (!capsule || !items.length) {
      return;
    }

    function activeItem() {
      return element.querySelector('.switch__item[aria-current="page"], .switch__item[aria-selected="true"]');
    }

    function moveTo(item) {
      if (!item || !item.offsetWidth || window.getComputedStyle(capsule).display === 'none') {
        element.classList.remove('has-capsule');
        return;
      }
      var box = element.getBoundingClientRect();
      var rect = item.getBoundingClientRect();
      element.style.setProperty('--cap-x', (rect.left - box.left) + 'px');
      element.style.setProperty('--cap-w', rect.width + 'px');
      element.classList.add('has-capsule');
      items.forEach(function (other) {
        other.classList.toggle('is-highlighted', other === item);
      });
    }

    function rest() {
      var focused = doc.activeElement;
      if (focused && items.indexOf(focused) !== -1 && focused.matches(':focus-visible')) {
        moveTo(focused);
        return;
      }
      var active = activeItem();
      if (active) {
        moveTo(active);
      } else {
        element.classList.remove('has-capsule');
        items.forEach(function (other) {
          other.classList.remove('is-highlighted');
        });
      }
    }

    function restWithoutAnimation() {
      element.classList.add('no-anim');
      rest();
      element.getBoundingClientRect();
      element.classList.remove('no-anim');
    }

    items.forEach(function (item) {
      item.addEventListener('pointerenter', function (event) {
        if (event.pointerType === 'mouse') {
          moveTo(item);
        }
      });
      item.addEventListener('focus', function () {
        if (item.matches(':focus-visible')) {
          moveTo(item);
        }
      });
      item.addEventListener('blur', function () {
        window.requestAnimationFrame(rest);
      });
    });
    element.addEventListener('pointerleave', rest);
    element.addEventListener('tt:sync', rest);
    window.addEventListener('resize', debounce(restWithoutAnimation, 120));
    if (doc.fonts && doc.fonts.ready) {
      doc.fonts.ready.then(restWithoutAnimation);
    }
    trackGlass(element);
    restWithoutAnimation();
  }

  // --- Entrada al hacer scroll ------------------------------------------------
  // Solo lo que al cargar está bajo el pliegue: lo visible no se oculta (sin parpadeo ni
  // retraso del LCP). Una vez por elemento; los que entran juntos van escalonados (60 ms).
  var REVEAL = [
    '.section-title:not(.section-head *):not(.features__head *):not(.specs-section__head *)',
    '.section-head', '.category-index > li', '.grid-cards > *', '.pillar', '.cta-band__inner',
    '.features__head', '.features__track', '.specs-section__head', '.specs-section__body',
    '.info-block', '.list-count'
  ].join(',');

  function initReveal() {
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return;
    }
    var fold = window.innerHeight * 0.92;
    var pending = [];
    Array.prototype.forEach.call(doc.querySelectorAll(REVEAL), function (element) {
      // En una fila deslizable (Destacados en el celular) entra la fila entera: las tarjetas de
      // la derecha no cruzan el viewport y se quedarían ocultas hasta deslizar.
      var parent = element.parentElement;
      if (parent && parent.scrollWidth > parent.clientWidth + 1 && /auto|scroll/.test(window.getComputedStyle(parent).overflowX)) {
        element = parent;
      }
      if (pending.indexOf(element) === -1 && element.getBoundingClientRect().top > fold) {
        pending.push(element);
      }
    });
    if (!pending.length) {
      return;
    }
    function settle(element) {
      element.classList.remove('reveal', 'is-in');
      element.style.removeProperty('--reveal-delay');
    }
    var observer = new IntersectionObserver(function (entries) {
      var order = 0;
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }
        var element = entry.target;
        observer.unobserve(element);
        element.style.setProperty('--reveal-delay', Math.min(order, 5) * 60 + 'ms');
        order++;
        element.classList.add('is-in');
        // Al terminar se quitan las clases: no queda nada que pelee con hover o :active.
        element.addEventListener('transitionend', function done(event) {
          if (event.target === element && event.propertyName === 'opacity') {
            element.removeEventListener('transitionend', done);
            settle(element);
          }
        });
      });
    }, { rootMargin: '0px 0px -8% 0px' });
    pending.forEach(function (element) {
      element.classList.add('reveal');
      observer.observe(element);
    });
  }

  function init() {
    initHeader();
    initReveal();
    Array.prototype.forEach.call(doc.querySelectorAll('[data-switch]'), initSwitch);
    Array.prototype.forEach.call(doc.querySelectorAll('.stage__rotation, .stage__arrow, .round-button'), trackGlass);
  }

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
