<?php $__env->startSection('title', 'Create Academic Period'); ?>

<?php
    $activeNav = 'academic_periods';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-calendar-days"></i>
            Create Academic Period
        </h2>
        <div class="page-actions">
            <a href="<?php echo e(route('admin.academic_periods.index')); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Back to periods
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <style>
        .academic-period-create {
            max-width: 980px;
            margin: 0 auto;
        }
        .academic-period-create .period-hero {
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
            overflow: hidden;
            margin-bottom: 18px;
            padding: 20px 22px;
            border: 1px solid var(--dash-line);
            border-radius: 16px;
            background:
                radial-gradient(circle at 90% 10%, rgba(34, 211, 238, .24), transparent 40%),
                linear-gradient(135deg, rgba(23, 58, 168, .92), rgba(10, 16, 32, .97));
            box-shadow: var(--bcp-shadow, 0 18px 50px rgba(3, 8, 20, .28));
        }
        .academic-period-create .period-hero-icon {
            display: grid;
            place-items: center;
            width: 48px;
            height: 48px;
            flex: none;
            border-radius: 13px;
            color: #fff;
            background: linear-gradient(135deg, #22d3ee, #0e7490);
            box-shadow: 0 10px 24px rgba(34, 211, 238, .26);
        }
        .academic-period-create .period-hero h3 {
            margin: 0;
            color: #fff;
            font-size: 1.02rem;
        }
        .academic-period-create .period-hero p {
            margin: 4px 0 0;
            color: #c8d9f8;
            font-size: .76rem;
        }
        .academic-period-create .period-hero-badge {
            margin-left: auto;
            padding: 6px 11px;
            border: 1px solid rgba(98, 201, 245, .28);
            border-radius: 999px;
            color: #cceeff;
            background: rgba(98, 201, 245, .1);
            font-size: .68rem;
            font-weight: 750;
            white-space: nowrap;
        }
        .academic-period-create .period-card {
            overflow: hidden;
            background: var(--dash-surface);
            border: 1px solid var(--dash-line);
            border-radius: 16px;
            box-shadow: var(--bcp-shadow, 0 18px 50px rgba(3, 8, 20, .28));
        }
        .academic-period-create .period-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 17px 22px;
            border-bottom: 1px solid var(--dash-line);
            background: linear-gradient(90deg, rgba(77, 143, 240, .1), transparent);
        }
        .academic-period-create .period-card-head h3 {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            color: var(--dash-text);
            font-size: .84rem;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .academic-period-create .period-card-head h3 i {
            color: var(--dash-cyan);
        }
        .academic-period-create .period-card-head span {
            color: var(--dash-muted);
            font-size: .68rem;
        }
        .academic-period-create .period-section {
            padding: 22px;
        }
        .academic-period-create .period-section + .period-section {
            border-top: 1px solid var(--dash-line);
        }
        .academic-period-create .period-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 16px;
            color: var(--dash-text);
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .academic-period-create .period-section-title i {
            color: var(--dash-cyan);
        }
        .academic-period-create .period-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }
        .academic-period-create .period-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }
        .academic-period-create .period-field.full {
            grid-column: 1 / -1;
        }
        .academic-period-create .period-field label {
            color: var(--dash-muted);
            font-size: .72rem;
            font-weight: 750;
        }
        .academic-period-create .period-field label .required {
            color: #fb7185;
        }
        .academic-period-create .period-field input,
        .academic-period-create .period-field textarea {
            width: 100%;
            min-height: 39px;
            padding: 9px 11px;
            color: var(--dash-text);
            background: var(--dash-bg);
            border: 1px solid var(--dash-line);
            border-radius: 9px;
            font-size: .82rem;
        }
        .academic-period-create .period-field textarea {
            min-height: 92px;
            resize: vertical;
        }
        .academic-period-create .period-field input:focus,
        .academic-period-create .period-field textarea:focus {
            outline: 0;
            border-color: var(--dash-blue-light);
            box-shadow: 0 0 0 3px rgba(77, 143, 240, .16);
        }
        .academic-period-create .period-field input::placeholder,
        .academic-period-create .period-field textarea::placeholder {
            color: var(--dash-muted);
            opacity: .72;
        }
        .academic-period-create .field-help {
            color: var(--dash-muted);
            font-size: .66rem;
        }
        .academic-period-create .field-error {
            color: #fb7185;
            font-size: .68rem;
        }
        .academic-period-create .period-options {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .academic-period-create .period-option {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            color: var(--dash-text);
            background: rgba(77, 143, 240, .04);
            border: 1px solid var(--dash-line);
            border-radius: 10px;
            cursor: pointer;
        }
        .academic-period-create .period-option:hover {
            border-color: rgba(98, 201, 245, .45);
            background: rgba(77, 143, 240, .08);
        }
        .academic-period-create .period-option input {
            width: 16px;
            height: 16px;
            min-height: 16px;
            margin: 2px 0 0;
            accent-color: var(--dash-blue-light);
        }
        .academic-period-create .period-option strong {
            display: block;
            font-size: .75rem;
        }
        .academic-period-create .period-option small {
            display: block;
            margin-top: 3px;
            color: var(--dash-muted);
            font-size: .66rem;
        }
        .academic-period-create .period-card-footer {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            padding: 17px 22px;
            border-top: 1px solid var(--dash-line);
            background: rgba(77, 143, 240, .04);
        }
        @media (max-width: 680px) {
            .academic-period-create .period-hero {
                align-items: flex-start;
            }
            .academic-period-create .period-hero-badge {
                display: none;
            }
            .academic-period-create .period-grid,
            .academic-period-create .period-options {
                grid-template-columns: 1fr;
            }
            .academic-period-create .period-section {
                padding: 18px 15px;
            }
            .academic-period-create .period-card-footer {
                flex-direction: column-reverse;
                padding: 15px;
            }
            .academic-period-create .period-card-footer .btn {
                justify-content: center;
                width: 100%;
            }
        }
    </style>

    <div class="academic-period-create">
        <section class="period-hero" aria-labelledby="period-hero-title">
            <span class="period-hero-icon"><i class="fa-solid fa-calendar-days"></i></span>
            <div>
                <h3 id="period-hero-title">Set up a new academic period</h3>
                <p>Define the school calendar, enrollment window, and current term status.</p>
            </div>
            <span class="period-hero-badge"><i class="fa-solid fa-shield-check"></i> Admin setup</span>
        </section>

        <form class="period-card" method="POST" action="<?php echo e(route('admin.academic_periods.store')); ?>">
            <?php echo csrf_field(); ?>

            <div class="period-card-head">
                <h3><i class="fa-solid fa-calendar-check"></i> Period details</h3>
                <span><span style="color:#fb7185">*</span> Required field</span>
            </div>

            <section class="period-section">
                <h4 class="period-section-title"><i class="fa-solid fa-pen-to-square"></i> Identity and schedule</h4>
                <div class="period-grid">
                    <div class="period-field">
                        <label for="code">Period code <span class="required">*</span></label>
                        <input id="code" type="text" name="code" value="<?php echo e(old('code')); ?>" required maxlength="50" placeholder="e.g. AY2025-2026">
                        <span class="field-help">A short unique identifier for reports and classes.</span>
                        <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="period-field">
                        <label for="name">Period name <span class="required">*</span></label>
                        <input id="name" type="text" name="name" value="<?php echo e(old('name')); ?>" required maxlength="255" placeholder="e.g. Academic Year 2025–2026">
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="period-field">
                        <label for="start_date">Start date <span class="required">*</span></label>
                        <input id="start_date" type="date" name="start_date" value="<?php echo e(old('start_date')); ?>" required>
                        <?php $__errorArgs = ['start_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="period-field">
                        <label for="end_date">End date <span class="required">*</span></label>
                        <input id="end_date" type="date" name="end_date" value="<?php echo e(old('end_date')); ?>" required>
                        <span class="field-help">Must be after the start date.</span>
                        <?php $__errorArgs = ['end_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="period-field full">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" maxlength="2000" placeholder="Optional notes about this academic period..."><?php echo e(old('description')); ?></textarea>
                        <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </section>

            <section class="period-section">
                <h4 class="period-section-title"><i class="fa-solid fa-toggle-on"></i> Period settings</h4>
                <div class="period-options">
                    <label class="period-option">
                        <input type="checkbox" name="is_current" value="1" <?php if(old('is_current')): echo 'checked'; endif; ?>>
                        <span>
                            <strong>Set as current period</strong>
                            <small>Any existing current period will be unset automatically.</small>
                        </span>
                    </label>
                    <label class="period-option">
                        <input type="checkbox" name="is_enrollment_open" value="1" <?php if(old('is_enrollment_open')): echo 'checked'; endif; ?>>
                        <span>
                            <strong>Open enrollment</strong>
                            <small>Allow eligible students to enroll during this period.</small>
                        </span>
                    </label>
                </div>
            </section>

            <div class="period-card-footer">
                <a href="<?php echo e(route('admin.academic_periods.index')); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Create academic period</button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/admin/academic_periods/create.blade.php ENDPATH**/ ?>