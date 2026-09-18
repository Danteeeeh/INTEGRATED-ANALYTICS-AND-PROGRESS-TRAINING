<?php $__env->startSection('title', 'Create Virtual Class - ' . $class->name); ?>
<?php
    $activeNav = 'classes';
    $pageTitle = 'Create Virtual Class';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            Create Virtual Class &mdash; <?php echo e($class->code); ?>

        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="form-card">
        <h3>New Virtual Class</h3>
        <form method="POST" action="<?php echo e(route('instructor.classes.virtual_classes.store', $class)); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-grid">
                <div class="form-field full">
                    <label>Title <span class="req">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Week 3 - Introduction to Algorithms" value="<?php echo e(old('title')); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Agenda, topics, materials to prepare..."><?php echo e(old('description')); ?></textarea>
                </div>

                <div class="form-field">
                    <label>Date <span class="req">*</span></label>
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

                <div class="form-field">
                    <label>Provider <span class="req">*</span></label>
                    <select name="meeting_provider" required>
                        <option value="">Select a provider</option>
                        <?php $__currentLoopData = $providers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php echo e(old('meeting_provider') == $value ? 'selected' : ''); ?>>
                                <?php echo e($label); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                    <input type="text" name="meeting_password" placeholder="e.g. Abc123" value="<?php echo e(old('meeting_password')); ?>">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Status <span class="req">*</span></label>
                    <select name="status" required>
                        <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php echo e(old('status', 'scheduled') == $value ? 'selected' : ''); ?>>
                                <?php echo e($label); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <span class="field-error"></span>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Create Virtual Class</button>
                <a href="<?php echo e(route('instructor.classes.virtual_classes.index', $class)); ?>" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\virtual_classes\create.blade.php ENDPATH**/ ?>