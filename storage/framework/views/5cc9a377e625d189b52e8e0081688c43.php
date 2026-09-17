<?php $__env->startSection('title', 'Attempt Results'); ?>
<?php $activeNav = 'quizzes'; ?>

<?php $__env->startSection('content'); ?>
<div class="learning-shell">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Attempt Results','subtitle' => ''.e($quiz->title).' — attempt #'.e($attempt->attempt_number).'','icon' => 'fa-clipboard-check']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Attempt Results','subtitle' => ''.e($quiz->title).' — attempt #'.e($attempt->attempt_number).'','icon' => 'fa-clipboard-check']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($attempt->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($attempt->status).'']); ?>
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
            <span class="user-status"><?php echo e(number_format($attempt->score_percent ?? $attempt->score ?? 0, 1)); ?>%</span>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('student.courses.quizzes.show', [$course, $quiz])); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
        <div class="user-panel-head"><h3><i class="fa-solid fa-graduation-cap"></i> Result Summary</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Score</label>
                    <div><strong><?php echo e(number_format($attempt->score_percent ?? $attempt->score ?? 0, 1)); ?>%</strong></div>
                </div>
                <div class="form-field">
                    <label>Completed</label>
                    <div><?php echo e($attempt->completed_at?->format('M j, Y g:i A') ?? $attempt->created_at?->format('M j, Y g:i A')); ?></div>
                </div>
                <?php if($quiz->passing_score_percent): ?>
                    <div class="form-field">
                        <label>Passing Score</label>
                        <div>
                            <?php echo e($quiz->passing_score_percent); ?>%
                            <?php if(($attempt->score_percent ?? 0) >= $quiz->passing_score_percent): ?>
                                <span class="user-status active">Passed</span>
                            <?php else: ?>
                                <span class="user-status">Not passed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-list-check"></i> Review Answers</h3></div>
        <div class="user-panel-body">
            <?php if($attempt->answers && $attempt->answers->count() > 0): ?>
                <?php $__currentLoopData = $attempt->answers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $answer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="user-toolbar" style="flex-direction:column;align-items:stretch;margin-bottom:12px">
                        <strong>Q<?php echo e($index + 1); ?>: <?php echo e($answer->question?->question_text ?? $answer->question?->text ?? 'Question'); ?></strong>
                        <div class="user-email" style="margin-top:6px">
                            Your answer:
                            <?php if($answer->choice): ?>
                                <?php echo e($answer->choice->choice_text ?? $answer->choice->text ?? 'Choice'); ?>

                            <?php elseif($answer->answer_text): ?>
                                <?php echo e($answer->answer_text); ?>

                            <?php else: ?>
                                —
                            <?php endif; ?>
                            <?php if($answer->is_correct === true): ?>
                                <span class="user-status active"><i class="fa-solid fa-check"></i> Correct</span>
                            <?php elseif($answer->is_correct === false): ?>
                                <span class="user-status"><i class="fa-solid fa-xmark"></i> Incorrect</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-list-check','title' => 'No answers recorded','description' => 'No answers were recorded for this attempt.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-list-check','title' => 'No answers recorded','description' => 'No answers were recorded for this attempt.']); ?>
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

    <div class="user-actions">
        <?php if($quiz->available()): ?>
            <a href="<?php echo e(route('student.courses.quizzes.attempt.start', [$course, $quiz])); ?>" class="btn btn-primary"><i class="fa-solid fa-rotate-right"></i> Try Again</a>
        <?php endif; ?>
        <a href="<?php echo e(route('student.courses.quizzes.index', $course)); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> All Quizzes</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\quizzes\attempt_show.blade.php ENDPATH**/ ?>