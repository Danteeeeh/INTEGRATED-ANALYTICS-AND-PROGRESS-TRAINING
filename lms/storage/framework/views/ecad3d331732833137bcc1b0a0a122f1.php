

<?php $__env->startSection('title', 'New Question Bank'); ?>
<?php $activeNav = 'question_banks'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'New Question Bank','subtitle' => 'Create a reusable set of questions.','icon' => 'fa-database']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'New Question Bank','subtitle' => 'Create a reusable set of questions.','icon' => 'fa-database']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.question_banks.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-pen"></i> Bank Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('admin.question_banks.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="<?php echo e(old('title')); ?>" required placeholder="e.g. Midterm Biology Questions">
                        <span class="field-error"><?php echo e($errors->first('title')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Code</label>
                        <input type="text" name="code" value="<?php echo e(old('code')); ?>" placeholder="e.g. BIO-MID" maxlength="50">
                        <span class="field-error"><?php echo e($errors->first('code')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Category</label>
                        <input type="text" name="category" value="<?php echo e(old('category')); ?>" placeholder="e.g. Biology" maxlength="100">
                        <span class="field-error"><?php echo e($errors->first('category')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">— General —</option>
                            <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($course->id); ?>" <?php if(old('course_id') == $course->id): echo 'selected'; endif; ?>><?php echo e($course->code); ?> — <?php echo e($course->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('course_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">— General —</option>
                            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" <?php if(old('class_id') == $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?> — <?php echo e($class->course?->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('class_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            <?php $__currentLoopData = ['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('status', 'draft') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('status')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_shared" value="1" <?php if(old('is_shared')): echo 'checked'; endif; ?>>
                            Share this bank across courses
                        </label>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Bank description"><?php echo e(old('description')); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('description')); ?></span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('admin.question_banks.index')); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Create Bank
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\question-banks\create.blade.php ENDPATH**/ ?>