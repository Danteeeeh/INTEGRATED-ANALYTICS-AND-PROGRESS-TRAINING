<?php $__env->startSection('title', $assignment->title); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = $assignment->title;
    $pageIcon = '<i class="fa-solid fa-file-pen"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-pen"></i>
            <?php echo e($assignment->title); ?>

        </h2>
        <div class="page-actions">
            <a href="<?php echo e(route('instructor.courses.assignments.index', $course)); ?>" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back
            </a>
            <a href="<?php echo e(route('instructor.courses.assignments.edit', [$course, $assignment])); ?>" class="btn-add">
                <i class="fa-solid fa-pen-to-square"></i>
                Edit
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card" style="margin-bottom:20px;">
        <div class="crud-header">
            <h3><i class="fa-solid fa-circle-info"></i> Assignment Details</h3>
            <?php if($assignment->status === 'published'): ?>
                <span class="badge-published">Published</span>
            <?php elseif($assignment->status === 'closed'): ?>
                <span class="badge-draft">Closed</span>
            <?php else: ?>
                <span class="badge-draft">Draft</span>
            <?php endif; ?>
        </div>
        <div style="padding: 20px 24px; display:flex; flex-direction:column; gap:14px;">
            <div style="display:flex; gap:24px; flex-wrap:wrap;">
                <div><span class="dash-stat-label">Class</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;"><?php echo e($assignment->class?->code ?? '—'); ?></div></div>
                <div><span class="dash-stat-label">Points</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;"><?php echo e($assignment->points ?? '—'); ?></div></div>
                <div><span class="dash-stat-label">Submission Type</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;"><?php echo e(ucfirst($assignment->submission_type ?? 'text')); ?></div></div>
                <?php if($assignment->due_date): ?>
                    <div><span class="dash-stat-label">Due</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;"><?php echo e($assignment->due_date->format('M j, Y g:i A')); ?></div></div>
                <?php endif; ?>
            </div>
            <?php if($assignment->instructions): ?>
                <div>
                    <span class="dash-stat-label">Instructions</span>
                    <div style="color:#c7d4ec;margin:4px 0 0;font-size:.88rem;line-height:1.6;white-space:pre-wrap;"><?php echo e($assignment->instructions); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-inbox"></i> Submissions (<?php echo e($assignment->submissions->count()); ?>)</h3>
            <a href="<?php echo e(route('instructor.courses.assignments.submissions', [$course, $assignment])); ?>" class="btn-add">
                <i class="fa-solid fa-inbox"></i>
                Review Submissions
            </a>
        </div>
        <div style="padding: 16px 24px;">
            <?php $__empty_1 = true; $__currentLoopData = $assignment->submissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $submission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(52,211,153,.13);color:#6ee7b7"><i class="fa-solid fa-file-lines"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title"><?php echo e($submission->student?->full_name ?? 'Student'); ?></div>
                        <div class="module-mini-meta">
                            Status: <?php echo e(ucfirst($submission->status ?? 'submitted')); ?>

                            <?php if($submission->score !== null): ?>
                                · Score: <?php echo e($submission->score); ?>

                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="<?php echo e(route('instructor.courses.assignments.submissions.show', [$course, $assignment, $submission])); ?>" class="btn-icon btn-view" title="Review">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="tab-empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    No submissions yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\assignments\show.blade.php ENDPATH**/ ?>