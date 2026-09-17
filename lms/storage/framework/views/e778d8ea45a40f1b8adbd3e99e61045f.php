<?php $__env->startSection('title', 'Discussions — ' . $course->code); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Discussions';
    $pageIcon = '<i class="fa-solid fa-comments"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-comments"></i>
            Discussions — <?php echo e($course->code); ?>

        </h2>
        <div class="page-actions">
            <a href="<?php echo e(route('instructor.courses.show', $course)); ?>" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Course
            </a>
            <a href="<?php echo e(route('instructor.courses.discussions.create', $course)); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Create Discussion
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-comments"></i> Discussions (<?php echo e($discussions->total()); ?>)</h3>
        </div>
        <div style="padding: 16px 24px;">
            <?php $__empty_1 = true; $__currentLoopData = $discussions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $discussion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(34,211,238,.13);color:#67e8f9"><i class="fa-solid fa-comments"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title"><?php echo e($discussion->title); ?>

                            <?php if($discussion->is_pinned ?? false): ?>
                                <span class="badge-published"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
                            <?php endif; ?>
                        </div>
                        <div class="module-mini-meta">
                            <?php echo e($discussion->class?->code ?? 'Course-wide'); ?>

                            <?php if($discussion->posts_count ?? null): ?>
                                <span style="margin:0 8px;">·</span><?php echo e($discussion->posts_count); ?> posts
                            <?php endif; ?>
                            <span style="margin:0 8px;">·</span><?php echo e($discussion->created_at?->diffForHumans()); ?>

                        </div>
                    </div>
                    <a href="<?php echo e(route('instructor.courses.discussions.show', [$course, $discussion])); ?>" class="btn-icon btn-view" title="View">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="tab-empty-state">
                    <i class="fa-solid fa-comments"></i>
                    No discussions yet. <a href="<?php echo e(route('instructor.courses.discussions.create', $course)); ?>">Start one</a>
                </div>
            <?php endif; ?>
        </div>
        <?php if($discussions->hasPages()): ?>
            <div style="padding:14px 24px;"><?php echo e($discussions->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\discussions\index.blade.php ENDPATH**/ ?>