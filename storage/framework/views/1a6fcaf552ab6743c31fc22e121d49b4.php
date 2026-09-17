<?php $__env->startSection('title', 'Edit Module - ' . $course->name); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Edit Module';
    $pageIcon = '<i class="fa-solid fa-layer-group"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-layer-group"></i>
            Edit Module - <?php echo e($module->title); ?>

        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="form-card">
        <h3>Edit Module</h3>
        <form method="POST" action="<?php echo e(route('admin.courses.modules.update', [$course, $module])); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <div class="form-grid">
                <div class="form-field full">
                    <label>Title <span class="req">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Introduction to Programming" value="<?php echo e(old('title', $module->title)); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Module description..."><?php echo e(old('description', $module->description)); ?></textarea>
                </div>

                <div class="form-field full">
                    <label>Objectives</label>
                    <textarea name="objectives" rows="4" placeholder="Learning objectives for this module (one per line)..."><?php echo e(old('objectives', $module->objectives)); ?></textarea>
                </div>

                <div class="form-field">
                    <label>Position <span class="req">*</span></label>
                    <input type="number" name="position" required min="1" placeholder="e.g. 1" value="<?php echo e(old('position', $module->position)); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Status <span class="req">*</span></label>
                    <select name="status" required>
                        <option value="draft" <?php echo e(old('status', $module->status) === 'draft' ? 'selected' : ''); ?>>Draft</option>
                        <option value="published" <?php echo e(old('status', $module->status) === 'published' ? 'selected' : ''); ?>>Published</option>
                    </select>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Update Module</button>
                <a href="<?php echo e(route('admin.courses.modules.index', $course)); ?>" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 60px; border-radius: 8px; font-size: 0.92rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; margin-left: 10px;">Cancel</a>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\modules\edit.blade.php ENDPATH**/ ?>