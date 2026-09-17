<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['items' => []]));

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

foreach (array_filter((['items' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="flex items-center gap-2 px-5 h-16 border-b border-slate-800">
    <span class="text-lg font-semibold tracking-tight">LMS</span>
</div>

<nav class="flex-1 overflow-y-auto py-4" aria-label="Main navigation">
    <ul class="space-y-1 px-3">
        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li>
                <?php
                    $active = $item['route'] && request()->routeIs($item['route']);
                ?>
                <a href="<?php echo e($item['route'] ? route($item['route']) : '#'); ?>"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          <?php echo e($active ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'); ?>"
                   <?php echo e($active ? 'aria-current="page"' : ''); ?>>
                    <span class="w-4 h-4 rounded-sm bg-current opacity-70 shrink-0"></span>
                    <span><?php echo e($item['label']); ?></span>
                </a>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</nav>
<?php /**PATH C:\xampp\htdocs\lms\resources\views\components\sidebar.blade.php ENDPATH**/ ?>