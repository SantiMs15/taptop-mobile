<?php
/**
 * Características principales en desglose (6.2): acordeón nativo <details>/<summary>,
 * accesible y sin JavaScript. Datos de fábrica de la ficha técnica.
 * @var list<array{title: string, detail: string}> $specs
 * @var string|null $status 'borrador' | 'confirmado' | 'pendiente' | null
 */
?>
<?php if ($specs !== []): ?>
<div class="specs">
  <?php foreach ($specs as $spec): ?>
  <details class="spec">
    <summary class="spec__summary"><span><?= e($spec['title']) ?></span><?= icon('plus', 'icon spec__icon') ?></summary>
    <div class="spec__body"><p><?= e($spec['detail']) ?></p></div>
  </details>
  <?php endforeach; ?>
</div>
<p class="specs__source">Especificaciones de fábrica según el fabricante.</p>
<?php else: ?>
<p class="specs__empty">Estamos confirmando la ficha técnica de este producto. Si necesitas un dato antes de comprar, escríbenos por WhatsApp y te lo confirmamos.</p>
<?php endif; ?>
