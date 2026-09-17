<?php $__env->startSection('title', 'Announcements — ' . $course->code); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Announcements';
    $pageIcon = '<i class="fa-solid fa-bullhorn"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-bullhorn"></i>
            Announcements — <?php echo e($course->code); ?>

        </h2>
        <div class="page-actions">
            <a href="<?php echo e(route('instructor.courses.show', $course)); ?>" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Course
            </a>
            <a href="<?php echo e(route('instructor.courses.announcements.create', $course)); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Create Announcement
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-bullhorn"></i> Announcements (<?php echo e($announcements->total()); ?>)</h3>
        </div>
        <div style="padding: 16px 24px;">
            <?php $__empty_1 = true; $__currentLoopData = $announcements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $announcement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(244,63,94,.13);color:#fda4af"><i class="fa-solid fa-bullhorn"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title"><?php echo e($announcement->title); ?>

                            <?php if($announcement->is_pinned ?? false): ?>
                                <span class="badge-published"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
                            <?php endif; ?>
                        </div>
                        <div class="module-mini-meta">
                            <?php echo e($announcement->class?->code ?? 'Course-wide'); ?>

                            <span style="margin:0 8px;">·</span><?php echo e($announcement->created_at?->diffForHumans()); ?>

                        </div>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <a href="<?php echo e(route('instructor.courses.announcements.show', [$course, $announcement])); ?>" class="btn-icon btn-view" title="View">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <a href="<?php echo e(route('instructor.courses.announcements.edit', [$course, $announcement])); ?>" class="btn-icon btn-edit" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="tab-empty-state">
                    <i class="fa-solid fa-bullhorn"></i>
                    No announcements yet. <a href="<?php echo e(route('instructor.courses.announcements.create', $course)); ?>">Post one</a>
                </div>
            <?php endif; ?>
        </div>
        <?php if($announcements->hasPages()): ?>
            <div style="padding:14px 24px;"><?php echo e($announcements->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\announcements\index.blade.php ENDPATH**/ ?>