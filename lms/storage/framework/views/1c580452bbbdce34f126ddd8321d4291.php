<?php $__env->startSection('title', 'Edit Academic Period'); ?>
<?php
    $activeNav = 'academic_periods';
    $pageTitle = 'Edit Academic Period';
    $pageIcon = '<i class="fa-solid fa-calendar"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-calendar"></i>
            Edit Academic Period
        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="form-card">
        <h3>Edit Academic Period</h3>
        <form method="POST" action="<?php echo e(route('admin.academic_periods.update', $academicPeriod)); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <div class="form-grid">
                <div class="form-field">
                    <label>Code <span class="req">*</span></label>
                    <input type="text" name="code" required placeholder="e.g. 2024-2025" value="<?php echo e(old('code', $academicPeriod->code)); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Name <span class="req">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. 2024-2025 Academic Year" value="<?php echo e(old('name', $academicPeriod->name)); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Start Date <span class="req">*</span></label>
                    <input type="date" name="start_date" required value="<?php echo e(old('start_date', $academicPeriod->start_date->format('Y-m-d'))); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>End Date <span class="req">*</span></label>
                    <input type="date" name="end_date" required value="<?php echo e(old('end_date', $academicPeriod->end_date->format('Y-m-d'))); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Period description..."><?php echo e(old('description', $academicPeriod->description)); ?></textarea>
                </div>

                <div class="form-field">
                    <label>Status</label>
                    <select name="is_active">
                        <option value="1" <?php echo e($academicPeriod->is_active ? 'selected' : ''); ?>>Active</option>
                        <option value="0" <?php echo e(!$academicPeriod->is_active ? 'selected' : ''); ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Update Period</button>
                <a href="<?php echo e(route('admin.academic_periods.index')); ?>" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 60px; border-radius: 8px; font-size: 0.92rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; margin-left: 10px;">Cancel</a>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\academic_periods\edit.blade.php ENDPATH**/ ?>