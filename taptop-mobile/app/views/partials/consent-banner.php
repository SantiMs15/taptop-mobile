<?php
/**
 * Aviso de cookies. Solo se incluye si hay un ID de GA4 o de Meta Pixel en config.php;
 * analytics.js lo muestra mientras el visitante no haya decidido.
 */
?>
<section class="consent glass-panel" data-consent hidden aria-label="Aviso de cookies">
  <p class="consent__text">Usamos cookies de analítica de Google y Meta para saber qué productos interesan más. Solo se activan si las aceptas. <a href="/privacidad#cookies">Más información</a></p>
  <div class="consent__actions">
    <button class="btn btn--outline btn--sm" type="button" data-consent-choice="denied">Rechazar</button>
    <button class="btn btn--primary btn--sm" type="button" data-consent-choice="granted">Aceptar</button>
  </div>
</section>
