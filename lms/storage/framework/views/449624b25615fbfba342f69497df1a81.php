<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-comments"></i> Discussions (<?php echo e($discussions->count()); ?>)</h3>
        <a href="<?php echo e(route('instructor.courses.discussions.create', $course)); ?>" class="btn-add">
            <i class="fa-solid fa-plus"></i>
            Create Discussion
        </a>
    </div>
    <div style="padding: 16px 24px;">
        <?php if($discussions->isNotEmpty()): ?>
            <?php $__currentLoopData = $discussions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $discussion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(34,211,238,.13);color:#67e8f9"><i class="fa-solid fa-comments"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title"><?php echo e($discussion->title); ?></div>
                        <div class="module-mini-meta">
                            <i class="fa-solid fa-school"></i> <?php echo e($discussion->class?->code ?? 'Course-wide'); ?>

                            <span style="margin: 0 8px;">·</span>
                            <i class="fa-solid fa-comment-dots"></i> <?php echo e($discussion->posts_count ?? $discussion->posts?->count() ?? 0); ?> posts
                            <?php if($discussion->is_pinned ?? false): ?>
                                <span style="margin: 0 8px;">·</span>
                                <span class="badge-published"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="<?php echo e(route('instructor.courses.discussions.show', [$course, $discussion])); ?>" class="btn-icon btn-view" title="View discussion">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <div class="tab-empty-state">
                <i class="fa-solid fa-comments"></i>
                No discussions yet.
                <a href="<?php echo e(route('instructor.courses.discussions.create', $course)); ?>">Start a discussion</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\_tab_discussions.blade.php ENDPATH**/ ?>