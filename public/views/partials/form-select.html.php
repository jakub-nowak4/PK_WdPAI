<?php
/**
 * Pole wyboru (select) z etykieta, podpowiedzia i komunikatem bledu; wzorowane na form-field.
 * @var callable $e
 * @var array<string,string> $errors blad per nazwa pola
 * @var array<string,string> $old poprzednio wybrana wartosc
 * @var array{name:string,label:string,options:array<string,string>,required?:bool,hint?:string,
 *   placeholder?:string,value?:string} $field
 */
$name = (string) $field['name'];
$id = 'field-' . $name;
$required = (bool) ($field['required'] ?? false);
$error = (string) ($errors[$name] ?? '');
$hint = (string) ($field['hint'] ?? '');
$selected = (string) ($old[$name] ?? $field['value'] ?? '');
$describedBy = trim(($hint !== '' ? $id . '-hint ' : '') . ($error !== '' ? $id . '-error' : ''));
?>
<div class="form-field<?= $error !== '' ? ' form-field--error' : '' ?>">
    <label class="form-field__label" for="<?= $e($id) ?>">
        <?= $e($field['label']) ?>
        <?php if ($required): ?><span aria-hidden="true">*</span><?php endif; ?>
    </label>
    <select class="form-field__control" id="<?= $e($id) ?>" name="<?= $e($name) ?>"
            <?= $required ? 'required' : '' ?>
            <?php if ($error !== ''): ?>aria-invalid="true"<?php endif; ?>
            <?php if ($describedBy !== ''): ?>aria-describedby="<?= $e($describedBy) ?>"<?php endif; ?>>
        <?php if (isset($field['placeholder'])): ?>
            <option value=""><?= $e($field['placeholder']) ?></option>
        <?php endif; ?>
        <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
            <option value="<?= $e((string) $optionValue) ?>"<?= (string) $optionValue === $selected ? ' selected' : '' ?>><?= $e($optionLabel) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($hint !== ''): ?>
        <p class="form-field__hint" id="<?= $e($id) ?>-hint"><?= $e($hint) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="form-field__error" id="<?= $e($id) ?>-error" role="alert"><?= $e($error) ?></p>
    <?php endif; ?>
</div>
