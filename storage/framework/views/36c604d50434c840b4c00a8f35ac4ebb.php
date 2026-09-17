<?php $__env->startSection('title', 'Create Class'); ?>

<?php ($activeNav = 'classes'); ?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title"><i class="fa-solid fa-chalkboard-user"></i> Create Class</h2>
        <div class="page-actions">
            <a href="<?php echo e(route('admin.classes.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to classes</a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('admin.classes._form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\classes\create.blade.php ENDPATH**/ ?>