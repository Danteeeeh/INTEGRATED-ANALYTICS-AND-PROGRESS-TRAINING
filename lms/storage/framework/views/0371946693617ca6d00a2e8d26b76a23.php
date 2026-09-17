<?php $__env->startSection('title', 'New Competency'); ?>
<?php $activeNav = 'competencies'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'New Competency','subtitle' => 'Define a competency within a framework.','icon' => 'fa-compass']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'New Competency','subtitle' => 'Define a competency within a framework.','icon' => 'fa-compass']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.competencies.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-pen"></i> Competency Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('admin.competencies.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="form-grid">
                    <div class="form-field">
                        <label>Framework <span class="required">*</span></label>
                        <select name="framework_id" required>
                            <option value="">— Select framework —</option>
                            <?php $__currentLoopData = $frameworks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $framework): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($framework->id); ?>" <?php if(old('framework_id', $preselectedFrameworkId) == $framework->id): echo 'selected'; endif; ?>><?php echo e($framework->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('framework_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Parent Competency</label>
                        <select name="parent_id">
                            <option value="">— None —</option>
                            <?php $__currentLoopData = $competencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $competency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($competency->id); ?>" <?php if(old('parent_id', $preselectedParentId) == $competency->id): echo 'selected'; endif; ?>><?php echo e($competency->code); ?> — <?php echo e($competency->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('parent_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Code <span class="required">*</span></label>
                        <input type="text" name="code" value="<?php echo e(old('code')); ?>" required placeholder="e.g. COMP-01" maxlength="50">
                        <span class="field-error"><?php echo e($errors->first('code')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Position</label>
                        <input type="number" name="position" min="0" value="<?php echo e(old('position')); ?>">
                        <span class="field-error"><?php echo e($errors->first('position')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" value="<?php echo e(old('name')); ?>" required placeholder="Competency name">
                        <span class="field-error"><?php echo e($errors->first('name')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Competency description"><?php echo e(old('description')); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('description')); ?></span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('admin.competencies.index')); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Create Competency
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\competencies\create.blade.php ENDPATH**/ ?>