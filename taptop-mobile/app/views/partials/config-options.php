<?php
/**
 * Configurador (Apple Watch): un selector por opción con todos sus valores. Solo se venden las
 * combinaciones que existen como variante. Un valor que no está en ninguna se ve en gris, pero se
 * puede elegir (la ficha pasa a "No disponible" y WhatsApp); uno que sí está lleva a la
 * configuración más parecida (color-swatches.js ajusta las demás opciones, leyendas y precio).
 * @var array $card vista de ProductPresenter (con client.cfg)
 * @var string $uid prefijo único para ids y names
 */
$cfg = $card['client']['cfg'] ?? null;
if ($cfg === null) {
    return;
}
$selected = $cfg['opts'][$card['selected']['option']];
/** ¿Alguna configuración tiene $value en $axis? */
$exists = static function (int $axis, int $value) use ($cfg): bool {
    foreach ($cfg['opts'] as $opt) {
        if ($opt[$axis] === $value) {
            return true;
        }
    }
    return false;
};
?>
<div class="config" data-cfg>
  <?php foreach ($cfg['axes'] as $a => $axis):
      // Ejes de color (con hex): círculos como el color del iPhone; el resto, botones.
      $round = ($axis['hex'] ?? null) !== null;
  ?>
  <fieldset class="<?= $round ? 'swatches swatches--lg' : 'options options--lg' ?>">
    <legend class="field-legend"><?= e($axis['name']) ?>: <span class="field-legend__value" data-cfg-value="<?= (int) $a ?>"><?= e($axis['values'][$selected[$a]]) ?></span></legend>
    <div class="<?= $round ? 'swatches__list' : 'options__list' ?>">
      <?php foreach ($axis['values'] as $v => $value):
          $id = $uid . '-cfg-' . $a . '-' . $v;
          $unavailable = !$exists($a, $v);
      ?>
      <input class="<?= $round ? 'swatch__input' : 'option__input' ?> visually-hidden" type="radio" id="<?= e($id) ?>" name="<?= e($uid) ?>-cfg-<?= (int) $a ?>"
             value="<?= (int) $v ?>" data-cfg-axis="<?= (int) $a ?>"<?= $v === $selected[$a] ? ' checked' : '' ?>>
      <?php if ($round): ?>
      <label class="swatch<?= $unavailable ? ' is-unavailable' : '' ?>" for="<?= e($id) ?>" style="--swatch: <?= e($axis['hex'][$value]) ?>" title="<?= e($value . ($unavailable ? ' (no disponible)' : '')) ?>" data-cfg-title="<?= e($value) ?>">
        <span class="visually-hidden"><?= e($value) ?></span>
        <span class="visually-hidden" data-cfg-state><?= $unavailable ? ', no disponible' : '' ?></span>
      </label>
      <?php else: ?>
      <label class="option<?= $unavailable ? ' is-unavailable' : '' ?>" for="<?= e($id) ?>">
        <span class="option__main"><?= e($value) ?></span>
        <span class="visually-hidden" data-cfg-state><?= $unavailable ? ', no disponible' : '' ?></span>
      </label>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <?php endforeach; ?>
</div>
