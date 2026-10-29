<?php
/**
 * Selector de color circular (color exacto del modelo) y de capacidad (6.1 y 6.2).
 * Radios nativos: funcionan con teclado y lectores de pantalla. Lo agotado se ve
 * tachado y no se puede elegir (modo 'mark' de config.php).
 * @var array $card
 * @var string $uid prefijo único para ids y names
 * @var string $size 'sm' o 'lg'
 */
$selected = $card['selected'];
$selectedColor = $selected['color'] !== null ? $card['colors'][$selected['color']] : null;
?>
<?php if ($card['colors'] !== []): ?>
<fieldset class="swatches swatches--<?= e($size) ?>" data-swatches>
  <legend class="field-legend">Color: <span class="field-legend__value" data-color-name><?= e($selectedColor['name'] ?? '') ?></span></legend>
  <div class="swatches__list">
    <?php foreach ($card['colors'] as $color):
        $id = $uid . '-color-' . $color['index'];
        $soldOut = !$color['available'];
    ?>
    <input class="swatch__input visually-hidden" type="radio" id="<?= e($id) ?>" name="<?= e($uid) ?>-color"
           value="<?= (int) $color['index'] ?>" data-color-index="<?= (int) $color['index'] ?>"
           <?= $color['index'] === $selected['color'] ? 'checked' : '' ?> <?= $soldOut ? 'disabled' : '' ?>>
    <label class="swatch<?= $soldOut ? ' is-sold-out' : '' ?>" for="<?= e($id) ?>" style="--swatch: <?= e($color['hex']) ?>" title="<?= e($color['name'] . ($soldOut ? ' (agotado)' : '')) ?>">
      <span class="visually-hidden"><?= e($color['name']) ?><?= $soldOut ? ', agotado' : '' ?></span>
    </label>
    <?php endforeach; ?>
  </div>
</fieldset>
<?php endif; ?>
<?php if (count($card['options']) > 1 && !isset($card['client']['cfg'])): // El configurador tiene su propio parcial. ?>
<fieldset class="options options--<?= e($size) ?>" data-options>
  <legend class="field-legend">Capacidad</legend>
  <div class="options__list">
    <?php foreach ($card['options'] as $option):
        $id = $uid . '-option-' . $option['index'];
        $disabled = !$option['exists'] || !$option['available'];
        $stateText = !$option['exists'] ? ', no disponible en este color' : (!$option['available'] ? ', agotado' : '');
    ?>
    <input class="option__input visually-hidden" type="radio" id="<?= e($id) ?>" name="<?= e($uid) ?>-option"
           value="<?= (int) $option['index'] ?>" data-option-index="<?= (int) $option['index'] ?>"
           <?= $option['index'] === $selected['option'] ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
    <label class="option" for="<?= e($id) ?>">
      <span class="option__main"><?= e($option['label']) ?></span>
      <?php if ($option['sub'] !== null): ?><span class="option__sub"><?= e($option['sub']) ?></span><?php endif; ?>
      <span class="visually-hidden" data-option-state><?= e($stateText) ?></span>
    </label>
    <?php endforeach; ?>
  </div>
</fieldset>
<?php endif; ?>
