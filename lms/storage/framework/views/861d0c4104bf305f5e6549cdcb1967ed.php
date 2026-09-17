<?php $__env->startSection('title', 'Edit Assignment — ' . $assignment->title); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Edit Assignment';
    $pageIcon = '<i class="fa-solid fa-pen-to-square"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Assignment — <?php echo e($assignment->title); ?>

        </h2>
        <div class="page-actions">
            <a href="<?php echo e(route('instructor.courses.assignments.show', [$course, $assignment])); ?>" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card course-form-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-file-pen"></i> Assignment Details</h3>
        </div>
        <form method="POST" action="<?php echo e(route('instructor.courses.assignments.update', [$course, $assignment])); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <div class="modal-section" style="padding: 24px;">
                <div class="modal-grid">
                    <div class="modal-row">
                        <label>Title <span style="color:#dc2626;">*</span></label>
                        <input type="text" name="title" value="<?php echo e(old('title', $assignment->title)); ?>" required class="form-input">
                        <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="modal-row">
                        <label>Class</label>
                        <select name="class_id" class="form-input">
                            <option value="">Select Class</option>
                            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" <?php echo e(old('class_id', $assignment->class_id) == $class->id ? 'selected' : ''); ?>><?php echo e($class->code); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['class_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="modal-row">
                        <label>Submission Type <span style="color:#dc2626;">*</span></label>
                        <select name="submission_type" required class="form-input">
                            <?php $__currentLoopData = $submissionTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" <?php echo e(old('submission_type', $assignment->submission_type) == $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['submission_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="modal-row">
                        <label>Points</label>
                        <input type="number" name="points" value="<?php echo e(old('points', $assignment->points)); ?>" min="0" class="form-input">
                        <?php $__errorArgs = ['points'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="modal-row">
                        <label>Due Date</label>
                        <input type="datetime-local" name="due_date" value="<?php echo e(old('due_date', $assignment->due_date?->format('Y-m-d\TH:i'))); ?>" class="form-input">
                        <?php $__errorArgs = ['due_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="modal-row">
                        <label>Status <span style="color:#dc2626;">*</span></label>
                        <select name="status" required class="form-input">
                            <option value="draft" <?php echo e(old('status', $assignment->status) == 'draft' ? 'selected' : ''); ?>>Draft</option>
                            <option value="published" <?php echo e(old('status', $assignment->status) == 'published' ? 'selected' : ''); ?>>Published</option>
                            <option value="closed" <?php echo e(old('status', $assignment->status) == 'closed' ? 'selected' : ''); ?>>Closed</option>
                        </select>
                        <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="modal-row" style="margin-top:14px;">
                    <label>Instructions</label>
                    <textarea name="instructions" rows="5" class="form-input"><?php echo e(old('instructions', $assignment->instructions)); ?></textarea>
                    <?php $__errorArgs = ['instructions'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="modal-footer" style="justify-content:flex-end; gap:10px; padding: 18px 24px;">
                <a href="<?php echo e(route('instructor.courses.assignments.show', [$course, $assignment])); ?>" class="btn-modal-cancel">Cancel</a>
                <button type="submit" class="btn-modal-save"><i class="fa-solid fa-save"></i> Update Assignment</button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\assignments\edit.blade.php ENDPATH**/ ?>