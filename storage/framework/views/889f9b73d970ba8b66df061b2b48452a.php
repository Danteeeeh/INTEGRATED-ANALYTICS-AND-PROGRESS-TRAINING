<?php $__env->startSection('title', $assignment->title); ?>
<?php $activeNav = 'assignments'; ?>

<?php $__env->startSection('content'); ?>
<div class="learning-shell">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => ''.e($assignment->title).'','subtitle' => ''.e($course->title).' — assignment details','icon' => 'fa-tasks']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($assignment->title).'','subtitle' => ''.e($course->title).' — assignment details','icon' => 'fa-tasks']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <?php if($assignment->due_date): ?>
                <span class="user-status"><?php echo e($assignment->due_date->isPast() ? 'Overdue' : 'Open'); ?></span>
                <span>Due <?php echo e($assignment->due_date->format('l, F j, Y g:i A')); ?></span>
            <?php endif; ?>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <?php if(!$mySubmission || in_array($mySubmission->status, ['draft', 'returned'], true)): ?>
                <a href="<?php echo e(route('student.courses.assignments.submit', [$course, $assignment])); ?>" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> <?php echo e($mySubmission ? 'Resubmit' : 'Submit'); ?></a>
            <?php endif; ?>
            <a href="<?php echo e(route('student.courses.assignments.index', $course)); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
        <div class="user-panel-head"><h3><i class="fa-solid fa-info-circle"></i> Assignment</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Points</label>
                    <div><?php echo e($assignment->points); ?></div>
                </div>
                <div class="form-field">
                    <label>Due Date</label>
                    <div><?php echo e($assignment->due_date?->format('M j, Y g:i A') ?? 'No due date'); ?></div>
                </div>
                <?php if($assignment->description): ?>
                    <div class="form-field full">
                        <label>Description</label>
                        <div style="white-space:pre-line"><?php echo e($assignment->description); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($assignment->attachments && $assignment->attachments->count() > 0): ?>
                <div class="form-section">
                    <div class="modal-section-title"><i class="fa-solid fa-paperclip"></i> Attachments</div>
                    <div class="user-actions" style="justify-content:flex-start">
                        <?php $__currentLoopData = $assignment->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e($file->url ?? '#'); ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-file"></i> <?php echo e($file->original_name ?? $file->name ?? 'File'); ?>

                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-file-circle-check"></i> My Submission</h3></div>
        <div class="user-panel-body">
            <?php if($mySubmission): ?>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Status</label>
                        <div><?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($mySubmission->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($mySubmission->status).'']); ?>
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
                    <div class="form-field">
                        <label>Submitted</label>
                        <div><?php echo e($mySubmission->submitted_at?->format('M j, Y g:i A') ?? $mySubmission->created_at?->format('M j, Y g:i A')); ?></div>
                    </div>
                    <?php if($mySubmission->grade): ?>
                        <div class="form-field">
                            <label>Score</label>
                            <div><strong><?php echo e(number_format($mySubmission->grade->score_percent ?? 0, 1)); ?>%</strong> (<?php echo e($mySubmission->grade->points ?? 0); ?>/<?php echo e($assignment->points); ?>)</div>
                        </div>
                    <?php endif; ?>
                    <?php if($mySubmission->grade?->feedback): ?>
                        <div class="form-field full">
                            <label>Feedback</label>
                            <div style="white-space:pre-line"><?php echo e($mySubmission->grade->feedback); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="user-actions" style="justify-content:flex-start;margin-top:12px">
                    <a href="<?php echo e(route('student.courses.assignments.submissions.show', [$course, $assignment, $mySubmission])); ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i> View Submission</a>
                </div>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-file-circle-check','title' => 'Not submitted yet','description' => 'Submit your work before the due date.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-file-circle-check','title' => 'Not submitted yet','description' => 'Submit your work before the due date.']); ?>
                     <?php $__env->slot('action', null, []); ?> 
                        <a href="<?php echo e(route('student.courses.assignments.submit', [$course, $assignment])); ?>" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Now</a>
                     <?php $__env->endSlot(); ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb3aac3110999f7435f4950a3fe8df251)): ?>
<?php $attributes = $__attributesOriginalb3aac3110999f7435f4950a3fe8df251; ?>
<?php unset($__attributesOriginalb3aac3110999f7435f4950a3fe8df251); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb3aac3110999f7435f4950a3fe8df251)): ?>
<?php $component = $__componentOriginalb3aac3110999f7435f4950a3fe8df251; ?>
<?php unset($__componentOriginalb3aac3110999f7435f4950a3fe8df251); ?>
<?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if(!$mySubmission || in_array($mySubmission->status, ['draft', 'returned'], true)): ?>
        <div class="learning-next-action">
            <div>
                <strong>Ready to submit?</strong>
                <span>Make sure you have completed all parts of the assignment.</span>
            </div>
            <a href="<?php echo e(route('student.courses.assignments.submit', [$course, $assignment])); ?>" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Go to Submission</a>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\assignments\show.blade.php ENDPATH**/ ?>