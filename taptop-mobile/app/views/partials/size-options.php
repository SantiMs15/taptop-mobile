<?php
/**
 * Selector de tamaño (p. ej. 13" y 15") en el detalle. Informativo: no cambia precio ni SKU,
 * así que color-swatches.js no lo lee. Lo no disponible se ve tachado y no se puede elegir.
 * @var array $sizes list<array{label: string, available: bool}>
 * @var string $uid prefijo único para ids y names
 */
if (count($sizes) < 2) {
    return;
}
$checked = array_search(true, array_column($sizes, 'available'), true);
?>
<fieldset class="options options--lg">
  <legend class="field-legend">Tamaño</legend>
  <div class="options__list">
    <?php foreach ($sizes as $i => $size):
        $id = $uid . '-size-' . $i;
    ?>
    <input class="option__input visually-hidden" type="radio" id="<?= e($id) ?>" name="<?= e($uid) ?>-size"
           value="<?= (int) $i ?>" <?= $i === $checked ? 'checked' : '' ?> <?= $size['available'] ? '' : 'disabled' ?>>
    <label class="option" for="<?= e($id) ?>">
      <span class="option__main"><?= e($size['label']) ?></span>
      <?php if (!$size['available']): ?><span class="option__sub">No disponible</span><?php endif; ?>
      <span class="visually-hidden"><?= $size['available'] ? '' : ', no disponible por ahora' ?></span>
    </label>
    <?php endforeach; ?>
  </div>
</fieldset>
