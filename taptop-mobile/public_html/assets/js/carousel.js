/**
 * Hero del Inicio: carrusel con flechas (adaptación del controlador LTX al brief 5.1).
 *
 * - Autoplay de 3 s (el slide del logo dura lo que indique data-duration). El tiempo lo lleva
 *   la animación CSS de un temporizador invisible: pausarla es pausar el carrusel, sin timers por cuadro.
 * - Pausa temporal al pasar el cursor por los controles o los textos, con foco de teclado,
 *   con la pestaña del navegador oculta, fuera de pantalla o mientras corre el intro. También mientras
 *   un dedo toca el hero y unos segundos al girar el logo 3D. "Reducir movimiento": sin autoplay.
 * - Transición sin cortes: el slide nuevo solo se muestra cuando su imagen está decodificada;
 *   el anterior sigue visible hasta que el nuevo terminó de entrar. Cada petición lleva un
 *   token; las respuestas viejas se ignoran y nunca se encolan transiciones.
 */
(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;
  var stage = doc.querySelector('[data-carousel]');
  if (!stage) {
    return;
  }

  var slides = toArray(stage.querySelectorAll('[data-slide]'));
  var timer = stage.querySelector('[data-timer]');
  var live = stage.querySelector('[data-live]');
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  if (slides.length < 2 || !timer) {
    return;
  }

  var state = {
    selected: 0,
    shown: 0,
    token: 0,
    stopped: reduceMotion.matches,
    holds: { intro: root.classList.contains('intro-on') },
    pendingAdvance: false,
    finishPending: null
  };

  function toArray(list) {
    return Array.prototype.slice.call(list);
  }

  function isHeld() {
    for (var key in state.holds) {
      if (Object.prototype.hasOwnProperty.call(state.holds, key) && state.holds[key]) {
        return true;
      }
    }
    return false;
  }

  function nextIndex(step) {
    return (state.selected + step + slides.length) % slides.length;
  }

  function slideDuration(index) {
    var value = parseInt(slides[index].getAttribute('data-duration'), 10);
    return value > 0 ? value : 3000;
  }

  function slideTitle(index) {
    var heading = slides[index].querySelector('.slide__title');
    return heading ? heading.textContent.replace(/\s+/g, ' ').trim() : '';
  }

  // --- Estado de reproducción -----------------------------------------------------
  function syncPlayState() {
    var held = isHeld();
    stage.classList.toggle('is-stopped', state.stopped);
    stage.classList.toggle('is-held', !state.stopped && held);
  }

  function startProgress() {
    timer.classList.remove('is-running');
    state.pendingAdvance = false;
    if (state.stopped) {
      return;
    }
    stage.style.setProperty('--slide-dur', slideDuration(state.selected) + 'ms');
    void timer.offsetWidth; // reinicia el temporizador
    timer.classList.add('is-running');
  }

  function setHold(name, on) {
    if (Boolean(state.holds[name]) === on) {
      return;
    }
    state.holds[name] = on;
    syncPlayState();
    if (!isHeld() && state.pendingAdvance && !state.stopped) {
      state.pendingAdvance = false;
      go(nextIndex(1), { auto: true });
    }
  }

  function stop() {
    if (state.stopped) {
      return;
    }
    state.stopped = true;
    startProgress();
    syncPlayState();
  }

  // --- Anuncios --------------------------------------------------------------------
  function announce(index) {
    if (live) {
      live.textContent = 'Destacado ' + (index + 1) + ' de ' + slides.length + ': ' + slideTitle(index);
    }
  }

  // --- Carga segura de la imagen del slide --------------------------------------------
  function ensureReady(slide) {
    var image = slide.querySelector('img[data-slide-img]');
    if (!image) {
      return Promise.resolve(true);
    }
    if (image.loading === 'lazy') {
      image.loading = 'eager';
    }
    var loaded = image.complete && image.naturalWidth > 0
      ? Promise.resolve(true)
      : new Promise(function (resolve) {
        function finish(ok) {
          image.removeEventListener('load', onLoad);
          image.removeEventListener('error', onError);
          resolve(ok);
        }
        function onLoad() { finish(true); }
        function onError() { finish(false); }
        image.addEventListener('load', onLoad);
        image.addEventListener('error', onError);
      });
    return loaded.then(function (ok) {
      if (!ok) {
        image.style.visibility = 'hidden'; // sin foto: queda el estudio y el texto, nunca un ícono roto
        return false;
      }
      if (typeof image.decode !== 'function') {
        return true;
      }
      return image.decode().then(function () { return true; }, function () { return true; });
    });
  }

  function warm(index) {
    ensureReady(slides[index]);
  }

  // --- Transición -----------------------------------------------------------------------
  function reveal(index) {
    if (state.finishPending) {
      state.finishPending(); // cierra en seco la transición anterior antes de empezar otra
    }
    var from = slides[state.shown];
    var to = slides[index];
    if (from === to) {
      return;
    }
    var timer = 0;

    function finish() {
      clearTimeout(timer);
      if (state.finishPending === finish) {
        state.finishPending = null;
      }
      from.classList.remove('is-active', 'is-leaving');
      from.setAttribute('inert', '');
      to.classList.remove('is-entering', 'is-pre');
      to.classList.add('is-active');
      state.shown = index;
      doc.dispatchEvent(new CustomEvent('tt:slide', { detail: { index: index, id: to.id } }));
    }

    to.removeAttribute('inert');
    if (reduceMotion.matches) {
      finish();
      return;
    }
    to.classList.add('is-entering', 'is-pre');
    void to.offsetWidth;
    to.classList.remove('is-pre');
    from.classList.add('is-leaving');
    state.finishPending = finish;
    timer = setTimeout(finish, 900);
  }

  function go(index, options) {
    options = options || {};
    if (index === state.selected) {
      return;
    }
    var token = ++state.token;
    state.selected = index;
    startProgress();
    if (!options.auto) {
      announce(index);
    }
    ensureReady(slides[index]).then(function () {
      if (token !== state.token) {
        return; // llegó otra petición mientras cargaba: esta ya no cuenta
      }
      reveal(index);
      warm((index + 1) % slides.length);
    });
  }

  // --- Eventos ----------------------------------------------------------------------------
  timer.addEventListener('animationend', function () {
    if (state.stopped) {
      return;
    }
    if (isHeld()) {
      state.pendingAdvance = true;
      return;
    }
    go(nextIndex(1), { auto: true });
  });

  var prev = stage.querySelector('[data-prev]');
  var next = stage.querySelector('[data-next]');
  if (prev) {
    prev.addEventListener('click', function () { go(nextIndex(-1)); });
  }
  if (next) {
    next.addEventListener('click', function () { go(nextIndex(1)); });
  }

  toArray(stage.querySelectorAll('[data-hover-hold]')).forEach(function (element) {
    element.addEventListener('pointerenter', function (event) {
      if (event.pointerType === 'mouse') {
        setHold('hover', true);
      }
    });
    element.addEventListener('pointerleave', function (event) {
      if (event.pointerType === 'mouse') {
        setHold('hover', false);
      }
    });
  });

  stage.addEventListener('focusin', function (event) {
    if (event.target.matches && event.target.matches(':focus-visible')) {
      setHold('focus', true);
    }
  });
  stage.addEventListener('focusout', function (event) {
    if (!event.relatedTarget || !stage.contains(event.relatedTarget)) {
      setHold('focus', false);
    }
  });

  doc.addEventListener('visibilitychange', function () {
    setHold('hidden', doc.hidden);
  });

  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      setHold('offscreen', !entries[0].isIntersecting);
    }, { threshold: 0.2 }).observe(stage);
  }

  doc.addEventListener('tt:intro-done', function () {
    setHold('intro', false);
  });
  // Girar el logo 3D frena el carrusel un momento; sin botón de pausa no puede quedar detenido.
  var logoRelease = 0;
  doc.addEventListener('tt:carousel-stop', function () {
    setHold('logo', true);
    window.clearTimeout(logoRelease);
    logoRelease = window.setTimeout(function () {
      setHold('logo', false);
    }, 4000);
  });

  // Táctil: el autoplay espera mientras el dedo toca; deslizar cambia de slide (no sobre el logo 3D ni las flechas).
  var swipe = null;
  stage.addEventListener('pointerdown', function (event) {
    if (event.pointerType === 'mouse') {
      return;
    }
    setHold('touch', true);
    if (event.target.closest('model-viewer, a, button')) {
      swipe = null;
      return;
    }
    swipe = { x: event.clientX, y: event.clientY };
  });
  stage.addEventListener('pointerup', function (event) {
    setHold('touch', false);
    if (!swipe) {
      return;
    }
    var dx = event.clientX - swipe.x;
    var dy = event.clientY - swipe.y;
    swipe = null;
    if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy) * 1.4) {
      go(nextIndex(dx < 0 ? 1 : -1));
    }
  });
  stage.addEventListener('pointercancel', function () {
    setHold('touch', false);
    swipe = null;
  });

  function onMotionPreference() {
    if (reduceMotion.matches) {
      stop();
    }
  }
  if (reduceMotion.addEventListener) {
    reduceMotion.addEventListener('change', onMotionPreference);
  } else if (reduceMotion.addListener) {
    reduceMotion.addListener(onMotionPreference);
  }

  // --- Arranque ----------------------------------------------------------------------------
  stage.classList.add('is-ready');
  syncPlayState();
  startProgress();
  warm(1);
})();
