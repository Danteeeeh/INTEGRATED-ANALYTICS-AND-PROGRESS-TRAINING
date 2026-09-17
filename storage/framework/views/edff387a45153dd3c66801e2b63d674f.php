<?php $__env->startSection('title', 'Quizzes'); ?>
<?php $activeNav = 'quizzes'; ?>

<?php $__env->startSection('content'); ?>
<div class="learning-shell">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Quizzes','subtitle' => ''.e($course->title).' — available quizzes','icon' => 'fa-question-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Quizzes','subtitle' => ''.e($course->title).' — available quizzes','icon' => 'fa-question-circle']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('student.courses.show', $course)); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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

    <?php if($quizzes->count() > 0): ?>
        <div class="learning-grid">
            <?php $__currentLoopData = $quizzes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $best = $quiz->attempts->first();
                    $available = $quiz->available();
                    $attempted = $best !== null;
                ?>
                <div class="learning-card">
                    <div>
                        <div class="user-kicker">
                            <?php if($attempted): ?>
                                <span style="color:var(--user-success)"><i class="fa-solid fa-circle-check"></i> Attempted</span>
                            <?php elseif($available): ?>
                                Available now
                            <?php else: ?>
                                Not available
                            <?php endif; ?>
                        </div>
                        <h3><?php echo e($quiz->title); ?></h3>
                        <p><?php echo e(Str::limit($quiz->description ?? '', 80)); ?></p>
                    </div>
                    <div>
                        <div class="user-actions" style="justify-content:space-between">
                            <span class="user-status">
                                <?php if($quiz->time_limit_minutes): ?><?php echo e($quiz->time_limit_minutes); ?> min · <?php endif; ?>
                                <?php if($attempted): ?>Best <?php echo e(number_format($best->score_percent ?? $best->score ?? 0, 1)); ?>%<?php else: ?><?php echo e($quiz->questions->count()); ?> questions@endif
                            </span>
                            <a href="<?php echo e(route('student.courses.quizzes.show', [$course, $quiz])); ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i> Open</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <?php if($quizzes->hasPages()): ?>
            <div class="pagination"><?php echo e($quizzes->links()); ?></div>
        <?php endif; ?>
    <?php else: ?>
        <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-question-circle','title' => 'No quizzes','description' => 'No quizzes have been assigned to this course yet.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-question-circle','title' => 'No quizzes','description' => 'No quizzes have been assigned to this course yet.']); ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\quizzes\index.blade.php ENDPATH**/ ?>