<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label' => '', 'value' => '', 'hint' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['label' => '', 'value' => '', 'hint' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div <?php echo e($attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200 p-5 shadow-sm'])); ?>>
    <p class="text-sm font-medium text-slate-500"><?php echo e($label); ?></p>
    <p class="mt-2 text-2xl font-semibold text-slate-900"><?php echo e($value); ?></p>
    <?php if($hint): ?>
        <p class="mt-1 text-xs text-slate-400"><?php echo e($hint); ?></p>
    <?php endif; ?>
</div>
<?php /**PATH C:\xampp\htdocs\lms\resources\views\components\card.blade.php ENDPATH**/ ?>