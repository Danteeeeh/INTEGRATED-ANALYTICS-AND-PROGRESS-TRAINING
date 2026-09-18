<?php $__env->startSection('title', 'Discussion Details'); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Discussion Details';
    $pageIcon = '<i class="fa-solid fa-comments"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-comments"></i>
            <?php echo e($discussion->title); ?>

        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="form-card">
        <h3>Discussion Information</h3>

        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
            <?php
                $typeBadge = match($discussion->type) {
                    'general' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'General'],
                    'academic' => ['bg' => '#ede9fe', 'color' => '#6d28d9', 'label' => 'Academic'],
                    'qna' => ['bg' => '#fef3c7', 'color' => '#b45309', 'label' => 'Q&amp;A'],
                    'graded' => ['bg' => '#dcfce7', 'color' => '#15803d', 'label' => 'Graded'],
                    default => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => ucfirst($discussion->type)],
                };
            ?>
            <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; background: <?php echo e($typeBadge['bg']); ?>; color: <?php echo e($typeBadge['color']); ?>;">
                <?php echo $typeBadge['label']; ?>

            </span>
            <?php if($discussion->is_pinned): ?>
                <span class="badge-active"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
            <?php endif; ?>
            <?php if($discussion->is_locked): ?>
                <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; background: #fee2e2; color: #dc2626;">
                    <i class="fa-solid fa-lock"></i> Locked
                </span>
            <?php endif; ?>
        </div>

        <div class="modal-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>
        <div class="modal-row"><span>Course:</span><span><?php echo e($discussion->course->code ?? '-'); ?> - <?php echo e(($discussion->course->name ?? $discussion->course->title) ?? 'N/A'); ?></span></div>
        <div class="modal-row"><span>Class:</span><span><?php echo e($discussion->class->code ?? 'N/A'); ?></span></div>
        <div class="modal-row"><span>Author:</span><span><?php echo e($discussion->user->name ?? 'N/A'); ?></span></div>
        <div class="modal-row"><span>Created:</span><span><?php echo e($discussion->created_at?->format('M d, Y g:i A') ?? 'N/A'); ?></span></div>
        <div class="modal-row"><span>Posts:</span><span><?php echo e(($discussion->posts_count ?? $discussion->posts->count()) ?? 0); ?></span></div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-align-left"></i> Content</div>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px; line-height: 1.7; color: #374151; white-space: pre-wrap;">
            <?php echo e($discussion->content ?? 'No content'); ?>

        </div>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <a href="<?php echo e(route('admin.discussions.edit', $discussion)); ?>" class="btn-submit" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center; padding: 10px 24px;">
                <i class="fa-solid fa-pen-to-square" style="margin-right: 6px;"></i> Edit
            </a>
            <form method="POST" action="<?php echo e(route('admin.discussions.pin', $discussion)); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" style="padding: 10px 24px; background: <?php echo e(($discussion->is_pinned ? '#fef3c7' : '#e5e7eb')); ?>; color: <?php echo e(($discussion->is_pinned ? '#b45309' : '#374151')); ?>; border: none; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer;">
                    <i class="fa-solid fa-thumbtack" style="margin-right: 6px;"></i> <?php echo e($discussion->is_pinned ? 'Unpin' : 'Pin'); ?>

                </button>
            </form>
            <form method="POST" action="<?php echo e(route('admin.discussions.lock', $discussion)); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" style="padding: 10px 24px; background: <?php echo e(($discussion->is_locked ? '#fee2e2' : '#e5e7eb')); ?>; color: <?php echo e(($discussion->is_locked ? '#dc2626' : '#374151')); ?>; border: none; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer;">
                    <i class="fa-solid fa-lock" style="margin-right: 6px;"></i> <?php echo e($discussion->is_locked ? 'Unlock' : 'Lock'); ?>

                </button>
            </form>
        </div>
    </div>

    <div style="margin: 0 24px 24px;">
        <a href="<?php echo e(route('admin.discussions.index')); ?>" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            ← Back to Discussions
        </a>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\discussions\show.blade.php ENDPATH**/ ?>