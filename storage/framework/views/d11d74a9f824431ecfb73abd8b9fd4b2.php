<?php $__env->startSection('title', 'Academic Periods'); ?>
<?php
    $activeNav = 'academic_periods';
    $pageTitle = 'Academic Periods';
    $pageIcon = '<i class="fa-solid fa-calendar"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-calendar"></i>
            Academic Periods
        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card">
        <div class="crud-header">
            <h3>Academic Periods</h3>
            <a href="<?php echo e(route('admin.academic_periods.create')); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Add Period
            </a>
        </div>
        
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $periods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $period): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($period->name); ?></td>
                        <td><?php echo e($period->start_date->format('M d, Y')); ?></td>
                        <td><?php echo e($period->end_date->format('M d, Y')); ?></td>
                        <td>
                            <span class="badge <?php echo e($period->is_current ? 'badge-active' : 'badge-inactive'); ?>">
                                <?php echo e($period->is_current ? 'Current' : 'Inactive'); ?>

                            </span>
                        </td>
                        <td class="actions-cell action-buttons">
                            <?php if(! $period->is_current): ?>
                                <form method="POST" action="<?php echo e(route('admin.academic_periods.set-current', $period)); ?>" class="inline-form">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn-icon btn-success" title="Set as current" onclick="return confirm('Set this as the current academic period?')">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="<?php echo e(route('admin.academic_periods.show', $period)); ?>" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('admin.academic_periods.edit', $period)); ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="<?php echo e(route('admin.academic_periods.destroy', $period)); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn-icon btn-delete" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:24px;color:#aaa;">
                            No academic periods found. <a href="<?php echo e(route('admin.academic_periods.create')); ?>" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\academic_periods\index.blade.php ENDPATH**/ ?>