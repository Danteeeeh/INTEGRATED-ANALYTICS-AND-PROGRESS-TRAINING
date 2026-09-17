<?php $__env->startSection('title', 'Create Quiz'); ?>

<?php
    $activeNav = 'quizzes';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-circle-question"></i>
            Create Quiz
        </h2>
        <div class="page-actions">
            <a href="<?php echo e(route('admin.quizzes.index')); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Quizzes
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <style>
        .admin-quiz-create {
            max-width: 1040px;
            margin: 0 auto;
        }
        .admin-quiz-create .quiz-form-card {
            overflow: hidden;
            background: var(--dash-surface);
            border: 1px solid var(--dash-line);
            border-radius: 16px;
            box-shadow: var(--bcp-shadow, 0 18px 50px rgba(3, 8, 20, .28));
        }
        .admin-quiz-create .quiz-form-head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 22px;
            border-bottom: 1px solid var(--dash-line);
            background: linear-gradient(90deg, rgba(139, 92, 246, .14), transparent);
        }
        .admin-quiz-create .quiz-form-head-icon {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            flex: none;
            border-radius: 11px;
            color: #fff;
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        }
        .admin-quiz-create .quiz-form-head h3 {
            margin: 0;
            color: var(--dash-text);
            font-size: .95rem;
        }
        .admin-quiz-create .quiz-form-head p {
            margin: 3px 0 0;
            color: var(--dash-muted);
            font-size: .72rem;
        }
        .admin-quiz-create .quiz-form-section {
            padding: 22px;
        }
        .admin-quiz-create .quiz-form-section + .quiz-form-section {
            border-top: 1px solid var(--dash-line);
        }
        .admin-quiz-create .quiz-form-section h4 {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 16px;
            color: var(--dash-text);
            font-size: .78rem;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .admin-quiz-create .quiz-form-section h4 i {
            color: #8b5cf6;
        }
        .admin-quiz-create .quiz-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }
        .admin-quiz-create .quiz-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }
        .admin-quiz-create .quiz-field.full {
            grid-column: 1 / -1;
        }
        .admin-quiz-create .quiz-field label {
            color: var(--dash-muted);
            font-size: .72rem;
            font-weight: 750;
        }
        .admin-quiz-create .quiz-field label .required {
            color: #fb7185;
        }
        .admin-quiz-create .quiz-field input,
        .admin-quiz-create .quiz-field select,
        .admin-quiz-create .quiz-field textarea {
            width: 100%;
            min-height: 38px;
            padding: 8px 10px;
            color: var(--dash-text);
            background: var(--dash-bg);
            border: 1px solid var(--dash-line);
            border-radius: 9px;
            font-size: .8rem;
        }
        .admin-quiz-create .quiz-field textarea {
            min-height: 90px;
            resize: vertical;
        }
        .admin-quiz-create .quiz-field input:focus,
        .admin-quiz-create .quiz-field select:focus,
        .admin-quiz-create .quiz-field textarea:focus {
            outline: 0;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, .16);
        }
        .admin-quiz-create .quiz-field input::placeholder,
        .admin-quiz-create .quiz-field textarea::placeholder {
            color: var(--dash-muted);
            opacity: .75;
        }
        .admin-quiz-create .quiz-hint {
            color: var(--dash-muted);
            font-size: .66rem;
        }
        .admin-quiz-create .quiz-error {
            color: #fb7185;
            font-size: .68rem;
        }
        .admin-quiz-create .quiz-check-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 9px;
        }
        .admin-quiz-create .quiz-check {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            padding: 10px 11px;
            color: var(--dash-muted);
            background: rgba(77, 143, 240, .04);
            border: 1px solid var(--dash-line);
            border-radius: 9px;
            font-size: .72rem;
            line-height: 1.35;
        }
        .admin-quiz-create .quiz-check input {
            width: 16px;
            min-height: 16px;
            margin: 1px 0 0;
            accent-color: #6d28d9;
        }
        .admin-quiz-create .quiz-form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            padding: 17px 22px;
            border-top: 1px solid var(--dash-line);
            background: rgba(77, 143, 240, .04);
        }
        @media (max-width: 680px) {
            .admin-quiz-create .quiz-form-grid,
            .admin-quiz-create .quiz-check-grid {
                grid-template-columns: 1fr;
            }
            .admin-quiz-create .quiz-form-section {
                padding: 18px 15px;
            }
            .admin-quiz-create .quiz-form-footer {
                flex-direction: column-reverse;
                padding: 15px;
            }
            .admin-quiz-create .quiz-form-footer .btn {
                justify-content: center;
                width: 100%;
            }
        }
    </style>

    <div class="admin-quiz-create">
        <form class="quiz-form-card" method="POST" action="<?php echo e(route('admin.quizzes.store')); ?>">
            <?php echo csrf_field(); ?>

            <div class="quiz-form-head">
                <span class="quiz-form-head-icon"><i class="fa-solid fa-circle-question"></i></span>
                <div>
                    <h3>Quiz settings</h3>
                    <p>Configure the assessment before adding questions.</p>
                </div>
            </div>

            <section class="quiz-form-section">
                <h4><i class="fa-solid fa-pen-to-square"></i> Basic information</h4>
                <div class="quiz-form-grid">
                    <div class="quiz-field full">
                        <label for="title">Quiz title <span class="required">*</span></label>
                        <input id="title" type="text" name="title" value="<?php echo e(old('title')); ?>" required maxlength="255" placeholder="e.g. Chapter 1 Quiz">
                        <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="quiz-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="quiz-field">
                        <label for="class_id">Class <span class="required">*</span></label>
                        <select id="class_id" name="class_id" required>
                            <option value="">Select class</option>
                            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" <?php if((string) old('class_id') === (string) $class->id): echo 'selected'; endif; ?>>
                                    <?php echo e($class->code); ?> — <?php echo e($class->course?->title ?? 'Course'); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['class_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="quiz-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="quiz-field">
                        <label for="status">Status <span class="required">*</span></label>
                        <select id="status" name="status" required>
                            <?php $__currentLoopData = ['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('status', 'draft') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="quiz-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="quiz-field full">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Describe what this quiz covers..."><?php echo e(old('description')); ?></textarea>
                        <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="quiz-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="quiz-field full">
                        <label for="instructions">Instructions</label>
                        <textarea id="instructions" name="instructions" placeholder="Instructions for students..."><?php echo e(old('instructions')); ?></textarea>
                        <?php $__errorArgs = ['instructions'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="quiz-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </section>

            <section class="quiz-form-section">
                <h4><i class="fa-solid fa-sliders"></i> Timing and scoring</h4>
                <div class="quiz-form-grid">
                    <div class="quiz-field">
                        <label for="time_limit_minutes">Time limit (minutes)</label>
                        <input id="time_limit_minutes" type="number" name="time_limit_minutes" value="<?php echo e(old('time_limit_minutes', 30)); ?>" min="1">
                        <span class="quiz-hint">Leave blank for no time limit.</span>
                        <?php $__errorArgs = ['time_limit_minutes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="quiz-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="quiz-field">
                        <label for="attempt_limit">Attempt limit</label>
                        <input id="attempt_limit" type="number" name="attempt_limit" value="<?php echo e(old('attempt_limit', 1)); ?>" min="1">
                        <?php $__errorArgs = ['attempt_limit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="quiz-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="quiz-field">
                        <label for="passing_score_percent">Passing score (%)</label>
                        <input id="passing_score_percent" type="number" name="passing_score_percent" value="<?php echo e(old('passing_score_percent', 60)); ?>" min="0" max="100">
                        <?php $__errorArgs = ['passing_score_percent'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="quiz-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="quiz-field">
                        <label for="auto_save_seconds">Auto-save interval (seconds)</label>
                        <input id="auto_save_seconds" type="number" name="auto_save_seconds" value="<?php echo e(old('auto_save_seconds', 30)); ?>" min="0">
                        <?php $__errorArgs = ['auto_save_seconds'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="quiz-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="quiz-field">
                        <label for="result_visibility">Result visibility</label>
                        <select id="result_visibility" name="result_visibility" required>
                            <?php $__currentLoopData = ['always' => 'Always visible', 'after_grading' => 'After grading', 'never' => 'Hidden']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('result_visibility', 'after_grading') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['result_visibility'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="quiz-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="quiz-field">
                        <label for="availability_from">Available from</label>
                        <input id="availability_from" type="datetime-local" name="availability_from" value="<?php echo e(old('availability_from')); ?>">
                        <?php $__errorArgs = ['availability_from'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="quiz-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="quiz-field">
                        <label for="availability_until">Available until</label>
                        <input id="availability_until" type="datetime-local" name="availability_until" value="<?php echo e(old('availability_until')); ?>">
                        <?php $__errorArgs = ['availability_until'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="quiz-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </section>

            <section class="quiz-form-section">
                <h4><i class="fa-solid fa-list-check"></i> Question and review options</h4>
                <div class="quiz-check-grid">
                    <label class="quiz-check"><input type="checkbox" name="shuffle_questions" value="1" <?php if(old('shuffle_questions')): echo 'checked'; endif; ?>> Shuffle questions</label>
                    <label class="quiz-check"><input type="checkbox" name="shuffle_choices" value="1" <?php if(old('shuffle_choices')): echo 'checked'; endif; ?>> Shuffle answer choices</label>
                    <label class="quiz-check"><input type="checkbox" name="allow_navigation" value="1" <?php if(old('allow_navigation', true)): echo 'checked'; endif; ?>> Allow question navigation</label>
                    <label class="quiz-check"><input type="checkbox" name="auto_submit_on_timeout" value="1" <?php if(old('auto_submit_on_timeout', true)): echo 'checked'; endif; ?>> Auto-submit when time expires</label>
                    <label class="quiz-check"><input type="checkbox" name="review_allowed" value="1" <?php if(old('review_allowed')): echo 'checked'; endif; ?>> Allow answer review</label>
                    <label class="quiz-check"><input type="checkbox" name="show_correct_answers" value="1" <?php if(old('show_correct_answers')): echo 'checked'; endif; ?>> Show correct answers</label>
                </div>
            </section>

            <div class="quiz-form-footer">
                <a href="<?php echo e(route('admin.quizzes.index')); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Create quiz</button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\quizzes\create.blade.php ENDPATH**/ ?>