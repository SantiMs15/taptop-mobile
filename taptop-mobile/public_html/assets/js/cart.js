/**
 * Carrito: "Agregar al carrito" sin recargar y contador de la cabecera.
 *
 * Intercepta el envío de [data-add-to-cart] y lo manda con fetch (Accept: application/json).
 * Si la respuesta no es 2xx con JSON {ok:true}, deja que el formulario se envíe normal
 * (lleva a /carrito). Sin JS el formulario funciona igual.
 */
(function () {
  'use strict';

  var doc = document;
  var toast = null;
  var timer = null;

  function counters() {
    return Array.prototype.slice.call(doc.querySelectorAll('[data-cart-count]'));
  }

  var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Pulso corto del contador: une el aviso de abajo con el carrito de arriba. WAAPI para
  // poder repetirlo sin quitar y poner clases.
  function pulse(node) {
    if (calm || typeof node.animate !== 'function') {
      return;
    }
    node.animate(
      [{ transform: 'scale(1)' }, { transform: 'scale(1.2)' }, { transform: 'scale(1)' }],
      { duration: 300, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' }
    );
  }

  function setCount(count) {
    var n = Number(count) || 0;
    counters().forEach(function (node) {
      var changed = node.textContent !== String(n);
      node.textContent = String(n);
      // En el header, el contador solo aparece con productos (carrito vacío = solo la silueta).
      if (node.classList.contains('cart-link__count')) {
        node.hidden = n === 0;
      }
      if (changed) {
        pulse(node);
      }
    });
    Array.prototype.forEach.call(doc.querySelectorAll('[data-cart-link]'), function (link) {
      link.setAttribute('aria-label', 'Carrito, ' + n + (n === 1 ? ' producto' : ' productos'));
    });
  }

  function showToast() {
    if (!toast) {
      toast = doc.createElement('div');
      toast.className = 'cart-toast';
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      doc.body.appendChild(toast);
      // Fija el estado oculto antes de mostrarlo para que la primera vez también entre.
      toast.getBoundingClientRect();
    }
    toast.textContent = '';
    var text = doc.createElement('span');
    text.textContent = 'Agregado ✓';
    var link = doc.createElement('a');
    link.href = '/carrito';
    link.textContent = 'Ver carrito';
    toast.appendChild(text);
    toast.appendChild(link);
    toast.classList.add('is-visible');
    window.clearTimeout(timer);
    timer = window.setTimeout(function () {
      toast.classList.remove('is-visible');
    }, 4000);
  }

  doc.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form || !form.matches || !form.matches('[data-add-to-cart]') || event.defaultPrevented) {
      return;
    }
    if (typeof window.fetch !== 'function' || typeof window.FormData !== 'function') {
      return;
    }
    event.preventDefault();
    var button = form.querySelector('button[type="submit"]');
    if (button) {
      button.disabled = true;
    }

    function fallback() {
      // Vuelve al envío normal del formulario (redirige a /carrito).
      if (button) {
        button.disabled = false;
      }
      form.submit();
    }

    window.fetch(form.action, {
      method: 'POST',
      body: new URLSearchParams(new FormData(form)),
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    }).then(function (response) {
      var type = response.headers.get('Content-Type') || '';
      if (type.indexOf('json') === -1) {
        throw new Error('no-json');
      }
      return response.json().then(function (body) {
        return { status: response.status, body: body };
      });
    }).then(function (result) {
      if (button) {
        button.disabled = false;
      }
      if (result.body && result.body.ok === true) {
        setCount(result.body.count);
        showToast();
        return;
      }
      // JSON de error (agotado, carrito lleno): se muestra el aviso de la página del carrito.
      form.submit();
    }).catch(fallback);
  });
})();
