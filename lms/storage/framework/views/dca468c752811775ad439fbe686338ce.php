<?php $__env->startSection('title', 'Notification Preferences'); ?>
<?php $activeNav = 'notifications'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Notification Preferences','subtitle' => 'Configure which channels and events notify each user.','icon' => 'fa-sliders']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Notification Preferences','subtitle' => 'Configure which channels and events notify each user.','icon' => 'fa-sliders']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <span class="user-status active"><?php echo e($user->name); ?></span>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.notifications.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
        <form class="user-toolbar" method="GET" action="<?php echo e(route('admin.notifications.preferences')); ?>">
            <select class="form-control" name="user_id" aria-label="Select user">
                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($option->id); ?>" <?php if($option->id === $user->id): echo 'selected'; endif; ?>><?php echo e($option->name); ?> (<?php echo e($option->email); ?>)</option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-user"></i> Load Preferences</button>
        </form>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-bell"></i> Delivery Channels</h3>
        </div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('admin.notifications.preferences.update')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <input type="hidden" name="user_id" value="<?php echo e($user->id); ?>">

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Notification channels for <?php echo e($user->name); ?></label>
                        <div class="checkbox-grid">
                            <?php $__currentLoopData = [
                                'email_notifications' => 'Email notifications',
                                'push_notifications' => 'Push notifications',
                                'sms_notifications' => 'SMS notifications',
                                'daily_digest' => 'Daily digest',
                                'weekly_summary' => 'Weekly summary',
                            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="<?php echo e($key); ?>" value="1" <?php if($preferences[$key] ?? false): echo 'checked'; endif; ?>>
                                    <?php echo e($label); ?>

                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>

                    <div class="form-field full">
                        <label>Events to notify about</label>
                        <div class="checkbox-grid">
                            <?php $__currentLoopData = [
                                'notify_on_assignment' => 'Assignments',
                                'notify_on_quiz' => 'Quizzes',
                                'notify_on_grade' => 'Grades',
                                'notify_on_announcement' => 'Announcements',
                                'notify_on_discussion' => 'Discussions',
                                'notify_on_message' => 'Messages',
                                'notify_on_attendance' => 'Attendance',
                            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="<?php echo e($key); ?>" value="1" <?php if($preferences[$key] ?? false): echo 'checked'; endif; ?>>
                                    <?php echo e($label); ?>

                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>

                    <div class="form-field">
                        <label>Quiet hours start</label>
                        <input type="time" name="quiet_hours_start" value="<?php echo e($preferences['quiet_hours_start'] ?? ''); ?>">
                    </div>

                    <div class="form-field">
                        <label>Quiet hours end</label>
                        <input type="time" name="quiet_hours_end" value="<?php echo e($preferences['quiet_hours_end'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('admin.notifications.index')); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Save Preferences
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\notifications\preferences.blade.php ENDPATH**/ ?>