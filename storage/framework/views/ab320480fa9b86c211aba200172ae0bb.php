<?php $__env->startSection('title', 'Assignments — ' . $course->code); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Assignments';
    $pageIcon = '<i class="fa-solid fa-file-pen"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-pen"></i>
            Assignments — <?php echo e($course->code); ?>

        </h2>
        <div class="page-actions">
            <a href="<?php echo e(route('instructor.courses.show', $course)); ?>" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Course
            </a>
            <a href="<?php echo e(route('instructor.courses.assignments.create', $course)); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Create Assignment
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-file-pen"></i> Assignments (<?php echo e($assignments->total()); ?>)</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Class</th>
                    <th>Points</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $assignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($assignment->title); ?></td>
                        <td><?php echo e($assignment->class?->code ?? '—'); ?></td>
                        <td><?php echo e($assignment->points ?? '—'); ?></td>
                        <td><?php echo e($assignment->due_date?->format('M j, Y') ?? '—'); ?></td>
                        <td>
                            <?php if($assignment->status === 'published'): ?>
                                <span class="badge-published">Published</span>
                            <?php elseif($assignment->status === 'closed'): ?>
                                <span class="badge-draft">Closed</span>
                            <?php else: ?>
                                <span class="badge-draft">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <a href="<?php echo e(route('instructor.courses.assignments.show', [$course, $assignment])); ?>" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('instructor.courses.assignments.edit', [$course, $assignment])); ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <?php if($assignment->status === 'published'): ?>
                                <a href="<?php echo e(route('instructor.courses.assignments.submissions', [$course, $assignment])); ?>" class="btn-icon" title="Submissions" style="color:#62c9f5;">
                                    <i class="fa-solid fa-inbox"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No assignments yet. <a href="<?php echo e(route('instructor.courses.assignments.create', $course)); ?>" style="color:#2563eb;text-decoration:underline;">Create one</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if($assignments->hasPages()): ?>
            <div style="padding:14px 24px;"><?php echo e($assignments->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\assignments\index.blade.php ENDPATH**/ ?>