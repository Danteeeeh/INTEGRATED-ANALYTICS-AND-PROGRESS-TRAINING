<?php $__env->startSection('title', 'Edit Attendance'); ?>
<?php $activeNav = 'attendance'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Edit Attendance','subtitle' => 'Update attendance details for a student session.','icon' => 'fa-clipboard-user']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Edit Attendance','subtitle' => 'Update attendance details for a student session.','icon' => 'fa-clipboard-user']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.attendance.show', $attendanceRecord)); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5)): ?>
<?php $attributes = $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5; ?>
<?php unset($__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8fc5d82814dad270c8dc67128a2a98d5)): ?>
<?php $component = $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5; ?>
<?php unset($__componentOriginal8fc5d82814dad270c8dc67128a2a98d5); ?>
<?php endif; ?>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Record Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('admin.attendance.update', $attendanceRecord)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="form-grid">
                    <div class="form-field">
                        <label>Student <span class="required">*</span></label>
                        <select name="student_id" required>
                            <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($student->id); ?>" <?php if(old('student_id', $attendanceRecord->student_id) == $student->id): echo 'selected'; endif; ?>><?php echo e($student->name); ?> (<?php echo e($student->email); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('student_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Class <span class="required">*</span></label>
                        <select name="class_id" required>
                            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" <?php if(old('class_id', $attendanceRecord->class_id) == $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?> — <?php echo e($class->course?->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('class_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Virtual Class</label>
                        <select name="virtual_class_id">
                            <option value="">— None —</option>
                            <?php $__currentLoopData = $virtualClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $virtualClass): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($virtualClass->id); ?>" <?php if(old('virtual_class_id', $attendanceRecord->virtual_class_id) == $virtualClass->id): echo 'selected'; endif; ?>><?php echo e($virtualClass->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('virtual_class_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Date <span class="required">*</span></label>
                        <input type="date" name="attendance_date" value="<?php echo e(old('attendance_date', $attendanceRecord->attendance_date?->format('Y-m-d'))); ?>" required>
                        <span class="field-error"><?php echo e($errors->first('attendance_date')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Session Title</label>
                        <input type="text" name="session_title" value="<?php echo e(old('session_title', $attendanceRecord->session_title)); ?>" placeholder="e.g. Week 3 lecture">
                        <span class="field-error"><?php echo e($errors->first('session_title')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Status <span class="required">*</span></label>
                        <select name="status" required>
                            <?php $__currentLoopData = ['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'excused' => 'Excused']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('status', $attendanceRecord->status) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('status')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Joined At</label>
                        <input type="datetime-local" name="joined_at" value="<?php echo e(old('joined_at', $attendanceRecord->joined_at?->format('Y-m-d\TH:i'))); ?>">
                        <span class="field-error"><?php echo e($errors->first('joined_at')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Left At</label>
                        <input type="datetime-local" name="left_at" value="<?php echo e(old('left_at', $attendanceRecord->left_at?->format('Y-m-d\TH:i'))); ?>">
                        <span class="field-error"><?php echo e($errors->first('left_at')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration_minutes" min="0" value="<?php echo e(old('duration_minutes', $attendanceRecord->duration_minutes)); ?>">
                        <span class="field-error"><?php echo e($errors->first('duration_minutes')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Notes</label>
                        <textarea name="notes" rows="3" maxlength="500" placeholder="Optional note"><?php echo e(old('notes', $attendanceRecord->notes)); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('notes')); ?></span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('admin.attendance.show', $attendanceRecord)); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\attendance\edit.blade.php ENDPATH**/ ?>