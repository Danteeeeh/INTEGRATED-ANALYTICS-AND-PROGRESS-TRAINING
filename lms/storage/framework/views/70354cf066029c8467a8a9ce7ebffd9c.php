<?php $__env->startSection('title', 'Lessons — ' . $module->title); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Lessons';
    $pageIcon = '<i class="fa-solid fa-book-open"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-book-open"></i>
            Lessons — <?php echo e($module->title); ?>

        </h2>
        <div class="page-actions">
            <a href="<?php echo e(route('instructor.courses.modules.show', [$course, $module])); ?>" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Module
            </a>
            <a href="<?php echo e(route('instructor.courses.modules.lessons.create', [$course, $module])); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Add Lesson
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-book-open"></i> Lessons (<?php echo e($lessons->total()); ?>)</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $lessons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lesson): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($lesson->position ?? $loop->iteration); ?></td>
                        <td><?php echo e($lesson->title); ?></td>
                        <td><?php echo e(ucfirst($lesson->lesson_type ?? 'text')); ?></td>
                        <td><?php echo e($lesson->duration_minutes ? $lesson->duration_minutes . ' min' : '—'); ?></td>
                        <td>
                            <?php if($lesson->status === 'published'): ?>
                                <span class="badge-published">Published</span>
                            <?php elseif($lesson->status === 'archived'): ?>
                                <span class="badge-draft">Archived</span>
                            <?php else: ?>
                                <span class="badge-draft">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <a href="<?php echo e(route('instructor.courses.modules.lessons.show', [$course, $module, $lesson])); ?>" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('instructor.courses.modules.lessons.edit', [$course, $module, $lesson])); ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="<?php echo e(route('instructor.courses.modules.lessons.materials', [$course, $module, $lesson])); ?>" class="btn-icon" title="Materials" style="color:#62c9f5;">
                                <i class="fa-solid fa-folder-open"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No lessons yet. <a href="<?php echo e(route('instructor.courses.modules.lessons.create', [$course, $module])); ?>" style="color:#2563eb;text-decoration:underline;">Add one</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\modules\lessons\index.blade.php ENDPATH**/ ?>