<?php $__env->startSection('title', 'Add Instructor'); ?>
<?php $activeNav = 'instructors'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Add Instructor','subtitle' => 'Create a new instructor account with role permissions.','icon' => 'fa-chalkboard-user']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Add Instructor','subtitle' => 'Create a new instructor account with role permissions.','icon' => 'fa-chalkboard-user']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.instructors.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-user-plus"></i> Instructor Information</h3>
        </div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('admin.instructors.store')); ?>" method="POST" class="enhanced-form">
                <?php echo csrf_field(); ?>

                <div class="form-section" data-section="1">
                    <div class="modal-section-title">
                        <i class="fa-solid fa-user"></i> Personal Information
                    </div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>First Name <span class="required">*</span></label>
                            <input type="text" name="first_name" value="<?php echo e(old('first_name')); ?>" required placeholder="Enter first name" autocomplete="given-name">
                            <span class="field-error"><?php echo e($errors->first('first_name')); ?></span>
                        </div>
                        <div class="form-field">
                            <label>Last Name <span class="required">*</span></label>
                            <input type="text" name="last_name" value="<?php echo e(old('last_name')); ?>" required placeholder="Enter last name" autocomplete="family-name">
                            <span class="field-error"><?php echo e($errors->first('last_name')); ?></span>
                        </div>
                        <div class="form-field full">
                            <label>Email Address <span class="required">*</span></label>
                            <input type="email" name="email" value="<?php echo e(old('email')); ?>" required placeholder="instructor@example.com" autocomplete="email">
                            <span class="field-error"><?php echo e($errors->first('email')); ?></span>
                        </div>
                    </div>
                </div>

                <div class="form-section" data-section="2">
                    <div class="modal-section-title">
                        <i class="fa-solid fa-cog"></i> Account Settings
                    </div>
                    <div class="form-grid">
                        <div class="form-field full">
                            <label>Password <span class="required">*</span></label>
                            <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters" autocomplete="new-password">
                            <span class="field-error"><?php echo e($errors->first('password')); ?></span>
                        </div>
                        <div class="form-field">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo e(old('phone')); ?>" placeholder="09XXXXXXXXX" autocomplete="tel">
                            <span class="field-error"><?php echo e($errors->first('phone')); ?></span>
                        </div>
                        <div class="form-field">
                            <label>Instructor ID</label>
                            <input type="text" name="identifier" value="<?php echo e(old('identifier')); ?>" placeholder="Instructor ID (optional)">
                            <span class="field-error"><?php echo e($errors->first('identifier')); ?></span>
                        </div>
                        <div class="form-field full">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" <?php if(old('status', 'active') === 'active'): echo 'selected'; endif; ?>>Active</option>
                                <option value="inactive" <?php if(old('status') === 'inactive'): echo 'selected'; endif; ?>>Inactive</option>
                                <option value="suspended" <?php if(old('status') === 'suspended'): echo 'selected'; endif; ?>>Suspended</option>
                            </select>
                            <span class="field-error"><?php echo e($errors->first('status')); ?></span>
                        </div>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('admin.instructors.index')); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-user-plus"></i> Create Instructor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\instructors\create.blade.php ENDPATH**/ ?>