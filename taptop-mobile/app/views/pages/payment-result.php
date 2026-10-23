<?php
/**
 * SPIKE Wompi: resultado del pago. El estado viene de la API de Wompi, no de la URL de regreso.
 * @var string|null $error
 * @var bool $sandbox
 * @var string $status APPROVED | DECLINED | VOIDED | ERROR | PENDING
 */
$titles = [
    'APPROVED' => ['¡Pago aprobado!', 'Recibimos tu pago. Te escribiremos para coordinar la entrega.'],
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
    <?php if ($lines !== []): ?>
    <dt><strong><?= count($lines) > 1 ? 'Productos' : 'Producto' ?></strong></dt>
    <dd><?php foreach ($lines as $line): ?><?= e($line['qty'] . ' × ' . $line['label'] . ' — ' . $line['line_gw_f']) ?><br><?php endforeach; ?></dd>
    <?php endif; ?>
    <dt><strong>Valor</strong></dt><dd><?= e($amount_f) ?></dd>
    <?php if ($method !== ''): ?>
    <dt><strong>Medio de pago</strong></dt><dd><?= e($method) ?></dd>
    <?php endif; ?>
    <dt><strong>Referencia</strong></dt><dd><?= e($reference) ?></dd>
    <dt><strong>Transacción Wompi</strong></dt><dd><?= e($transaction_id) ?></dd>
    <?php if ($sandbox): ?>
    <dt><strong>Confirmación por webhook</strong></dt><dd><?= $webhook !== null ? e($webhook['status'] . ' (recibida ' . $webhook['received_at'] . ')') : 'aún no llega' ?></dd>
    <?php endif; ?>
  </dl>
  <?php if (!$amount_matches): ?>
  <p class="page-lede"><mark class="todo">El valor no coincide con el del pedido o la referencia no es de este sitio.</mark></p>
  <?php endif; ?>
  <?php endif; ?>

  <div class="not-found__actions">
    <?php if (($wa_link ?? null) !== null): ?>
    <a class="btn btn--wa" href="<?= e($wa_link) ?>" target="_blank" rel="noopener noreferrer"><?= icon('whatsapp', 'icon') ?><span>Pagar por transferencia (WhatsApp)</span></a>
    <?php endif; ?>
    <?php if (($back_url ?? null) !== null): ?>
    <a class="btn btn--primary" href="<?= e($back_url) ?>"><?= ($source ?? '') === 'carrito' ? 'Volver al carrito' : 'Volver al producto' ?></a>
    <?php endif; ?>
    <a class="btn btn--outline" href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer"><?= icon('whatsapp', 'icon') ?><span>Escribir por WhatsApp</span></a>
  </div>
</section>
