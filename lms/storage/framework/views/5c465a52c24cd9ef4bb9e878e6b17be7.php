<?php $__env->startSection('title', $quiz->title); ?>
<?php $activeNav = 'quizzes'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => ''.e($quiz->title).'','subtitle' => 'Quiz metadata, questions, and attempt summaries.','icon' => 'fa-question-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($quiz->title).'','subtitle' => 'Quiz metadata, questions, and attempt summaries.','icon' => 'fa-question-circle']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($quiz->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($quiz->status).'']); ?>
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
            <span class="user-status"><?php echo e($quiz->questions->count()); ?> questions</span>
            <span class="user-status"><?php echo e($quiz->attempts->count()); ?> attempts</span>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <?php if($quiz->status === 'published'): ?>
                <form method="POST" action="<?php echo e(route('admin.quizzes.close', $quiz)); ?>" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-lock"></i> Close</button>
                </form>
            <?php else: ?>
                <form method="POST" action="<?php echo e(route('admin.quizzes.publish', $quiz)); ?>" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Publish</button>
                </form>
            <?php endif; ?>
            <a href="<?php echo e(route('admin.quizzes.edit', $quiz)); ?>" class="btn btn-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="<?php echo e(route('admin.quizzes.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-info-circle"></i> Quiz Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Class</label>
                    <div><?php echo e($quiz->class?->code ?? '—'); ?> — <?php echo e($quiz->class?->course?->title ?? ''); ?></div>
                </div>
                <div class="form-field">
                    <label>Time Limit</label>
                    <div><?php echo e($quiz->time_limit_minutes ? $quiz->time_limit_minutes.' min' : '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Attempt Limit</label>
                    <div><?php echo e($quiz->attempt_limit ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Passing Score</label>
                    <div><?php echo e($quiz->passing_score_percent ? $quiz->passing_score_percent.'%' : '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Available From</label>
                    <div><?php echo e($quiz->availability_from?->format('M j, Y g:i A') ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Available Until</label>
                    <div><?php echo e($quiz->availability_until?->format('M j, Y g:i A') ?? '—'); ?></div>
                </div>
                <?php if($quiz->description): ?>
                    <div class="form-field full">
                        <label>Description</label>
                        <div><?php echo e($quiz->description); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-list-ol"></i> Questions</h3>
            <span class="user-status"><?php echo e($quiz->questions->count()); ?> questions</span>
        </div>
        <div class="user-panel-body">
            <?php if($quiz->questions->count() > 0): ?>
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Question</th>
                                <th>Type</th>
                                <th>Choices</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $quiz->questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($index + 1); ?></td>
                                    <td><?php echo e(Str::limit($question->question_text ?? $question->text ?? 'Question', 70)); ?></td>
                                    <td><span class="user-status"><?php echo e(ucfirst(str_replace('_', ' ', $question->question_type ?? 'choice'))); ?></span></td>
                                    <td><?php echo e($question->choices->count()); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-list-ol','title' => 'No questions yet','description' => 'Add questions to this quiz before publishing.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-list-ol','title' => 'No questions yet','description' => 'Add questions to this quiz before publishing.']); ?>
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

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-user-clock"></i> Attempts</h3>
            <span class="user-status"><?php echo e($quiz->attempts->count()); ?> attempts</span>
        </div>
        <div class="user-panel-body">
            <?php if($quiz->attempts->count() > 0): ?>
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $quiz->attempts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attempt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($attempt->student?->name ?? '—'); ?></td>
                                    <td><?php echo e(round($attempt->score_percent ?? $attempt->score ?? 0, 1)); ?>%</td>
                                    <td><?php echo e($attempt->created_at?->format('M j, Y g:i A')); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-user-clock','title' => 'No attempts yet','description' => 'Student attempts will appear here once the quiz is taken.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-user-clock','title' => 'No attempts yet','description' => 'Student attempts will appear here once the quiz is taken.']); ?>
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
        <form action="<?php echo e(route('admin.quizzes.destroy', $quiz)); ?>" method="POST" onsubmit="return confirm('Delete this quiz?')">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\quizzes\show.blade.php ENDPATH**/ ?>