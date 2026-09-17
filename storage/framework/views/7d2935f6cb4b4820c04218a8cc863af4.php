<?php $__env->startSection('title', 'Edit Quiz'); ?>
<?php $activeNav = 'quizzes'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Edit Quiz','subtitle' => ''.e($course->title).'','icon' => 'fa-question-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Edit Quiz','subtitle' => ''.e($course->title).'','icon' => 'fa-question-circle']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('instructor.courses.quizzes.show', [$course, $quiz])); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
        <div class="user-panel-head"><h3><i class="fa-solid fa-pen"></i> Quiz Settings</h3></div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('instructor.courses.quizzes.update', [$course, $quiz])); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="<?php echo e(old('title', $quiz->title)); ?>" required>
                        <span class="field-error"><?php echo e($errors->first('title')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Class</label>
                        <select name="class_id">
                            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" <?php if(old('class_id', $quiz->class_id) == $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('class_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            <?php $__currentLoopData = ['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('status', $quiz->status) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('status')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Time Limit (minutes)</label>
                        <input type="number" name="time_limit_minutes" min="1" value="<?php echo e(old('time_limit_minutes', $quiz->time_limit_minutes)); ?>">
                        <span class="field-error"><?php echo e($errors->first('time_limit_minutes')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Attempt Limit</label>
                        <input type="number" name="attempt_limit" min="1" value="<?php echo e(old('attempt_limit', $quiz->attempt_limit)); ?>">
                        <span class="field-error"><?php echo e($errors->first('attempt_limit')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Passing Score (%)</label>
                        <input type="number" name="passing_score_percent" min="0" max="100" value="<?php echo e(old('passing_score_percent', $quiz->passing_score_percent)); ?>">
                        <span class="field-error"><?php echo e($errors->first('passing_score_percent')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Result Visibility</label>
                        <select name="result_visibility">
                            <?php $__currentLoopData = $resultVisibilityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('result_visibility', $quiz->result_visibility) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('result_visibility')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Available From</label>
                        <input type="datetime-local" name="availability_from" value="<?php echo e(old('availability_from', $quiz->availability_from?->format('Y-m-d\TH:i'))); ?>">
                        <span class="field-error"><?php echo e($errors->first('availability_from')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Available Until</label>
                        <input type="datetime-local" name="availability_until" value="<?php echo e(old('availability_until', $quiz->availability_until?->format('Y-m-d\TH:i'))); ?>">
                        <span class="field-error"><?php echo e($errors->first('availability_until')); ?></span>
                    </div>

                    <div class="form-field full">
                        <div class="checkbox-grid">
                            <label class="checkbox-label">
                                <input type="checkbox" name="shuffle_questions" value="1" <?php if(old('shuffle_questions', $quiz->shuffle_questions)): echo 'checked'; endif; ?>> Shuffle questions
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="shuffle_choices" value="1" <?php if(old('shuffle_choices', $quiz->shuffle_choices)): echo 'checked'; endif; ?>> Shuffle choices
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="review_allowed" value="1" <?php if(old('review_allowed', $quiz->review_allowed)): echo 'checked'; endif; ?>> Allow review
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_correct_answers" value="1" <?php if(old('show_correct_answers', $quiz->show_correct_answers)): echo 'checked'; endif; ?>> Show correct answers
                            </label>
                        </div>
                    </div>

                    <div class="form-field full">
                        <label>Instructions</label>
                        <textarea name="instructions" rows="4"><?php echo e(old('instructions', $quiz->instructions)); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('instructions')); ?></span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('instructor.courses.quizzes.show', [$course, $quiz])); ?>" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update Quiz</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\quizzes\edit.blade.php ENDPATH**/ ?>