<?php $__env->startSection('title', 'Create Virtual Class'); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'New Virtual Class';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            New Virtual Class
        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="form-card">
        <h3>Virtual Class Information</h3>
        <form method="POST" action="<?php echo e(route('admin.virtual_classes.store')); ?>">
            <?php echo csrf_field(); ?>

            <div class="modal-section-title"><i class="fa-solid fa-link"></i> Associations</div>
            <div class="form-grid">
                <div class="form-field">
                    <label>Course</label>
                    <select name="course_id">
                        <option value="">Select Course (optional)</option>
                        <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($course->id); ?>" <?php echo e(old('course_id') == $course->id ? 'selected' : ''); ?>>
                                <?php echo e($course->code); ?> - <?php echo e($course->title); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Class</label>
                    <select name="class_id">
                        <option value="">Select Class (optional)</option>
                        <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($class->id); ?>" <?php echo e(old('class_id') == $class->id ? 'selected' : ''); ?>>
                                <?php echo e($class->code); ?> (<?php echo e($class->course->code ?? 'N/A'); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Instructor <span class="req">*</span></label>
                    <select name="instructor_id" required>
                        <option value="">Select Instructor</option>
                        <?php $__currentLoopData = $instructors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $instructor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($instructor->id); ?>" <?php echo e(old('instructor_id') == $instructor->id ? 'selected' : ''); ?>>
                                <?php echo e($instructor->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <span class="field-error"></span>
                </div>
            </div>

            <div class="modal-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>
            <div class="form-grid">
                <div class="form-field full">
                    <label>Title <span class="req">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Week 3: Introduction to Algorithms" value="<?php echo e(old('title')); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Agenda, topics, notes..."><?php echo e(old('description')); ?></textarea>
                </div>
            </div>

            <div class="modal-section-title"><i class="fa-solid fa-calendar-days"></i> Schedule</div>
            <div class="form-grid">
                <div class="form-field">
                    <label>Meeting Date <span class="req">*</span></label>
                    <input type="date" name="meeting_date" required value="<?php echo e(old('meeting_date')); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Start Time <span class="req">*</span></label>
                    <input type="time" name="start_time" required value="<?php echo e(old('start_time')); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>End Time <span class="req">*</span></label>
                    <input type="time" name="end_time" required value="<?php echo e(old('end_time')); ?>">
                    <span class="field-error"></span>
                </div>
            </div>

            <div class="modal-section-title"><i class="fa-solid fa-video"></i> Meeting Details</div>
            <div class="form-grid">
                <div class="form-field">
                    <label>Meeting Provider <span class="req">*</span></label>
                    <select name="meeting_provider" required>
                        <option value="">Select Provider</option>
                        <option value="zoom" <?php echo e(old('meeting_provider') == 'zoom' ? 'selected' : ''); ?>>Zoom</option>
                        <option value="google_meet" <?php echo e(old('meeting_provider') == 'google_meet' ? 'selected' : ''); ?>>Google Meet</option>
                        <option value="microsoft_teams" <?php echo e(old('meeting_provider') == 'microsoft_teams' ? 'selected' : ''); ?>>Microsoft Teams</option>
                        <option value="other" <?php echo e(old('meeting_provider') == 'other' ? 'selected' : ''); ?>>Other</option>
                    </select>
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <label>Meeting URL</label>
                    <input type="url" name="meeting_url" placeholder="https://..." value="<?php echo e(old('meeting_url')); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Meeting ID</label>
                    <input type="text" name="meeting_id" placeholder="e.g. 123 456 7890" value="<?php echo e(old('meeting_id')); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Meeting Password</label>
                    <input type="text" name="meeting_password" placeholder="e.g. abc123" value="<?php echo e(old('meeting_password')); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Status <span class="req">*</span></label>
                    <select name="status" required>
                        <option value="scheduled" <?php echo e(old('status', 'scheduled') == 'scheduled' ? 'selected' : ''); ?>>Scheduled</option>
                        <option value="ongoing" <?php echo e(old('status') == 'ongoing' ? 'selected' : ''); ?>>Ongoing</option>
                        <option value="completed" <?php echo e(old('status') == 'completed' ? 'selected' : ''); ?>>Completed</option>
                        <option value="cancelled" <?php echo e(old('status') == 'cancelled' ? 'selected' : ''); ?>>Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="form-submit">
                <a href="<?php echo e(route('admin.virtual_classes.index')); ?>" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 60px; border-radius: 8px; font-size: 0.92rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; margin-right: 10px;">Cancel</a>
                <button type="submit" class="btn-submit">Create Virtual Class</button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\virtual_classes\create.blade.php ENDPATH**/ ?>