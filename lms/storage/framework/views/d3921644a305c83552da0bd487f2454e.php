<?php $__env->startSection('title', 'New Question'); ?>
<?php $activeNav = 'questions'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'New Question','subtitle' => 'Create a question with type, difficulty, and bank context.','icon' => 'fa-circle-question']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'New Question','subtitle' => 'Create a question with type, difficulty, and bank context.','icon' => 'fa-circle-question']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.questions.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-pen"></i> Question Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('admin.questions.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="form-grid">
                    <div class="form-field">
                        <label>Question Bank <span class="required">*</span></label>
                        <select name="question_bank_id" required>
                            <option value="">— Select bank —</option>
                            <?php $__currentLoopData = $questionBanks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bank): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($bank->id); ?>" <?php if(old('question_bank_id', $preselectedBankId) == $bank->id): echo 'selected'; endif; ?>><?php echo e($bank->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('question_bank_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Type <span class="required">*</span></label>
                        <select name="question_type" required>
                            <?php $__currentLoopData = ['multiple_choice' => 'Multiple Choice', 'multiple_answer' => 'Multiple Answer', 'true_false' => 'True/False', 'identification' => 'Identification', 'short_answer' => 'Short Answer', 'essay' => 'Essay']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('question_type', 'multiple_choice') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('question_type')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Difficulty <span class="required">*</span></label>
                        <select name="difficulty" required>
                            <?php $__currentLoopData = ['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('difficulty', 'medium') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('difficulty')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Default Points</label>
                        <input type="number" name="default_points" min="0" step="0.5" value="<?php echo e(old('default_points', 1)); ?>">
                        <span class="field-error"><?php echo e($errors->first('default_points')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <input type="text" name="status" value="<?php echo e(old('status', 'active')); ?>" maxlength="50">
                        <span class="field-error"><?php echo e($errors->first('status')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Question Text <span class="required">*</span></label>
                        <textarea name="question_text" rows="4" required placeholder="Enter the question"><?php echo e(old('question_text')); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('question_text')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Explanation (shown after answering)</label>
                        <textarea name="explanation" rows="3" placeholder="Optional explanation"><?php echo e(old('explanation')); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('explanation')); ?></span>
                    </div>

                    <div class="form-field full">
                        <label>Tags (comma-separated)</label>
                        <input type="text" name="tags" value="<?php echo e(is_array(old('tags')) ? implode(', ', old('tags')) : old('tags')); ?>" placeholder="e.g. biology, midterm">
                        <span class="field-error"><?php echo e($errors->first('tags')); ?></span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('admin.questions.index')); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Create Question
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\questions\create.blade.php ENDPATH**/ ?>