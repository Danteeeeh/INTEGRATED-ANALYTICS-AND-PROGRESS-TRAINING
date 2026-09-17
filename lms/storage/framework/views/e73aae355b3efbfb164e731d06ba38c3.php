<?php $__env->startSection('title', 'Edit Course'); ?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('components.admin-sidebar', ['activeNav' => 'courses'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="page-title-bar">
    <h2 class="page-title">
        <i class="fa-solid fa-book"></i>
        Edit Course
    </h2>
    <div class="page-actions">
        <a href="<?php echo e(route('admin.courses.index')); ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Courses
        </a>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.view')): ?>
            <a href="<?php echo e(route('admin.courses.show', $course)); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-eye"></i>
                View Course
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card course-form-card">
    <div class="card-body">
        <form method="POST" action="<?php echo e(route('admin.courses.update', $course)); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <div class="form-section">
                <h3>Basic Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="code">Course Code <span class="required">*</span></label>
                        <input type="text" id="code" name="code" value="<?php echo e(old('code', $course->code)); ?>" required class="form-control" placeholder="e.g. CS101">
                        <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="error-message"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="form-group">
                        <label for="title">Course Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" value="<?php echo e(old('title', $course->title)); ?>" required class="form-control" placeholder="e.g. Introduction to Computer Science">
                        <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="error-message"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">Select Category</option>
                            <?php $__currentLoopData = $categories ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($category->id); ?>" <?php echo e(old('category_id', $course->category_id) == $category->id ? 'selected' : ''); ?>>
                                    <?php echo e($category->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="error-message"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="form-group">
                        <label for="academic_period_id">Academic Period</label>
                        <select id="academic_period_id" name="academic_period_id" class="form-control">
                            <option value="">Select Period</option>
                            <?php $__currentLoopData = $academicPeriods ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $period): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($period->id); ?>" <?php echo e(old('academic_period_id', $course->academic_period_id) == $period->id ? 'selected' : ''); ?>>
                                    <?php echo e($period->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['academic_period_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="error-message"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="duration_weeks">Duration (Weeks)</label>
                        <input type="number" id="duration_weeks" name="duration_weeks" value="<?php echo e(old('duration_weeks', $course->duration_weeks)); ?>" class="form-control" placeholder="e.g. 12">
                        <?php $__errorArgs = ['duration_weeks'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="error-message"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="form-group">
                        <label for="status">Status <span class="required">*</span></label>
                        <select id="status" name="status" required class="form-control">
                            <option value="draft" <?php echo e(old('status', $course->status) == 'draft' ? 'selected' : ''); ?>>Draft</option>
                            <option value="published" <?php echo e(old('status', $course->status) == 'published' ? 'selected' : ''); ?>>Published</option>
                            <option value="archived" <?php echo e(old('status', $course->status) == 'archived' ? 'selected' : ''); ?>>Archived</option>
                        </select>
                        <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="error-message"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Course Details</h3>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" class="form-control" placeholder="Provide a detailed description of the course..."><?php echo e(old('description', $course->description)); ?></textarea>
                    <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span class="error-message"><?php echo e($message); ?></span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="form-group">
                    <label for="objectives">Learning Objectives</label>
                    <textarea id="objectives" name="objectives" rows="3" class="form-control" placeholder="What will students learn in this course?"><?php echo e(old('objectives', $course->objectives)); ?></textarea>
                    <?php $__errorArgs = ['objectives'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span class="error-message"><?php echo e($message); ?></span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="form-group">
                    <label for="prerequisites">Prerequisites</label>
                    <textarea id="prerequisites" name="prerequisites" rows="2" class="form-control" placeholder="Any required prior knowledge or courses..."><?php echo e(old('prerequisites', $course->prerequisites)); ?></textarea>
                    <?php $__errorArgs = ['prerequisites'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span class="error-message"><?php echo e($message); ?></span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="form-group">
                    <label for="syllabus">Syllabus</label>
                    <textarea id="syllabus" name="syllabus" rows="4" class="form-control" placeholder="Course syllabus and schedule..."><?php echo e(old('syllabus', $course->syllabus)); ?></textarea>
                    <?php $__errorArgs = ['syllabus'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span class="error-message"><?php echo e($message); ?></span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?php echo e(route('admin.courses.index')); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i>
                    Update Course
                </button>
            </div>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\courses\edit.blade.php ENDPATH**/ ?>