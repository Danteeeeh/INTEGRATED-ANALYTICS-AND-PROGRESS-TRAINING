<?php $__env->startSection('title', 'Edit Assignment'); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Edit Assignment';
    $pageIcon = '<i class="fa-solid fa-file-circle-check"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-circle-check"></i>
            Edit Assignment
        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="form-card">
        <h3>Edit Assignment</h3>
        <form method="POST" action="<?php echo e(route('admin.assignments.update', $assignment)); ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <div class="form-grid">
                <div class="form-field full">
                    <label>Title <span class="req">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Midterm Project" value="<?php echo e(old('title', $assignment->title)); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <label>Instructions</label>
                    <textarea name="instructions" rows="5" placeholder="Assignment instructions and requirements..."><?php echo e(old('instructions', $assignment->instructions)); ?></textarea>
                </div>

                <div class="form-field">
                    <label>Class <span class="req">*</span></label>
                    <select name="class_id" required>
                        <option value="">Select Class</option>
                        <?php $__currentLoopData = $classes ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($class->id); ?>" <?php echo e(old('class_id', $assignment->class_id) == $class->id ? 'selected' : ''); ?>>
                                <?php echo e($class->name); ?> (<?php echo e($class->course->name ?? 'No Course'); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Points <span class="req">*</span></label>
                    <input type="number" name="points" required min="0" placeholder="e.g. 100" value="<?php echo e(old('points', $assignment->points)); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Due Date <span class="req">*</span></label>
                    <input type="datetime-local" name="due_date" required value="<?php echo e(old('due_date', $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : '')); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Submission Type <span class="req">*</span></label>
                    <select name="submission_type" required>
                        <option value="">Select Type</option>
                        <option value="<?php echo e(\App\Models\Assignment::TYPE_TEXT); ?>" <?php echo e(old('submission_type', $assignment->submission_type) == \App\Models\Assignment::TYPE_TEXT ? 'selected' : ''); ?>>Text Only</option>
                        <option value="<?php echo e(\App\Models\Assignment::TYPE_FILE); ?>" <?php echo e(old('submission_type', $assignment->submission_type) == \App\Models\Assignment::TYPE_FILE ? 'selected' : ''); ?>>Single File</option>
                        <option value="<?php echo e(\App\Models\Assignment::TYPE_MULTIPLE_FILES); ?>" <?php echo e(old('submission_type', $assignment->submission_type) == \App\Models\Assignment::TYPE_MULTIPLE_FILES ? 'selected' : ''); ?>>Multiple Files</option>
                    </select>
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Availability From</label>
                    <input type="datetime-local" name="availability_from" value="<?php echo e(old('availability_from', $assignment->availability_from ? $assignment->availability_from->format('Y-m-d\TH:i') : '')); ?>">
                </div>

                <div class="form-field">
                    <label>Availability Until</label>
                    <input type="datetime-local" name="availability_until" value="<?php echo e(old('availability_until', $assignment->availability_until ? $assignment->availability_until->format('Y-m-d\TH:i') : '')); ?>">
                </div>

                <div class="form-field">
                    <label>Resubmission Limit</label>
                    <input type="number" name="resubmission_limit" min="0" placeholder="e.g. 3 (0 = unlimited)" value="<?php echo e(old('resubmission_limit', $assignment->max_attempts ?? 0)); ?>">
                </div>

                <div class="form-field">
                    <label>Rubric</label>
                    <select name="rubric_id">
                        <option value="">None</option>
                        <?php $__currentLoopData = $rubrics ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rubric): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($rubric->id); ?>" <?php echo e(old('rubric_id', $assignment->rubric_id) == $rubric->id ? 'selected' : ''); ?>>
                                <?php echo e($rubric->title); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="form-field full">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="allow_late" value="1" <?php echo e(old('allow_late', $assignment->allow_late) ? 'checked' : ''); ?> style="width:auto;">
                        Allow Late Submissions
                    </label>
                </div>

                <div class="form-field full">
                    <label>Status</label>
                    <select name="status">
                        <option value="<?php echo e(\App\Models\Assignment::STATUS_DRAFT); ?>" <?php echo e(old('status', $assignment->status) == \App\Models\Assignment::STATUS_DRAFT ? 'selected' : ''); ?>>Draft</option>
                        <option value="<?php echo e(\App\Models\Assignment::STATUS_PUBLISHED); ?>" <?php echo e(old('status', $assignment->status) == \App\Models\Assignment::STATUS_PUBLISHED ? 'selected' : ''); ?>>Published</option>
                        <option value="<?php echo e(\App\Models\Assignment::STATUS_CLOSED); ?>" <?php echo e(old('status', $assignment->status) == \App\Models\Assignment::STATUS_CLOSED ? 'selected' : ''); ?>>Closed</option>
                    </select>
                </div>

                <?php if($assignment->attachments && $assignment->attachments->count() > 0): ?>
                    <div class="form-field full">
                        <label>Current Attachments</label>
                        <div style="padding:12px;background:#f7f9fc;border-radius:8px;border:1px solid #e5e7eb;">
                            <?php $__currentLoopData = $assignment->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div style="padding:8px 0;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
                                    <span><i class="fa-solid fa-paperclip" style="color:#6b7280;margin-right:6px;"></i><?php echo e($att->filename); ?></span>
                                    <span class="btn btn-sm btn-secondary" aria-disabled="true" title="Attachment removal is not available yet"><i class="fa-solid fa-lock"></i> Remove unavailable</span>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-field full">
                    <label>Add Attachments</label>
                    <input type="file" name="attachments[]" multiple style="padding:10px;border:1.5px dashed #d0d7e2;border-radius:8px;background:#fafbfc;">
                    <span style="font-size:0.75rem;color:#888;">Upload additional files (optional)</span>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Update Assignment</button>
                <a href="<?php echo e(route('admin.assignments.index')); ?>" class="btn-modal-cancel" style="display:inline-flex;align-items:center;justify-content:center;padding:11px 60px;border-radius:8px;font-size:0.92rem;font-weight:600;cursor:pointer;transition:background 0.2s;text-decoration:none;margin-left:10px;">Cancel</a>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\assignments\edit.blade.php ENDPATH**/ ?>