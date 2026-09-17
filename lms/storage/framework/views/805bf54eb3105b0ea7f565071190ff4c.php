<?php $__env->startSection('title', 'Edit Calendar Event'); ?>
<?php $activeNav = 'calendar'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Edit Calendar Event','subtitle' => 'Update event schedule, type, and visibility.','icon' => 'fa-calendar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Edit Calendar Event','subtitle' => 'Update event schedule, type, and visibility.','icon' => 'fa-calendar']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.calendar.show', $calendarEvent)); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-pen"></i> Event Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('admin.calendar.update', $calendarEvent)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="<?php echo e(old('title', $calendarEvent->title)); ?>" required placeholder="Event title">
                        <span class="field-error"><?php echo e($errors->first('title')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Event Type <span class="required">*</span></label>
                        <select name="event_type" required>
                            <?php $__currentLoopData = ['assignment' => 'Assignment', 'quiz' => 'Quiz', 'virtual_class' => 'Virtual Class', 'exam' => 'Exam', 'announcement' => 'Announcement', 'course' => 'Course', 'personal' => 'Personal']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('event_type', $calendarEvent->event_type) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('event_type')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Visibility <span class="required">*</span></label>
                        <select name="visibility" required>
                            <?php $__currentLoopData = ['private' => 'Private', 'course' => 'Course', 'class' => 'Class', 'public' => 'Public']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('visibility', $calendarEvent->visibility) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('visibility')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Starts <span class="required">*</span></label>
                        <input type="datetime-local" name="start_at" value="<?php echo e(old('start_at', $calendarEvent->start_at?->format('Y-m-d\TH:i'))); ?>" required>
                        <span class="field-error"><?php echo e($errors->first('start_at')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Ends <span class="required">*</span></label>
                        <input type="datetime-local" name="end_at" value="<?php echo e(old('end_at', $calendarEvent->end_at?->format('Y-m-d\TH:i'))); ?>" required>
                        <span class="field-error"><?php echo e($errors->first('end_at')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">— Select course —</option>
                            <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($course->id); ?>" <?php if(old('course_id', $calendarEvent->course_id) == $course->id): echo 'selected'; endif; ?>><?php echo e($course->code); ?> — <?php echo e($course->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('course_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">— Select class —</option>
                            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" <?php if(old('class_id', $calendarEvent->class_id) == $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?> — <?php echo e($class->course?->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('class_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Location</label>
                        <input type="text" name="location" value="<?php echo e(old('location', $calendarEvent->location)); ?>" placeholder="Room, link, or venue">
                        <span class="field-error"><?php echo e($errors->first('location')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_all_day" value="1" <?php if(old('is_all_day', $calendarEvent->is_all_day)): echo 'checked'; endif; ?>>
                            All day event
                        </label>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Event description"><?php echo e(old('description', $calendarEvent->description)); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('description')); ?></span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('admin.calendar.show', $calendarEvent)); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\calendar\edit.blade.php ENDPATH**/ ?>