<?php $__env->startSection('title', $lesson->title); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = $lesson->title;
    $pageIcon = '<i class="fa-solid fa-book-open"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-book-open"></i>
            <?php echo e($lesson->title); ?>

        </h2>
        <div class="page-actions">
            <a href="<?php echo e(route('instructor.courses.modules.lessons.index', [$course, $module])); ?>" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Lessons
            </a>
            <a href="<?php echo e(route('instructor.courses.modules.lessons.edit', [$course, $module, $lesson])); ?>" class="btn-add">
                <i class="fa-solid fa-pen-to-square"></i>
                Edit Lesson
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card" style="margin-bottom:20px;">
        <div class="crud-header">
            <h3><i class="fa-solid fa-circle-info"></i> Lesson Details</h3>
            <?php if($lesson->status === 'published'): ?>
                <span class="badge-published">Published</span>
            <?php else: ?>
                <span class="badge-draft">Draft</span>
            <?php endif; ?>
        </div>
        <div style="padding: 20px 24px; display:flex; flex-direction:column; gap:14px;">
            <div style="display:flex; gap:24px; flex-wrap:wrap;">
                <div><span class="dash-stat-label">Type</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;"><?php echo e(ucfirst($lesson->lesson_type ?? 'text')); ?></div></div>
                <div><span class="dash-stat-label">Duration</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;"><?php echo e($lesson->duration_minutes ? $lesson->duration_minutes . ' min' : '—'); ?></div></div>
                <div><span class="dash-stat-label">Position</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;"><?php echo e($lesson->position ?? '—'); ?></div></div>
                <?php if($lesson->external_url): ?>
                    <div><span class="dash-stat-label">External URL</span><div style="margin-top:3px;"><a href="<?php echo e($lesson->external_url); ?>" target="_blank" style="color:#62c9f5;"><?php echo e($lesson->external_url); ?></a></div></div>
                <?php endif; ?>
            </div>

            <?php if($lesson->description): ?>
                <div>
                    <span class="dash-stat-label">Description</span>
                    <p style="color:#c7d4ec;margin:4px 0 0;font-size:.88rem;line-height:1.5;"><?php echo e($lesson->description); ?></p>
                </div>
            <?php endif; ?>

            <?php if($lesson->objectives): ?>
                <div>
                    <span class="dash-stat-label">Objectives</span>
                    <p style="color:#c7d4ec;margin:4px 0 0;font-size:.88rem;line-height:1.5;"><?php echo e($lesson->objectives); ?></p>
                </div>
            <?php endif; ?>

            <?php if($lesson->content): ?>
                <div>
                    <span class="dash-stat-label">Content</span>
                    <div style="color:#c7d4ec;margin:4px 0 0;font-size:.88rem;line-height:1.6;white-space:pre-wrap;"><?php echo e($lesson->content); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-folder-open"></i> Materials (<?php echo e($lesson->materials->count()); ?>)</h3>
            <a href="<?php echo e(route('instructor.courses.modules.lessons.materials', [$course, $module, $lesson])); ?>" class="btn-add">
                <i class="fa-solid fa-folder-open"></i>
                Manage Materials
            </a>
        </div>
        <div style="padding: 16px 24px;">
            <?php $__empty_1 = true; $__currentLoopData = $lesson->materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $material): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="module-mini-card">
                    <div class="module-mini-icon"><i class="fa-solid fa-paperclip"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title"><?php echo e($material->pivot->title ?? $material->filename); ?></div>
                        <div class="module-mini-meta">
                            <?php echo e($material->file_type ?? 'File'); ?>

                            <?php if($material->pivot->is_required ?? false): ?>
                                <span style="margin:0 8px;">·</span><span class="badge-published">Required</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="tab-empty-state">
                    <i class="fa-solid fa-folder-open"></i>
                    No materials attached yet.
                    <a href="<?php echo e(route('instructor.courses.modules.lessons.materials', [$course, $module, $lesson])); ?>">Add materials</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\modules\lessons\show.blade.php ENDPATH**/ ?>