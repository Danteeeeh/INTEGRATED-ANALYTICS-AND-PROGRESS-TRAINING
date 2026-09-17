<?php $__env->startSection('title', 'Edit Announcement'); ?>
<?php $activeNav = 'announcements'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Edit Announcement','subtitle' => ''.e($course->title).'','icon' => 'fa-bullhorn']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Edit Announcement','subtitle' => ''.e($course->title).'','icon' => 'fa-bullhorn']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('instructor.courses.announcements.show', [$course, $announcement])); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
        <div class="user-panel-head"><h3><i class="fa-solid fa-pen"></i> Announcement Details</h3></div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('instructor.courses.announcements.update', [$course, $announcement])); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="<?php echo e(old('title', $announcement->title)); ?>" required>
                        <span class="field-error"><?php echo e($errors->first('title')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Body <span class="required">*</span></label>
                        <textarea name="body" rows="6" required><?php echo e(old('body', $announcement->body)); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('body')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Audience</label>
                        <select name="audience_type">
                            <?php $__currentLoopData = $audienceTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('audience_type', $announcement->audience_type) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('audience_type')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">— None —</option>
                            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" <?php if(old('class_id', $announcement->class_id) == $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('class_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            <?php $__currentLoopData = ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('status', $announcement->status) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('status')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_pinned" value="1" <?php if(old('is_pinned', $announcement->is_pinned)): echo 'checked'; endif; ?>>
                            Pin this announcement
                        </label>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('instructor.courses.announcements.show', [$course, $announcement])); ?>" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\announcements\edit.blade.php ENDPATH**/ ?>