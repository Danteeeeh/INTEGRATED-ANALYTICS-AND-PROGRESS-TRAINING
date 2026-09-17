<?php $__env->startSection('title', 'New Lesson'); ?>
<?php $activeNav = 'modules'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'New Lesson','subtitle' => 'Add a lesson to '.e($module->title).'.','icon' => 'fa-list-check']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'New Lesson','subtitle' => 'Add a lesson to '.e($module->title).'.','icon' => 'fa-list-check']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.courses.modules.lessons.index', [$module->course_id, $module])); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-pen"></i> Lesson Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('admin.courses.modules.lessons.store', [$module->course_id, $module])); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="module_id" value="<?php echo e($module->id); ?>">

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="<?php echo e(old('title')); ?>" required placeholder="Lesson title">
                        <span class="field-error"><?php echo e($errors->first('title')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Module</label>
                        <select name="module_id" disabled>
                            <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($m->id); ?>" <?php if($m->id === $module->id): echo 'selected'; endif; ?>><?php echo e($m->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Lesson Type <span class="required">*</span></label>
                        <select name="lesson_type" required>
                            <?php $__currentLoopData = ['text' => 'Text', 'video' => 'Video', 'audio' => 'Audio', 'pdf' => 'PDF', 'document' => 'Document', 'presentation' => 'Presentation', 'external' => 'External Link']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('lesson_type', 'text') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('lesson_type')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            <?php $__currentLoopData = ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('status', 'draft') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('status')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration_minutes" min="0" value="<?php echo e(old('duration_minutes')); ?>">
                        <span class="field-error"><?php echo e($errors->first('duration_minutes')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_required" value="1" <?php if(old('is_required')): echo 'checked'; endif; ?>>
                            Required lesson
                        </label>
                    </div>

                    <div class="form-field">
                        <label>Available from</label>
                        <input type="datetime-local" name="availability_from" value="<?php echo e(old('availability_from')); ?>">
                        <span class="field-error"><?php echo e($errors->first('availability_from')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Available until</label>
                        <input type="datetime-local" name="availability_until" value="<?php echo e(old('availability_until')); ?>">
                        <span class="field-error"><?php echo e($errors->first('availability_until')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>External URL (for external lessons)</label>
                        <input type="url" name="external_url" value="<?php echo e(old('external_url')); ?>" placeholder="https://...">
                        <span class="field-error"><?php echo e($errors->first('external_url')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Short description"><?php echo e(old('description')); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('description')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Objectives</label>
                        <textarea name="objectives" rows="3" placeholder="Learning objectives"><?php echo e(old('objectives')); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('objectives')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Content</label>
                        <textarea name="content" rows="8" placeholder="Lesson content (text, embedded media, or instructions)"><?php echo e(old('content')); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('content')); ?></span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('admin.courses.modules.lessons.index', [$module->course_id, $module])); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Create Lesson
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\lessons\create.blade.php ENDPATH**/ ?>