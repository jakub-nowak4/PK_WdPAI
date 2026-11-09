<?php
/**
 * Pole formularza z etykieta, podpowiedzia i komunikatem bledu.
 * @var callable $t
 * @var callable $e
 * @var array<string,string> $errors blad per nazwa pola
 * @var array<string,string> $old poprzednie wartosci (nigdy hasla)
 * @var array{name:string,label:string,type?:string,autocomplete?:string,required?:bool,hint?:string,
 *   minlength?:int,maxlength?:int,inputmode?:string,placeholder?:string,pattern?:string,value?:string,
 *   min?:int|string,max?:int|string,step?:int|string} $field
 */
$name = (string) $field['name'];
$id = 'field-' . $name;
$type = (string) ($field['type'] ?? 'text');
$required = (bool) ($field['required'] ?? false);
$error = (string) ($errors[$name] ?? '');
$hint = (string) ($field['hint'] ?? '');
$value = $type === 'password' ? '' : (string) ($old[$name] ?? $field['value'] ?? '');
$describedBy = trim(($hint !== '' ? $id . '-hint ' : '') . ($error !== '' ? $id . '-error' : ''));
?>
<div class="form-field<?= $error !== '' ? ' form-field--error' : '' ?>">
    <label class="form-field__label" for="<?= $e($id) ?>">
        <?= $e($field['label']) ?>
        <?php if ($required): ?><span aria-hidden="true">*</span><?php endif; ?>
    </label>
    <input class="form-field__control" id="<?= $e($id) ?>" name="<?= $e($name) ?>" type="<?= $e($type) ?>"
           value="<?= $e($value) ?>"
           <?php if (isset($field['autocomplete'])): ?>autocomplete="<?= $e($field['autocomplete']) ?>"<?php endif; ?>
           <?php if (isset($field['inputmode'])): ?>inputmode="<?= $e($field['inputmode']) ?>"<?php endif; ?>
           <?php if (isset($field['placeholder'])): ?>placeholder="<?= $e($field['placeholder']) ?>"<?php endif; ?>
           <?php if (isset($field['pattern'])): ?>pattern="<?= $e($field['pattern']) ?>"<?php endif; ?>
           <?php if (isset($field['min'])): ?>min="<?= $e((string) $field['min']) ?>"<?php endif; ?>
           <?php if (isset($field['max'])): ?>max="<?= $e((string) $field['max']) ?>"<?php endif; ?>
           <?php if (isset($field['step'])): ?>step="<?= $e((string) $field['step']) ?>"<?php endif; ?>
           <?php if (isset($field['minlength'])): ?>minlength="<?= (int) $field['minlength'] ?>"<?php endif; ?>
           <?php if (isset($field['maxlength'])): ?>maxlength="<?= (int) $field['maxlength'] ?>"<?php endif; ?>
           <?= $required ? 'required' : '' ?>
           <?php if ($error !== ''): ?>aria-invalid="true"<?php endif; ?>
           <?php if ($describedBy !== ''): ?>aria-describedby="<?= $e($describedBy) ?>"<?php endif; ?>>
    <?php if ($hint !== ''): ?>
        <p class="form-field__hint" id="<?= $e($id) ?>-hint"><?= $e($hint) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="form-field__error" id="<?= $e($id) ?>-error" role="alert"><?= $e($error) ?></p>
    <?php endif; ?>
</div>
