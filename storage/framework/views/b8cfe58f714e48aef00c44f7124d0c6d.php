<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('components.admin-sidebar', ['activeNav' => $activeNav ?? 'dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('notifications'); ?>
    <?php if (isset($component)) { $__componentOriginal305bf734960e43640e17253da7c97ffe = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal305bf734960e43640e17253da7c97ffe = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sms-notifications','data' => ['notifications' => []]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sms-notifications'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['notifications' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal305bf734960e43640e17253da7c97ffe)): ?>
<?php $attributes = $__attributesOriginal305bf734960e43640e17253da7c97ffe; ?>
<?php unset($__attributesOriginal305bf734960e43640e17253da7c97ffe); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal305bf734960e43640e17253da7c97ffe)): ?>
<?php $component = $__componentOriginal305bf734960e43640e17253da7c97ffe; ?>
<?php unset($__componentOriginal305bf734960e43640e17253da7c97ffe); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/layouts/admin-sms.blade.php ENDPATH**/ ?>