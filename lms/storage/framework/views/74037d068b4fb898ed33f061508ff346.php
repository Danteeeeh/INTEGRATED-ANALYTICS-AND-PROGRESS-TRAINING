<?php $__env->startSection('title', 'Quizzes — ' . $course->code); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Quizzes';
    $pageIcon = '<i class="fa-solid fa-circle-question"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-circle-question"></i>
            Quizzes — <?php echo e($course->code); ?>

        </h2>
        <div class="page-actions">
            <a href="<?php echo e(route('instructor.courses.show', $course)); ?>" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Course
            </a>
            <a href="<?php echo e(route('instructor.courses.quizzes.create', $course)); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Create Quiz
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-circle-question"></i> Quizzes (<?php echo e($quizzes->total()); ?>)</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Class</th>
                    <th>Time Limit</th>
                    <th>Passing</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $quizzes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($quiz->title); ?></td>
                        <td><?php echo e($quiz->class?->code ?? '—'); ?></td>
                        <td><?php echo e($quiz->time_limit_minutes ? $quiz->time_limit_minutes . ' min' : '—'); ?></td>
                        <td><?php echo e($quiz->passing_score_percent ? $quiz->passing_score_percent . '%' : '—'); ?></td>
                        <td>
                            <?php if($quiz->status === 'published'): ?>
                                <span class="badge-published">Published</span>
                            <?php elseif($quiz->status === 'closed'): ?>
                                <span class="badge-draft">Closed</span>
                            <?php else: ?>
                                <span class="badge-draft">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <a href="<?php echo e(route('instructor.courses.quizzes.show', [$course, $quiz])); ?>" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('instructor.courses.quizzes.edit', [$course, $quiz])); ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="<?php echo e(route('instructor.courses.quizzes.attempts', [$course, $quiz])); ?>" class="btn-icon" title="Attempts" style="color:#62c9f5;">
                                <i class="fa-solid fa-list-check"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No quizzes yet. <a href="<?php echo e(route('instructor.courses.quizzes.create', $course)); ?>" style="color:#2563eb;text-decoration:underline;">Create one</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if($quizzes->hasPages()): ?>
            <div style="padding:14px 24px;"><?php echo e($quizzes->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\quizzes\index.blade.php ENDPATH**/ ?>