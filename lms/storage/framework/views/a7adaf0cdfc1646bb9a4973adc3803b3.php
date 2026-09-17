<?php $__env->startSection('title', 'Edit Module'); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Edit Module';
    $pageIcon = '<i class="fa-solid fa-pen-to-square"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Module — <?php echo e($course->code); ?>

        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <style>
        .breadcrumb-row { margin: 0 24px 16px; display: flex; align-items: center; gap: 8px; font-size: 0.84rem; color: #64748b; }
        .breadcrumb-row a { color: #2563eb; text-decoration: none; }
        .breadcrumb-row a:hover { text-decoration: underline; }
    </style>

    <div class="breadcrumb-row">
        <a href="<?php echo e(route('instructor.courses.index')); ?>">Courses</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
        <a href="<?php echo e(route('instructor.courses.show', $course)); ?>"><?php echo e($course->code); ?></a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
        <a href="<?php echo e(route('instructor.courses.modules.index', $course)); ?>">Modules</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
        <a href="<?php echo e(route('instructor.courses.modules.show', [$course, $module])); ?>"><?php echo e(Str::limit($module->title, 30)); ?></a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
        <span>Edit</span>
    </div>

    <div class="form-card">
        <h3>Edit Module</h3>
        <form method="POST" action="<?php echo e(route('instructor.courses.modules.update', [$course, $module])); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <div class="form-grid">
                <div class="form-field full">
                    <label>Module Title <span class="req">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Introduction to Programming" value="<?php echo e(old('title', $module->title)); ?>">
                </div>

                <div class="form-field">
                    <label>Order</label>
                    <input type="number" name="order" min="1" placeholder="e.g. 1" value="<?php echo e(old('order', $module->order)); ?>">
                </div>

                <div class="form-field">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft" <?php echo e(old('status', $module->status) == 'draft' ? 'selected' : ''); ?>>Draft</option>
                        <option value="published" <?php echo e(old('status', $module->status) == 'published' ? 'selected' : ''); ?>>Published</option>
                        <option value="archived" <?php echo e(old('status', $module->status) == 'archived' ? 'selected' : ''); ?>>Archived</option>
                    </select>
                </div>

                <div class="form-field full">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Describe what students will learn in this module..."><?php echo e(old('description', $module->description)); ?></textarea>
                </div>

                <div class="form-field full">
                    <label>Objectives / Learning Outcomes</label>
                    <textarea name="objectives" rows="3" placeholder="List the key learning objectives..."><?php echo e(old('objectives', $module->objectives)); ?></textarea>
                </div>
            </div>
            <div class="form-submit" style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="<?php echo e(route('instructor.courses.modules.show', [$course, $module])); ?>" class="btn-modal-cancel" style="padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; text-decoration: none;">Cancel</a>
                <button type="submit" class="btn-submit">Save Changes</button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\modules\edit.blade.php ENDPATH**/ ?>