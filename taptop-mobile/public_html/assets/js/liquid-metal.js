/**
 * Botón metal líquido: borde animado con shader WebGL para los CTA grandes (.btn--metal).
 * Diseño basado en Liquid Metal Button de ObsidianUI (Atharvsinh, MIT), sin React.
 * Shader: @paper-design/shaders (Apache-2.0), en assets/vendor/paper-shaders.
 *
 * Sin WebGL2, sin JS o con ahorro de datos queda el borde en CSS de main.css.
 * El carrusel usa un solo contexto WebGL: el borde se mueve al botón del slide activo.
 */
(function () {
  'use strict';

  var buttons = document.querySelectorAll('.btn--metal');
  var vendor = document.currentScript && document.currentScript.getAttribute('data-vendor');
  var saveData = navigator.connection && navigator.connection.saveData;
  if (buttons.length === 0 || !vendor || saveData) {
    return;
  }

  var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var SPEED = calm ? 0 : 0.4;
  var SPEED_HOVER = calm ? 0 : 1.1;

  // El shader trae blanco y gris fijos: se cambian por los tonos de la marca.
  var LIGHT = 'vec3 color1 = vec3(.98, 0.98, 1.);';
  var DARK = 'vec3 color2 = vec3(.1, .1, .1 + .1 * smoothstep(.7, 1.3, diagTLtoBR));';
  var BRAND_LIGHT = 'vec3 color1 = vec3(.92, .98, 1.);';
  var BRAND_DARK = 'vec3 color2 = vec3(0., .36, .66 + .1 * smoothstep(.7, 1.3, diagTLtoBR));';

  function start(lib, pixel) {
    var shader = lib.liquidMetalFragmentShader.replace(LIGHT, BRAND_LIGHT).replace(DARK, BRAND_DARK);
    var uniforms = {
      u_colorBack: lib.getShaderColorFromString('#0D2655'),
      u_colorTint: lib.getShaderColorFromString('#FFFFFF'),
      u_image: pixel,
      u_repetition: 4,
      u_softness: 0,
      u_shiftRed: 0.12,
      u_shiftBlue: -0.12,
      u_distortion: 0.15,
      u_contour: 0.4,
      u_angle: 45,
      u_shape: 0,
      u_isImage: false,
      u_fit: lib.ShaderFitOptions.cover,
      u_scale: 1,
      u_rotation: 0,
      u_offsetX: 0,
      u_offsetY: 0,
      u_originX: 0.5,
      u_originY: 0.5,
      u_worldWidth: 0,
      u_worldHeight: 0,
      u_imageAspectRatio: 1
    };

    function mount(button) {
      var layer = document.createElement('span');
      layer.className = 'metal-layer';
      layer.setAttribute('aria-hidden', 'true');
      button.prepend(layer);
      try {
        return { layer: layer, shader: new lib.ShaderMount(layer, shader, uniforms, undefined, SPEED, 0) };
      } catch (error) {
        layer.remove();
        return null;
      }
    }

    function speedOnHover(button, metal) {
      button.addEventListener('pointerenter', function () {
        metal.shader.setSpeed(SPEED_HOVER);
      });
      button.addEventListener('pointerleave', function () {
        metal.shader.setSpeed(SPEED);
      });
    }

    var heroButtons = [];
    buttons.forEach(function (button) {
      if (button.closest('.slide')) {
        heroButtons.push(button);
        return;
      }
      var metal = mount(button);
      if (metal) {
        speedOnHover(button, metal);
      }
    });

    if (heroButtons.length === 0) {
      return;
    }

    function currentHeroButton() {
      return document.querySelector('.slide.is-entering .btn--metal')
        || document.querySelector('.slide.is-active .btn--metal')
        || heroButtons[0];
    }

    var hero = mount(currentHeroButton());
    if (!hero) {
      return;
    }
    heroButtons.forEach(function (button) {
      speedOnHover(button, hero);
    });
    var observer = new MutationObserver(function () {
      var target = currentHeroButton();
      if (hero.layer.parentElement !== target) {
        target.prepend(hero.layer);
      }
    });
    document.querySelectorAll('.slide').forEach(function (slide) {
      observer.observe(slide, { attributes: true, attributeFilter: ['class'] });
    });
  }

  function load() {
    var script = document.createElement('script');
    script.src = vendor;
    script.async = true;
    script.onload = function () {
      var lib = window.TTPaperShaders;
      var pixel = new Image();
      pixel.onload = function () {
        start(lib, pixel);
      };
      pixel.src = lib.emptyPixel;
    };
    document.head.appendChild(script);
  }

  // Compilar el shader bloquea el hilo principal (segundos en celulares sin GPU fuerte): se espera
  // a la primera interacción y a un momento libre, para no frenar el primer pintado (LCP/TBT).
  // Hasta entonces se ve el borde en CSS de main.css.
  var EVENTS = ['pointermove', 'pointerdown', 'touchstart', 'scroll', 'wheel', 'keydown'];
  var started = false;
  function wake() {
    if (started) {
      return;
    }
    started = true;
    EVENTS.forEach(function (name) {
      window.removeEventListener(name, wake, { passive: true });
    });
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(load, { timeout: 1500 });
    } else {
      window.setTimeout(load, 200);
    }
  }
  EVENTS.forEach(function (name) {
    window.addEventListener(name, wake, { passive: true });
  });
})();
