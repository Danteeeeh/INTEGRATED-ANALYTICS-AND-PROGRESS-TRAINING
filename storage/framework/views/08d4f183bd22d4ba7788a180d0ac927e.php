<?php $__env->startSection('title', 'Edit Course'); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Edit Course';
    $pageIcon = '<i class="fa-solid fa-book"></i>';
    $statusMeta = [
        'draft' => ['label' => 'Draft', 'cls' => 'st-draft', 'icon' => 'fa-pen-ruler'],
        'published' => ['label' => 'Published', 'cls' => 'st-published', 'icon' => 'fa-circle-check'],
        'archived' => ['label' => 'Archived', 'cls' => 'st-archived', 'icon' => 'fa-box-archive'],
    ];
    $currentStatus = $statusMeta[$course->status ?? 'draft'] ?? $statusMeta['draft'];
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-book"></i>
            Edit Course
        </h2>
        <div class="page-actions">
            <a href="<?php echo e(route('instructor.courses.index')); ?>" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Courses
            </a>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.view')): ?>
                <a href="<?php echo e(route('instructor.courses.show', $course)); ?>" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; text-decoration: none;">
                    <i class="fa-solid fa-eye"></i>
                    View Course
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="crud-card course-form-card course-edit-shell">
    <div class="course-edit-hero">
        <div class="ceh-icon"><i class="fa-solid fa-book-open"></i></div>
        <div class="ceh-body">
            <span class="ceh-kicker">Editing course</span>
            <h3><?php echo e($course->name ?? $course->title); ?></h3>
            <p><span class="ceh-code"><?php echo e($course->code); ?></span> <?php if($course->credits): ?> · <?php echo e($course->credits); ?> credit<?php echo e($course->credits > 1 ? 's' : ''); ?> <?php endif; ?> <?php if($course->duration_weeks): ?> · <?php echo e($course->duration_weeks); ?> weeks <?php endif; ?></p>
        </div>
        <span class="status-pill <?php echo e($currentStatus['cls']); ?>"><i class="fa-solid <?php echo e($currentStatus['icon']); ?>"></i> <?php echo e($currentStatus['label']); ?></span>
    </div>

    <form method="POST" action="<?php echo e(route('instructor.courses.update', $course)); ?>" id="courseEditForm" data-dirty-warn="true">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <div class="form-section">
            <h3><i class="fa-solid fa-info-circle"></i> Basic Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="code">Course Code <span class="required">*</span></label>
                    <input type="text" id="code" name="code" value="<?php echo e(old('code', $course->code)); ?>" required class="form-control" placeholder="e.g. CS101" maxlength="20">
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
                    <input type="text" id="title" name="title" value="<?php echo e(old('title', $course->title)); ?>" required class="form-control" placeholder="e.g. Introduction to Computer Science" maxlength="255">
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
                    <label for="academic_period_id">Academic Period</label>
                    <select id="academic_period_id" name="academic_period_id" class="form-control">
                        <option value="">Select Period</option>
                        <?php $__currentLoopData = $academicPeriods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $period): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
            <div class="form-row">
                <div class="form-group">
                    <label for="credits">Credits</label>
                    <input type="number" id="credits" name="credits" value="<?php echo e(old('credits', $course->credits)); ?>" min="1" max="10" class="form-control" placeholder="e.g. 3">
                    <?php $__errorArgs = ['credits'];
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
                    <label for="duration_weeks">Duration (Weeks)</label>
                    <input type="number" id="duration_weeks" name="duration_weeks" value="<?php echo e(old('duration_weeks', $course->duration_weeks)); ?>" min="1" max="52" class="form-control" placeholder="e.g. 12">
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
            </div>
            <div class="form-group">
                <label for="thumbnail">Thumbnail URL</label>
                <input type="text" id="thumbnail" name="thumbnail" value="<?php echo e(old('thumbnail', $course->thumbnail)); ?>" class="form-control" placeholder="https://example.com/image.jpg (optional)">
                <?php $__errorArgs = ['thumbnail'];
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

        <div class="form-section">
            <h3><i class="fa-solid fa-align-left"></i> Course Details</h3>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" class="form-control" placeholder="Provide a detailed description of the course..." data-char-count="descCount"><?php echo e(old('description', $course->description)); ?></textarea>
                <span class="char-counter" id="descCount">0 characters</span>
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
                <textarea id="objectives" name="objectives" rows="3" class="form-control" placeholder="What will students learn in this course?" data-char-count="objCount"><?php echo e(old('objectives', $course->objectives)); ?></textarea>
                <span class="char-counter" id="objCount">0 characters</span>
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
                <label for="syllabus">Syllabus</label>
                <textarea id="syllabus" name="syllabus" rows="4" class="form-control" placeholder="Course syllabus and schedule..." data-char-count="sylCount"><?php echo e(old('syllabus', $course->syllabus)); ?></textarea>
                <span class="char-counter" id="sylCount">0 characters</span>
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
            <div class="form-group">
                <label for="prerequisites">Prerequisites</label>
                <textarea id="prerequisites" name="prerequisites" rows="2" class="form-control" placeholder="Any required prior knowledge or courses..." data-char-count="preCount"><?php echo e(old('prerequisites', $course->prerequisites)); ?></textarea>
                <span class="char-counter" id="preCount">0 characters</span>
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
        </div>

        <div class="form-actions course-edit-actions">
            <a href="<?php echo e(route('instructor.courses.index')); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-xmark"></i>
                Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i>
                Update Course
            </button>
        </div>
    </form>
