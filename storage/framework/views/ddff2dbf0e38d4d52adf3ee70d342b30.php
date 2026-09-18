<?php $__env->startSection('title', 'Create Module'); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Create Module';
    $pageIcon = '<i class="fa-solid fa-layer-plus"></i>';
    $formAction = route('instructor.courses.modules.store', $course);
    $backUrl = route('instructor.courses.modules.index', $course);
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-layer-plus"></i>
            Create Module
        </h2>
        <div class="page-actions">
            <a href="<?php echo e($backUrl); ?>" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Modules
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <style>
        /* Shared create-page design (BCP) */
        .cc-hero {
            position: relative; overflow: hidden;
            display: flex; align-items: center; gap: 16px;
            padding: 20px 26px; margin-bottom: 22px;
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 16px;
            background:
                radial-gradient(circle at 90% 10%, rgba(77,143,240,.3), transparent 42%),
                linear-gradient(135deg, rgba(23,58,168,.85), rgba(10,16,32,.96));
            box-shadow: var(--bcp-shadow, 0 16px 36px rgba(3,8,20,.3));
        }
        .cc-icon {
            width: 50px; height: 50px; flex: none;
            display: grid; place-items: center;
            background: linear-gradient(135deg, #2449c6, #4d8ff0);
            color: #fff; font-size: 21px; border-radius: 13px;
            box-shadow: 0 10px 22px rgba(36,73,198,.35);
        }
        .cc-body { min-width: 0; }
        .cc-kicker {
            display: block; color: var(--bcp-cyan-400, #62c9f5);
            font-size: .64rem; font-weight: 800; letter-spacing: .15em; text-transform: uppercase; margin-bottom: 4px;
        }
        .cc-body h3 { margin: 0; color: #fff; font-size: 1.05rem; font-weight: 800; }
        .cc-body p { margin: 4px 0 0; color: #c8d9f8; font-size: .78rem; }
        .cc-badge {
            margin-left: auto; flex: none;
            padding: 6px 13px; border-radius: 999px;
            background: rgba(98,201,245,.13); border: 1px solid rgba(98,201,245,.28);
            color: #7dd3fc; font-size: .72rem; font-weight: 750;
            display: inline-flex; align-items: center; gap: 6px;
        }

        .cc-card {
            max-width: 960px; margin: 0 auto;
            background: var(--bcp-card, var(--dash-surface, #151c2c));
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            border-radius: 16px; overflow: hidden;
            box-shadow: var(--bcp-shadow, 0 18px 40px rgba(3,8,20,.3));
        }
        .cc-card-head {
            display: flex; align-items: center; gap: 10px;
            padding: 16px 24px;
            border-bottom: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            background: linear-gradient(90deg, rgba(77,143,240,.1), transparent);
        }
        .cc-card-head h3 {
            margin: 0; color: var(--bcp-ink, #eef4ff);
            font-size: .85rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
            display: flex; align-items: center; gap: 9px;
        }
        .cc-card-head h3 i { color: var(--bcp-cyan-400, #62c9f5); font-size: .82rem; }

        .cc-section { padding: 22px 24px; }
        .cc-section + .cc-section { border-top: 1px solid rgba(153,174,214,.1); }
        .cc-section-title {
            display: flex; align-items: center; gap: 8px;
            margin: 0 0 16px; color: var(--bcp-ink, #eef4ff);
            font-size: .78rem; font-weight: 750; letter-spacing: .05em; text-transform: uppercase;
        }
        .cc-section-title i { color: var(--bcp-cyan-400, #62c9f5); }

        .cc-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .cc-field { display: flex; flex-direction: column; gap: 6px; }
        .cc-field.full { grid-column: 1 / -1; }
        .cc-field label { color: var(--bcp-muted, #98a7c4); font-size: .72rem; font-weight: 750; }
        .cc-field label .req { color: #fda4af; }
        .cc-field input, .cc-field select, .cc-field textarea {
            width: 100%; min-height: 40px; padding: 9px 12px;
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 9px;
            background: #101625; color: var(--bcp-ink, #eef4ff);
            font-size: .88rem; transition: border-color .18s, box-shadow .18s;
        }
        .cc-field input:focus, .cc-field select:focus, .cc-field textarea:focus {
            outline: 0; border-color: var(--bcp-blue-500, #4d8ff0); box-shadow: 0 0 0 3px rgba(77,143,240,.15);
        }
        .cc-field textarea { resize: vertical; min-height: 86px; }
        .cc-field input::placeholder, .cc-field textarea::placeholder { color: #7f91b0; }
        .cc-hint { color: var(--bcp-muted, #98a7c4); font-size: .68rem; margin-top: 4px; }

        .cc-char { display: block; text-align: right; color: var(--bcp-muted, #98a7c4); font-size: .66rem; font-weight: 600; margin-top: 4px; }

        .cc-footer {
            display: flex; justify-content: flex-end; gap: 10px;
            padding: 18px 24px;
            border-top: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            background: rgba(77,143,240,.04);
        }
        .cc-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 24px; border-radius: 9px;
            font-size: .84rem; font-weight: 700; text-decoration: none; cursor: pointer;
            transition: transform .15s, box-shadow .15s, background .15s;
        }
        .cc-btn:hover { transform: translateY(-1px); }
        .cc-btn-cancel {
            color: var(--bcp-text, #eef4ff);
            background: var(--dash-surface-raised, #1b2437);
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18));
        }
        .cc-btn-cancel:hover { color: var(--bcp-cyan-400, #62c9f5); background: rgba(77,143,240,.15); }
        .cc-btn-save {
            color: #fff;
            background: linear-gradient(135deg, #2449c6, #173aa8);
            border: 1px solid #3159d1;
            box-shadow: 0 8px 20px rgba(36,73,198,.3);
        }
        .cc-btn-save:hover { background: linear-gradient(135deg, #4d8ff0, #2449c6); }

        @media (max-width: 680px) {
            .cc-grid { grid-template-columns: 1fr; }
            .cc-hero { padding: 16px 18px; }
            .cc-badge { display: none; }
            .cc-footer { flex-direction: column-reverse; }
            .cc-btn { justify-content: center; }
        }
    </style>

    
    <section class="cc-hero">
        <div class="cc-icon"><i class="fa-solid fa-layer-plus"></i></div>
        <div class="cc-body">
            <span class="cc-kicker">Course content</span>
            <h3>New Module — <?php echo e($course->name ?? $course->title); ?></h3>
            <p><?php echo e($course->code); ?> · Organize lessons into a logical learning unit</p>
        </div>
        <span class="cc-badge"><i class="fa-solid fa-layer-group"></i> <?php echo e($course->modules->count()); ?> modules</span>
    </section>

    
    <div class="cc-card">
        <div class="cc-card-head">
            <h3><i class="fa-solid fa-layer-group"></i> Module Information</h3>
        </div>
        <form method="POST" action="<?php echo e($formAction); ?>" id="createForm" data-dirty-warn="true">
            <?php echo csrf_field(); ?>
            <div class="cc-section">
                <div class="cc-grid">
                    <div class="cc-field full">
                        <label>Module Title <span class="req">*</span></label>
                        <input type="text" name="title" required placeholder="e.g. Introduction to Programming" value="<?php echo e(old('title')); ?>" maxlength="255" data-char-count="titleCount">
                        <span class="cc-char" id="titleCount">0 / 255</span>
                        <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="cc-field">
                        <label>Order</label>
                        <input type="number" name="order" min="1" placeholder="e.g. 1" value="<?php echo e(old('order', ($modules->count() ?? 0) + 1)); ?>">
                        <span class="cc-hint">Position sa course sequence</span>
                        <?php $__errorArgs = ['order'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="cc-field">
                        <label>Status</label>
                        <select name="status">
                            <option value="draft" <?php echo e(old('status') == 'draft' ? 'selected' : ''); ?>>Draft</option>
                            <option value="published" <?php echo e(old('status') == 'published' ? 'selected' : ''); ?>>Published</option>
                            <option value="archived" <?php echo e(old('status') == 'archived' ? 'selected' : ''); ?>>Archived</option>
                        </select>
                        <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <div class="cc-section">
                <div class="cc-section-title"><i class="fa-solid fa-align-left"></i> Module Details</div>
                <div class="cc-grid">
                    <div class="cc-field full">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Describe what students will learn in this module..." data-char-count="descCount"><?php echo e(old('description')); ?></textarea>
                        <span class="cc-char" id="descCount">0 characters</span>
                        <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="cc-field full">
                        <label>Objectives / Learning Outcomes</label>
                        <textarea name="objectives" rows="3" placeholder="List the key learning objectives..." data-char-count="objCount"><?php echo e(old('objectives')); ?></textarea>
                        <span class="cc-char" id="objCount">0 characters</span>
                        <?php $__errorArgs = ['objectives'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <div class="cc-footer">
                <a href="<?php echo e($backUrl); ?>" class="cc-btn cc-btn-cancel">
                    <i class="fa-solid fa-xmark"></i>
                    Cancel
                </a>
                <button type="submit" class="cc-btn cc-btn-save">
                    <i class="fa-solid fa-save"></i>
                    Create Module
                </button>
            </div>
        </form>
    </div>

    <script>
        // Char counters
        document.querySelectorAll('[data-char-count]').forEach(el => {
            const counter = document.getElementById(el.dataset.charCount);
            const update = () => {
                if (!counter) return;
                const max = el.maxLength;
                counter.textContent = max ? `${el.value.length.toLocaleString()} / ${max}` : el.value.length.toLocaleString() + ' characters';
            };
            el.addEventListener('input', update);
            update();
        });

        // Dirty form warning
        const form = document.getElementById('createForm');
        let dirty = false;
        form.addEventListener('input', () => { dirty = true; });
        form.addEventListener('change', () => { dirty = true; });
        form.addEventListener('submit', () => { dirty = false; });
        window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\modules\create.blade.php ENDPATH**/ ?>