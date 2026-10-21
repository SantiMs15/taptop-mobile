<?php
/**
 * SPIKE Wompi: resultado del pago. El estado viene de la API de Wompi, no de la URL de regreso.
 * @var string|null $error
 * @var bool $sandbox
 * @var string $status APPROVED | DECLINED | VOIDED | ERROR | PENDING
 */
$titles = [
    'APPROVED' => ['¡Pago aprobado!', 'Recibimos tu pago. Te escribiremos por WhatsApp para coordinar el envío.'],
    'PENDING' => ['Pago en proceso', 'Wompi todavía está confirmando el pago. Recarga esta página en unos minutos.'],
    'DECLINED' => ['Pago rechazado', 'El pago no fue aprobado. Puedes intentar de nuevo con otro medio o escribirnos por WhatsApp.'],
    'VOIDED' => ['Pago anulado', 'Este pago fue anulado. Si tienes dudas, escríbenos por WhatsApp.'],
    'ERROR' => ['No se pudo completar el pago', 'Hubo un error con el medio de pago. Intenta de nuevo o escríbenos por WhatsApp.'],
];
[$title, $lede] = $error !== null
    ? ['No pudimos confirmar el pago', $error]
    : ($titles[$status] ?? ['Estado del pago: ' . $status, 'Escríbenos por WhatsApp si necesitas ayuda.']);
?>
<section class="container not-found">
  <?php if ($sandbox): ?>
  <p class="badge">Modo de pruebas (sandbox): no se cobró dinero real</p>
  <?php endif; ?>
  <h1 class="page-title"><?= e($title) ?></h1>
  <p class="page-lede"><?= e($lede) ?></p>

  <?php if ($error === null): ?>
  <dl class="page-lede">
    <?php if ($product_name !== null): ?>
    <dt><strong>Producto</strong></dt><dd><?= e($product_name) ?></dd>
    <?php endif; ?>
    <dt><strong>Valor</strong></dt><dd><?= e($amount_f) ?></dd>
    <?php if ($method !== ''): ?>
    <dt><strong>Medio de pago</strong></dt><dd><?= e($method) ?></dd>
    <?php endif; ?>
    <dt><strong>Referencia</strong></dt><dd><?= e($reference) ?></dd>
    <dt><strong>Transacción Wompi</strong></dt><dd><?= e($transaction_id) ?></dd>
    <dt><strong>Confirmación por webhook</strong></dt><dd><?= $webhook !== null ? e($webhook['status'] . ' (recibida ' . $webhook['received_at'] . ')') : 'aún no llega' ?></dd>
  </dl>
  <?php if (!$amount_matches): ?>
  <p class="page-lede"><mark class="todo">El valor no coincide con el precio con pasarela del catálogo o la referencia no es de este sitio.</mark></p>
  <?php endif; ?>
  <?php endif; ?>

  <div class="not-found__actions">
    <?php if (($product_url ?? null) !== null): ?>
    <a class="btn btn--primary" href="<?= e($product_url) ?>">Volver al producto</a>
    <?php endif; ?>
    <a class="btn btn--outline" href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer"><?= icon('whatsapp', 'icon') ?><span>Escribir por WhatsApp</span></a>
  </div>
</section>
