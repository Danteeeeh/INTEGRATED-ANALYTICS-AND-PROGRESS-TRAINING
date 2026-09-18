<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-file-pen"></i> Assignments (<?php echo e($assignments->count()); ?>)</h3>
        <a href="<?php echo e($course->classes->first() ? route('instructor.courses.assignments.create', [$course, 'class_id' => $course->classes->first()->id]) : '#'); ?>" class="btn-add">
            <i class="fa-solid fa-plus"></i>
            Create Assignment
        </a>
    </div>
    <div style="padding: 16px 24px;">
        <?php if($assignments->isNotEmpty()): ?>
            <?php $__currentLoopData = $assignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(251,191,36,.14);color:#fcd34d"><i class="fa-solid fa-file-pen"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title"><?php echo e($assignment->title); ?></div>
                        <div class="module-mini-meta">
                            <i class="fa-solid fa-school"></i> <?php echo e($assignment->class?->code ?? 'Unassigned'); ?>

                            <?php if($assignment->points): ?>
                                <span style="margin: 0 8px;">·</span>
                                <i class="fa-solid fa-star"></i> <?php echo e($assignment->points); ?> pts
                            <?php endif; ?>
                            <?php if($assignment->due_date): ?>
                                <span style="margin: 0 8px;">·</span>
                                <i class="fa-solid fa-clock"></i> Due <?php echo e($assignment->due_date?->format('M j, Y')); ?>

                            <?php endif; ?>
                            <span style="margin: 0 8px;">·</span>
                            <?php if($assignment->status === 'published'): ?>
                                <span class="badge-published">Published</span>
                            <?php elseif($assignment->status === 'closed'): ?>
                                <span class="badge-draft">Closed</span>
                            <?php else: ?>
                                <span class="badge-draft" style="background:rgba(148,163,184,.14);color:#cbd5e1">Draft</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="<?php echo e(route('instructor.courses.assignments.show', [$course, $assignment])); ?>" class="btn-icon btn-view" title="View assignment">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <div class="tab-empty-state">
                <i class="fa-solid fa-file-pen"></i>
                No assignments yet.
                <a href="<?php echo e($course->classes->first() ? route('instructor.courses.assignments.create', [$course, 'class_id' => $course->classes->first()->id]) : '#'); ?>">Create one</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\_tab_assignments.blade.php ENDPATH**/ ?>