<?php $__env->startSection('title', 'Edit Discussion'); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Edit Discussion';
    $pageIcon = '<i class="fa-solid fa-comments"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-comments"></i>
            Edit Discussion
        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="form-card">
        <h3>Edit Discussion</h3>
        <form method="POST" action="<?php echo e(route('admin.discussions.update', $discussion)); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <div class="form-grid">
                <div class="form-field full">
                    <label>Title <span class="req">*</span></label>
                    <input type="text" name="title" required placeholder="Discussion title" value="<?php echo e(old('title', $discussion->title)); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Course</label>
                    <select name="course_id">
                        <option value="">Select Course (optional)</option>
                        <?php $__currentLoopData = $courses ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($course->id); ?>" <?php echo e(old('course_id', $discussion->course_id) == $course->id ? 'selected' : ''); ?>>
                                <?php echo e($course->code); ?> - <?php echo e($course->name ?? $course->title); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Class</label>
                    <select name="class_id">
                        <option value="">Select Class (optional)</option>
                        <?php $__currentLoopData = $classes ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($class->id); ?>" <?php echo e(old('class_id', $discussion->class_id) == $class->id ? 'selected' : ''); ?>>
                                <?php echo e($class->code); ?> (<?php echo e($class->course->code ?? 'N/A'); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Type <span class="req">*</span></label>
                    <select name="type" required>
                        <option value="">Select Type</option>
                        <option value="general" <?php echo e(old('type', $discussion->type) == 'general' ? 'selected' : ''); ?>>General</option>
                        <option value="academic" <?php echo e(old('type', $discussion->type) == 'academic' ? 'selected' : ''); ?>>Academic</option>
                        <option value="qna" <?php echo e(old('type', $discussion->type) == 'qna' ? 'selected' : ''); ?>>Q&amp;A</option>
                        <option value="graded" <?php echo e(old('type', $discussion->type) == 'graded' ? 'selected' : ''); ?>>Graded</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Options</label>
                    <div style="display: flex; gap: 20px; padding: 10px 0;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="is_pinned" value="1" <?php echo e(old('is_pinned', $discussion->is_pinned) ? 'checked' : ''); ?> style="width: 18px; height: 18px;">
                            <span style="font-size: 0.88rem;">Pin this discussion</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="is_locked" value="1" <?php echo e(old('is_locked', $discussion->is_locked) ? 'checked' : ''); ?> style="width: 18px; height: 18px;">
                            <span style="font-size: 0.88rem;">Lock this discussion</span>
                        </label>
                    </div>
                </div>

                <div class="form-field full">
                    <label>Content <span class="req">*</span></label>
                    <textarea name="content" rows="8" required placeholder="Write your discussion content here..."><?php echo e(old('content', $discussion->content)); ?></textarea>
                    <span class="field-error"></span>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Update Discussion</button>
                <a href="<?php echo e(route('admin.discussions.index')); ?>" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 60px; border-radius: 8px; font-size: 0.92rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; margin-left: 10px;">Cancel</a>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\discussions\edit.blade.php ENDPATH**/ ?>