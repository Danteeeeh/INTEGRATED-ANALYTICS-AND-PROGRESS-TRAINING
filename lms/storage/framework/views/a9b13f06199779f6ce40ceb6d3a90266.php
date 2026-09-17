<?php $__env->startSection('title', 'Submit Assignment'); ?>
<?php $activeNav = 'assignments'; ?>

<?php $__env->startSection('content'); ?>
<div class="learning-shell">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Submit Assignment','subtitle' => ''.e($assignment->title).' — '.e($course->title).'','icon' => 'fa-paper-plane']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Submit Assignment','subtitle' => ''.e($assignment->title).' — '.e($course->title).'','icon' => 'fa-paper-plane']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('student.courses.assignments.show', [$course, $assignment])); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
        <div class="user-panel-head"><h3><i class="fa-solid fa-upload"></i> Your Submission</h3></div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('student.courses.assignments.submit.store', [$course, $assignment])); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Your Answer <span class="required">*</span></label>
                        <textarea name="content" rows="8" required placeholder="Write your submission here..."><?php echo e(old('content', $existingSubmission->content ?? '')); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('content')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Attach Files</label>
                        <input type="file" name="files[]" multiple>
                        <span class="field-error"><?php echo e($errors->first('files')); ?></span>
                        <small class="user-email">You may attach one or more files.</small>
                    </div>
                </div>

                <?php if($existingSubmission): ?>
                    <div class="user-toolbar" style="margin-top:12px">
                        <span class="user-status pending">Existing submission</span>
                        <span class="user-email">Submitted <?php echo e($existingSubmission->created_at?->format('M j, Y g:i A')); ?></span>
                    </div>
                <?php endif; ?>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('student.courses.assignments.show', [$course, $assignment])); ?>" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <?php if($assignment->due_date): ?>
        <div class="learning-next-action">
            <div>
                <strong>Due <?php echo e($assignment->due_date->format('l, F j, Y g:i A')); ?></strong>
                <span><?php echo e($assignment->due_date->isPast() ? 'This assignment is overdue — late submission may be penalized.' : 'Submit before the deadline to avoid penalties.'); ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\assignments\submit.blade.php ENDPATH**/ ?>