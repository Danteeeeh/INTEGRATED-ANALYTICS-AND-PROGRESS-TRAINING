<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-circle-question"></i> Quizzes (<?php echo e($quizzes->count()); ?>)</h3>
        <a href="<?php echo e($course->classes->first() ? route('instructor.courses.quizzes.create', [$course, 'class_id' => $course->classes->first()->id]) : '#'); ?>" class="btn-add">
            <i class="fa-solid fa-plus"></i>
            Create Quiz
        </a>
    </div>
    <div style="padding: 16px 24px;">
        <?php if($quizzes->isNotEmpty()): ?>
            <?php $__currentLoopData = $quizzes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(139,92,246,.14);color:#c4b5fd"><i class="fa-solid fa-circle-question"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title"><?php echo e($quiz->title); ?></div>
                        <div class="module-mini-meta">
                            <i class="fa-solid fa-school"></i> <?php echo e($quiz->class?->code ?? 'Unassigned'); ?>

                            <?php if($quiz->time_limit_minutes): ?>
                                <span style="margin: 0 8px;">·</span>
                                <i class="fa-solid fa-stopwatch"></i> <?php echo e($quiz->time_limit_minutes); ?> min
                            <?php endif; ?>
                            <?php if($quiz->passing_score_percent): ?>
                                <span style="margin: 0 8px;">·</span>
                                <i class="fa-solid fa-bullseye"></i> Pass <?php echo e($quiz->passing_score_percent); ?>%
                            <?php endif; ?>
                            <span style="margin: 0 8px;">·</span>
                            <?php if($quiz->status === 'published'): ?>
                                <span class="badge-published">Published</span>
                            <?php elseif($quiz->status === 'closed'): ?>
                                <span class="badge-draft">Closed</span>
                            <?php else: ?>
                                <span class="badge-draft" style="background:rgba(148,163,184,.14);color:#cbd5e1">Draft</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="<?php echo e(route('instructor.courses.quizzes.show', [$course, $quiz])); ?>" class="btn-icon btn-view" title="View quiz">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <div class="tab-empty-state">
                <i class="fa-solid fa-circle-question"></i>
                No quizzes yet.
                <a href="<?php echo e($course->classes->first() ? route('instructor.courses.quizzes.create', [$course, 'class_id' => $course->classes->first()->id]) : '#'); ?>">Create one</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\_tab_quizzes.blade.php ENDPATH**/ ?>