<?php
    $activeNav = $activeNav ?? 'dashboard';
?>

<?php $__env->startSection('body-class', 'role-admin'); ?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('components.admin-sidebar', ['activeNav' => $activeNav], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/layouts/admin.blade.php ENDPATH**/ ?>