<?php $__env->startSection('title', 'Edit Student'); ?>
<?php $activeNav = 'students'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Edit Student','subtitle' => 'Update student account details.','icon' => 'fa-user-graduate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Edit Student','subtitle' => 'Update student account details.','icon' => 'fa-user-graduate']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('registrar.students.show', $student)); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
        <div class="user-panel-head"><h3><i class="fa-solid fa-user-pen"></i> Student Information</h3></div>
        <div class="user-panel-body">
            <form method="POST" action="<?php echo e(route('registrar.students.update', $student)); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="form-grid">
                    <div class="form-field">
                        <label>First Name <span class="required">*</span></label>
                        <input name="first_name" value="<?php echo e(old('first_name', $student->first_name)); ?>" required placeholder="Enter first name">
                        <span class="field-error"><?php echo e($errors->first('first_name')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Last Name <span class="required">*</span></label>
                        <input name="last_name" value="<?php echo e(old('last_name', $student->last_name)); ?>" required placeholder="Enter last name">
                        <span class="field-error"><?php echo e($errors->first('last_name')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" value="<?php echo e(old('email', $student->email)); ?>" required placeholder="student@example.com">
                        <span class="field-error"><?php echo e($errors->first('email')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Student ID</label>
                        <input name="identifier" value="<?php echo e(old('identifier', $student->identifier)); ?>" placeholder="Student ID (optional)">
                        <span class="field-error"><?php echo e($errors->first('identifier')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            <?php $__currentLoopData = ['active', 'inactive', 'pending', 'suspended']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($status); ?>" <?php if(old('status', $student->status) === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('status')); ?></span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('registrar.students.show', $student)); ?>" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save Student</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.registrar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\registrar\students\edit.blade.php ENDPATH**/ ?>