</div>

<script>
    // Char counters
    document.querySelectorAll('[data-char-count]').forEach(area => {
        const counter = document.getElementById(area.dataset.charCount);
        const update = () => { if (counter) counter.textContent = area.value.length.toLocaleString() + ' characters'; };
        area.addEventListener('input', update);
        update();
    });

    // Dirty form warning — prompt before leaving with unsaved changes
    const form = document.getElementById('courseEditForm');
    let dirty = false;
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('change', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', (e) => {
        if (dirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
</script>

<style>
    .course-edit-shell { max-width: 1100px; margin: 0 auto; }

    .course-edit-hero {
        display: flex; align-items: center; gap: 16px;
        padding: 22px 26px;
        background: radial-gradient(circle at 92% 12%, rgba(77,143,240,.28), transparent 45%), linear-gradient(135deg, rgba(23,58,168,.5), rgba(10,16,32,.9));
        border-bottom: 1px solid var(--bcp-line, rgba(153,174,214,.18));
    }
    .ceh-icon {
        width: 52px; height: 52px; flex: none;
        display: grid; place-items: center;
        background: linear-gradient(135deg, #2449c6, #4d8ff0);
        color: #fff; font-size: 22px; border-radius: 14px;
        box-shadow: 0 10px 24px rgba(36,73,198,.35);
    }
    .ceh-body { flex: 1; min-width: 0; }
    .ceh-kicker { display: block; color: var(--bcp-cyan-400, #62c9f5); font-size: .66rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; margin-bottom: 4px; }
    .ceh-body h3 { margin: 0; color: #fff; font-size: 1.15rem; font-weight: 800; letter-spacing: -.01em; }
    .ceh-body p { margin: 5px 0 0; color: #c8d9f8; font-size: .8rem; }
    .ceh-code {
        display: inline-block; padding: 2px 9px; border-radius: 6px;
        background: rgba(98,201,245,.14); color: #7dd3fc;
        font-weight: 750; font-size: .74rem; letter-spacing: .04em;
    }

    .status-pill {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 7px 14px; border-radius: 999px;
        font-size: .74rem; font-weight: 800; letter-spacing: .05em; white-space: nowrap;
    }
    .status-pill.st-draft { background: rgba(251,191,36,.14); color: #fcd34d; border: 1px solid rgba(251,191,36,.3); }
    .status-pill.st-published { background: rgba(52,211,153,.14); color: #6ee7b7; border: 1px solid rgba(52,211,153,.3); }
    .status-pill.st-archived { background: rgba(148,163,184,.14); color: #cbd5e1; border: 1px solid rgba(148,163,184,.3); }

    /* Form sections inherit .admin-ui .form-section styling; add spacing inside card */
    .course-edit-shell .form-section { padding: 20px 26px; }
    .course-edit-shell .form-section h3 {
        display: flex; align-items: center; gap: 9px;
        margin: 0 0 16px; color: var(--bcp-ink, #eef4ff);
        font-size: .84rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase;
    }
    .course-edit-shell .form-section h3 i { color: var(--bcp-cyan-400, #62c9f5); font-size: .8rem; }
    .course-edit-shell .form-group { margin-bottom: 14px; }
    .course-edit-shell .form-group:last-child { margin-bottom: 0; }

    .char-counter {
        display: block; margin-top: 5px;
        color: var(--bcp-muted, #98a7c4); font-size: .68rem; font-weight: 600;
        text-align: right;
    }

    .course-edit-actions { padding: 0 26px 24px; }

    @media (max-width: 640px) {
        .course-edit-hero { flex-wrap: wrap; padding: 18px 20px; }
        .status-pill { order: 3; }
        .course-edit-shell .form-section { padding: 16px 18px; }
        .course-edit-actions { padding: 0 18px 18px; }
    }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/instructor/courses/edit.blade.php ENDPATH**/ ?>