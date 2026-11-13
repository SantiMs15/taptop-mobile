<?php
/**
 * Datos de envío antes de pasar a Wompi. Los precios salen del catálogo en el servidor.
 * @var array $items resultado de checkout_items()
 * @var array $fields shipping_form_fields()
 * @var array<string, string> $values lo ya escrito
 * @var array<string, string> $errors mensaje por campo
 * @var bool $sandbox
 */
$priced = $items['priced'];
$first = $priced['lines'][0];
$isCart = $items['source'] === 'carrito';
?>
<section class="container checkout">
  <h1 class="page-title">Datos de envío</h1>

  <?php if ($errors !== []): ?>
  <p class="cart__notice" role="alert">Revisa los datos marcados para continuar.</p>
  <?php endif; ?>

  <?php if ($priced['has_unavailable']): ?>
  <p class="cart__notice" role="status">Algunos productos se agotaron y no se incluyen en este pedido.</p>
  <?php endif; ?>

  <div class="checkout__summary">
    <h2 class="checkout__h">Tu pedido</h2>
    <ul class="checkout__lines">
      <?php foreach ($priced['lines'] as $line): ?>
      <li>
        <span class="checkout__item"><?= (int) $line['qty'] ?> × <?= e($line['name']) ?><small><?= e($line['label']) ?></small></span>
        <strong><?= e(format_price($line['line_gw'])) ?></strong>
      </li>
      <?php endforeach; ?>
    </ul>
    <dl class="cart__totals">
      <div><dt>Envío</dt><dd><?= $priced['shipping']['cost_gateway'] > 0 ? e(format_price($priced['shipping']['cost_gateway'])) : 'Gratis' ?></dd></div>
      <div><dt>Total pagando en línea</dt><dd class="cart__total"><?= e(format_price($priced['total_gateway'])) ?></dd></div>
    </dl>
    <p class="cart__shipping">Entrega en <?= e(delivery_label()) ?>.</p>
    <p class="cart__back"><a href="<?= e(url($items['back'])) ?>">‹ <?= $isCart ? 'Volver al carrito' : 'Volver al producto' ?></a></p>
  </div>

  <form class="checkout__form" method="post" action="<?= e(url('/pago/envio')) ?>" novalidate>
    <input type="hidden" name="origen" value="<?= e($items['source']) ?>">
    <?php if (!$isCart): ?>
    <input type="hidden" name="producto" value="<?= e($first['slug']) ?>">
    <input type="hidden" name="sku" value="<?= e($first['sku']) ?>">
    <input type="hidden" name="color" value="<?= e((string) $first['color']) ?>">
    <?php endif; ?>
    <input type="hidden" name="total_visto" value="<?= (int) $priced['total_gateway'] ?>">

    <?php foreach ($fields as $name => $field): ?>
    <?php if ($name === 'acepta') { continue; } ?>
    <?php
      $error = $errors[$name] ?? null;
      $value = (string) ($values[$name] ?? '');
      $describedBy = $error !== null ? ' aria-describedby="err-' . $name . '"' : '';
    ?>
    <div class="field<?= $error !== null ? ' has-error' : '' ?>">
      <label class="field__label" for="f-<?= e($name) ?>"><?= e($field['label']) ?></label>
      <?php if (isset($field['options'])): ?>
      <select class="field__input field__input--select" id="f-<?= e($name) ?>" name="<?= e($name) ?>" autocomplete="<?= e($field['autocomplete']) ?>"<?= $field['required'] ? ' required' : '' ?><?= $describedBy ?><?= $error !== null ? ' aria-invalid="true"' : '' ?>>
        <option value=""<?= in_array($value, $field['options'], true) ? '' : ' selected' ?> disabled>Selecciona tu departamento</option>
        <?php foreach ($field['options'] as $option): ?>
        <option<?= $option === $value ? ' selected' : '' ?>><?= e($option) ?></option>
        <?php endforeach; ?>
      </select>
      <?php elseif ($name === 'notas'): ?>
      <textarea class="field__input" id="f-<?= e($name) ?>" name="<?= e($name) ?>" rows="3" maxlength="<?= (int) $field['max'] ?>"<?= $describedBy ?><?= $error !== null ? ' aria-invalid="true"' : '' ?>><?= e($value) ?></textarea>
      <?php else: ?>
      <input class="field__input" id="f-<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($field['type'] ?? 'text') ?>" value="<?= e($value) ?>" maxlength="<?= (int) $field['max'] ?>" autocomplete="<?= e($field['autocomplete']) ?>"<?= isset($field['inputmode']) ? ' inputmode="' . e($field['inputmode']) . '"' : '' ?><?= $field['required'] ? ' required' : '' ?><?= $describedBy ?><?= $error !== null ? ' aria-invalid="true"' : '' ?>>
      <?php endif; ?>
      <?php if ($error !== null): ?><p class="field__error" id="err-<?= e($name) ?>"><?= e($error) ?></p><?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php $acceptError = $errors['acepta'] ?? null; ?>
    <div class="field field--check<?= $acceptError !== null ? ' has-error' : '' ?>">
      <label class="field__check">
        <input type="checkbox" name="acepta" value="1" required<?= ($values['acepta'] ?? '') !== '' ? ' checked' : '' ?><?= $acceptError !== null ? ' aria-invalid="true" aria-describedby="err-acepta"' : '' ?>>
        <span>Acepto la <a href="<?= e(url('/privacidad')) ?>" target="_blank" rel="noopener">política de tratamiento de datos</a></span>
      </label>
      <?php if ($acceptError !== null): ?><p class="field__error" id="err-acepta"><?= e($acceptError) ?></p><?php endif; ?>
    </div>

    <button class="btn btn--metal" type="submit">Continuar al pago · <?= e(format_price($priced['total_gateway'])) ?></button>
    <p class="checkout__note">Pago con tarjeta, PSE o Nequi en Wompi.</p>
    <?php if ($sandbox): ?>
    <p class="badge">Modo de pruebas (sandbox): no se cobra dinero real</p>
    <?php endif; ?>
  </form>
</section>
