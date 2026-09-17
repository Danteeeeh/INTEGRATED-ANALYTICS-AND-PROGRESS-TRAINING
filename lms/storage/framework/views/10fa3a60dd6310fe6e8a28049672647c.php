<?php $__env->startSection('title', 'Submission Review'); ?>
<?php $activeNav = 'assignments'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Submission Review','subtitle' => ''.e($assignment->title).' — '.e($submission->student?->full_name ?? $submission->student?->name ?? 'Student').'','icon' => 'fa-tasks']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Submission Review','subtitle' => ''.e($assignment->title).' — '.e($submission->student?->full_name ?? $submission->student?->name ?? 'Student').'','icon' => 'fa-tasks']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($submission->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($submission->status).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $attributes = $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $component = $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('instructor.courses.assignments.submissions', [$course, $assignment])); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
        <div class="user-panel-head"><h3><i class="fa-solid fa-user"></i> Student</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Student</label>
                    <div><?php echo e($submission->student?->full_name ?? $submission->student?->name ?? '—'); ?></div>
                    <div class="user-email"><?php echo e($submission->student?->email); ?></div>
                </div>
                <div class="form-field">
                    <label>Submitted</label>
                    <div><?php echo e($submission->submitted_at?->format('M j, Y g:i A') ?? $submission->created_at?->format('M j, Y g:i A')); ?></div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($submission->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($submission->status).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $attributes = $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $component = $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-comment"></i> Submission Content</h3></div>
        <div class="user-panel-body">
            <?php if($submission->content): ?>
                <div style="white-space:pre-line"><?php echo e($submission->content); ?></div>
            <?php elseif($submission->text): ?>
                <div style="white-space:pre-line"><?php echo e($submission->text); ?></div>
            <?php else: ?>
                <p class="user-email">No text content provided.</p>
            <?php endif; ?>

            <?php if($submission->files && $submission->files->count() > 0): ?>
                <div class="form-section">
                    <div class="modal-section-title"><i class="fa-solid fa-paperclip"></i> Attached Files</div>
                    <div class="user-actions" style="justify-content:flex-start">
                        <?php $__currentLoopData = $submission->files; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e($file->url ?? $file->path ?? '#'); ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-file"></i> <?php echo e($file->original_name ?? $file->name ?? 'File'); ?>

                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-graduation-cap"></i> Grade Submission</h3></div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('instructor.courses.assignments.submissions.grade', [$course, $assignment, $submission])); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="form-grid">
                    <div class="form-field">
                        <label>Points (max <?php echo e($assignment->points); ?>) <span class="required">*</span></label>
                        <input type="number" name="points" min="0" max="<?php echo e($assignment->points); ?>" step="0.1" required
                               value="<?php echo e(old('points', $submission->grade?->points)); ?>">
                        <span class="field-error"><?php echo e($errors->first('points')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Feedback</label>
                        <textarea name="feedback" rows="4" placeholder="Feedback for the student"><?php echo e(old('feedback', $submission->grade?->feedback)); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('feedback')); ?></span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Grade</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\assignments\submission.blade.php ENDPATH**/ ?>