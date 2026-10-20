/**
 * Selector de color y capacidad de tarjetas y detalle.
 *
 * Lee la matriz que el servidor dejó en <script type="application/json" data-variants>:
 * por cada combinación color × opción trae precios ya formateados, envío, disponibilidad,
 * texto del botón y el enlace de WhatsApp ya armado. Aquí no se calcula ningún precio:
 * solo se elige la entrada que corresponde (sección 8 y 10.7 del brief).
 */
(function () {
  'use strict';

  var doc = document;

  function toArray(list) {
    return Array.prototype.slice.call(list);
  }

  function track(event, params) {
    if (typeof window.ttTrack === 'function') {
      window.ttTrack(event, params);
    }
  }

  function trackParams(element) {
    var params = {};
    if (!element) {
      return params;
    }
    toArray(element.attributes).forEach(function (attribute) {
      if (attribute.name.indexOf('data-track-') === 0) {
        params[attribute.name.slice(11).replace(/-/g, '_')] = attribute.value;
      }
    });
    return params;
  }

  function initCard(card) {
    var source = card.querySelector('script[data-variants]');
    if (!source) {
      return;
    }
    var data;
    try {
      data = JSON.parse(source.textContent);
    } catch (error) {
      return;
    }

    var hasColors = data.colors.length > 0;
    var state = { color: data.sel.c, option: data.sel.o, touched: false };
    var colorInputs = toArray(card.querySelectorAll('[data-color-index]'));
    var optionInputs = toArray(card.querySelectorAll('[data-option-index]'));
    var el = {
      colorName: card.querySelector('[data-color-name]'),
      from: card.querySelector('[data-price-from]'),
      cash: card.querySelector('[data-price-cash]'),
      gateway: card.querySelector('[data-price-gw]'),
      ship: card.querySelector('[data-price-ship]'),
      shipText: card.querySelector('[data-price-ship-text]'),
      stock: card.querySelector('[data-price-stock]'),
      live: card.querySelector('[data-price-live]'),
      cta: card.querySelector('[data-cta]'),
      ctaLabel: card.querySelector('[data-cta-label]'),
      media: card.querySelector('[data-media]')
    };

    function key(color, option) {
      return (hasColors ? String(color) : '_') + '|' + option;
    }

    function entry(color, option) {
      return data.m[key(color, option)] || null;
    }

    /** Si la capacidad elegida no existe o está agotada en el color nuevo, pasa a una disponible. */
    function fixOption() {
      var current = entry(state.color, state.option);
      if (current && current.available) {
        return;
      }
      var fallback = null;
      for (var option = 0; option < data.options.length; option++) {
        var candidate = entry(state.color, option);
        if (candidate && candidate.available) {
          state.option = option;
          return;
        }
        if (candidate && fallback === null) {
          fallback = option;
        }
      }
      if (!current && fallback !== null) {
        state.option = fallback;
      }
    }

    function updateMedia() {
      if (!el.media || !hasColors) {
        return;
      }
      var color = data.colors[state.color];
      var placeholder = el.media.querySelector('.ph');
      var image = el.media.querySelector('img[data-media-img]');
      var subject = data.name + ' en ' + color.name;
      if (placeholder) {
        placeholder.style.setProperty('--dv', color.hex);
        placeholder.setAttribute('aria-label', 'Imagen de referencia de ' + subject);
      }
      if (!color.image) {
        if (image) {
          image.hidden = true;
        }
        if (placeholder) {
          placeholder.classList.remove('is-covered');
        }
        return;
      }
      // Foto propia: se carga y decodifica antes de mostrarla (sin parpadeo).
      var next = new Image();
      next.src = color.image;
      var ready = typeof next.decode === 'function' ? next.decode() : Promise.resolve();
      ready.then(function () {
        if (data.colors[state.color] !== color) {
          return;
        }
        if (!image) {
          image = doc.createElement('img');
          image.className = 'media__img';
          image.setAttribute('data-media-img', '');
          image.decoding = 'async';
          el.media.appendChild(image);
        }
        image.src = color.image;
        image.alt = subject;
        image.hidden = false;
        if (placeholder) {
          placeholder.classList.add('is-covered');
        }
      }, function () { /* si falla, queda la silueta */ });
    }

    function render(announce) {
      var current = entry(state.color, state.option);
      if (!current) {
        return;
      }
      if (el.cash) {
        el.cash.textContent = current.cash_f;
      }
      if (el.gateway) {
        el.gateway.textContent = current.gw_f;
      }
      if (el.from) {
        el.from.hidden = !(data.from && !state.touched);
      }
      if (el.ship && el.shipText) {
        el.shipText.textContent = current.ship;
        el.ship.classList.toggle('is-free', Boolean(current.ship_free));
      }
      if (el.stock) {
        el.stock.hidden = current.available;
      }
      if (el.cta) {
        el.cta.setAttribute('href', current.wa);
        el.cta.setAttribute('data-track-color', current.color || '');
        el.cta.setAttribute('data-track-storage', current.storage || '');
        el.cta.setAttribute('data-track-price-shown', String(current.cash));
      }
      if (el.ctaLabel) {
        el.ctaLabel.textContent = current.cta;
      }
      if (el.colorName && hasColors) {
        el.colorName.textContent = data.colors[state.color].name;
      }
      colorInputs.forEach(function (input) {
        input.checked = Number(input.value) === state.color;
      });
      optionInputs.forEach(function (input) {
        var index = Number(input.value);
        var candidate = entry(state.color, index);
        var disabled = !candidate || !candidate.available;
        input.disabled = disabled;
        input.checked = index === state.option;
        var label = input.nextElementSibling;
        var stateText = label ? label.querySelector('[data-option-state]') : null;
        if (stateText) {
          stateText.textContent = !candidate ? ', no disponible en este color' : (candidate.available ? '' : ', agotado');
        }
      });
      card.classList.toggle('is-sold-out', !current.available);
      if (announce && el.live) {
        el.live.textContent = (current.available ? '' : 'Agotado. ') + current.cash_f + ' por consignación o transferencia; ' + current.gw_f + ' con tarjeta o pasarela.';
      }
    }

    colorInputs.forEach(function (input) {
      input.addEventListener('change', function () {
        if (!input.checked) {
          return;
        }
        state.color = Number(input.value);
        state.touched = true;
        fixOption();
        render(true);
        updateMedia();
        var params = trackParams(el.cta);
        params.color = data.colors[state.color].name;
        track('select_color', params);
      });
    });

    optionInputs.forEach(function (input) {
      input.addEventListener('change', function () {
        if (!input.checked) {
          return;
        }
        state.option = Number(input.value);
        state.touched = true;
        render(true);
        var params = trackParams(el.cta);
        params.storage = data.options[state.option].label + (data.options[state.option].sub ? ' ' + data.options[state.option].sub : '');
        track('select_storage', params);
      });
    });

    // El navegador puede restaurar radios marcados al volver atrás: se respeta esa elección.
    colorInputs.forEach(function (input) {
      if (input.checked) {
        state.color = Number(input.value);
      }
    });
    optionInputs.forEach(function (input) {
      if (input.checked) {
        state.option = Number(input.value);
      }
    });
    render(false);
    updateMedia();
  }

  toArray(doc.querySelectorAll('[data-product-card]')).forEach(initCard);
})();
