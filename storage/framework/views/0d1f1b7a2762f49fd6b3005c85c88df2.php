<?php $__env->startSection('title', 'Issue Certificate'); ?>
<?php $activeNav = 'certificates'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Issue Certificate','subtitle' => 'Create a completion certificate for a student.','icon' => 'fa-certificate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Issue Certificate','subtitle' => 'Create a completion certificate for a student.','icon' => 'fa-certificate']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.certificates.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-pen"></i> Certificate Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('admin.certificates.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="form-grid">
                    <div class="form-field">
                        <label>Class <span class="required">*</span></label>
                        <select name="class_id" required>
                            <option value="">— Select class —</option>
                            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" <?php if(old('class_id', $preselectedClassId) == $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?> — <?php echo e($class->course?->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('class_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">— From class —</option>
                            <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($course->id); ?>" <?php if(old('course_id') == $course->id): echo 'selected'; endif; ?>><?php echo e($course->code); ?> — <?php echo e($course->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('course_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Student <span class="required">*</span></label>
                        <select name="student_id" required>
                            <option value="">— Select student —</option>
                            <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($student->id); ?>" <?php if(old('student_id', $preselectedStudentId) == $student->id): echo 'selected'; endif; ?>><?php echo e($student->name); ?> (<?php echo e($student->email); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('student_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Template Name</label>
                        <input type="text" name="template_name" value="<?php echo e(old('template_name')); ?>" placeholder="e.g. Standard Completion">
                        <span class="field-error"><?php echo e($errors->first('template_name')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Student Name Display</label>
                        <input type="text" name="student_name_display" value="<?php echo e(old('student_name_display')); ?>" placeholder="Overrides student name">
                        <span class="field-error"><?php echo e($errors->first('student_name_display')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Course Name Display</label>
                        <input type="text" name="course_name_display" value="<?php echo e(old('course_name_display')); ?>" placeholder="Overrides course name">
                        <span class="field-error"><?php echo e($errors->first('course_name_display')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Completion Date</label>
                        <input type="date" name="completion_date" value="<?php echo e(old('completion_date', now()->format('Y-m-d'))); ?>">
                        <span class="field-error"><?php echo e($errors->first('completion_date')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Final Grade</label>
                        <input type="number" name="final_grade" min="0" max="100" step="0.01" value="<?php echo e(old('final_grade')); ?>">
                        <span class="field-error"><?php echo e($errors->first('final_grade')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            <option value="issued" <?php if(old('status', 'issued') === 'issued'): echo 'selected'; endif; ?>>Issued</option>
                            <option value="revoked" <?php if(old('status') === 'revoked'): echo 'selected'; endif; ?>>Revoked</option>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('status')); ?></span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('admin.certificates.index')); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-certificate"></i> Issue Certificate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\certificates\create.blade.php ENDPATH**/ ?>