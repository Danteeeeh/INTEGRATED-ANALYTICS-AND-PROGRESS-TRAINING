<?php $__env->startSection('title', 'New Announcement'); ?>
<?php $activeNav = 'announcements'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'New Announcement','subtitle' => 'Publish an announcement to the institution, a course, a class, or specific roles.','icon' => 'fa-bullhorn']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'New Announcement','subtitle' => 'Publish an announcement to the institution, a course, a class, or specific roles.','icon' => 'fa-bullhorn']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.announcements.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-pen"></i> Announcement Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('admin.announcements.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="<?php echo e(old('title')); ?>" required placeholder="Announcement title">
                        <span class="field-error"><?php echo e($errors->first('title')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Body <span class="required">*</span></label>
                        <textarea name="body" rows="6" required placeholder="Announcement message"><?php echo e(old('body')); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('body')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Audience <span class="required">*</span></label>
                        <select name="audience_type" id="audienceType" required>
                            <?php $__currentLoopData = ['institution' => 'Institution', 'course' => 'Course', 'class' => 'Class', 'role' => 'Role', 'users' => 'Specific Users']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('audience_type') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('audience_type')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            <?php $__currentLoopData = ['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published', 'archived' => 'Archived']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('status', 'draft') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('status')); ?></span>
                    </div>

                    <div class="form-field audience-field" data-audience="course">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">— Select course —</option>
                            <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($course->id); ?>" <?php if(old('course_id') == $course->id): echo 'selected'; endif; ?>><?php echo e($course->code); ?> — <?php echo e($course->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('course_id')); ?></span>
                    </div>

                    <div class="form-field audience-field" data-audience="class">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">— Select class —</option>
                            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" <?php if(old('class_id') == $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?> — <?php echo e($class->course?->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('class_id')); ?></span>
                    </div>

                    <div class="form-field audience-field" data-audience="role">
                        <label>Target Role</label>
                        <select name="target_role_id">
                            <option value="">— Select role —</option>
                            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($role->id); ?>" <?php if(old('target_role_id') == $role->id): echo 'selected'; endif; ?>><?php echo e($role->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('target_role_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Schedule (optional)</label>
                        <input type="datetime-local" name="publish_at" value="<?php echo e(old('publish_at')); ?>">
                        <span class="field-error"><?php echo e($errors->first('publish_at')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_pinned" value="1" <?php if(old('is_pinned')): echo 'checked'; endif; ?>>
                            Pin this announcement
                        </label>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('admin.announcements.index')); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i> Save Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const type = document.getElementById('audienceType');
        if (!type) return;
        const sync = function () {
            document.querySelectorAll('.audience-field').forEach(function (el) {
                el.style.display = el.dataset.audience === type.value ? '' : 'none';
            });
        };
        type.addEventListener('change', sync);
        sync();
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\announcements\create.blade.php ENDPATH**/ ?>