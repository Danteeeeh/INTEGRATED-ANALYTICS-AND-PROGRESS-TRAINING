<?php
    $editing = isset($class) && $class;
    $classValue = fn (string $field, $fallback = '') => old($field, $editing ? data_get($class, $field, $fallback) : $fallback);
    $formAction = $editing ? route('admin.classes.update', $class) : route('admin.classes.store');
    $submitLabel = $editing ? 'Save class changes' : 'Create class';
?>

<style>
    .class-form-shell { max-width: 1040px; margin: 0 auto; }
    .class-hero { display:flex; align-items:center; gap:15px; margin-bottom:18px; padding:20px 22px; border:1px solid var(--dash-line); border-radius:16px; background:radial-gradient(circle at 90% 10%,rgba(98,201,245,.25),transparent 40%),linear-gradient(135deg,rgba(23,58,168,.92),rgba(10,16,32,.97)); box-shadow:var(--bcp-shadow); }
    .class-hero-icon { display:grid; place-items:center; width:48px; height:48px; flex:none; border-radius:13px; color:#fff; background:linear-gradient(135deg,#22d3ee,#0e7490); box-shadow:0 10px 24px rgba(34,211,238,.26); }
    .class-hero h3 { margin:0; color:#fff; font-size:1.02rem; }
    .class-hero p { margin:4px 0 0; color:#c8d9f8; font-size:.76rem; }
    .class-hero-badge { margin-left:auto; padding:6px 11px; border:1px solid rgba(98,201,245,.28); border-radius:999px; color:#cceeff; background:rgba(98,201,245,.1); font-size:.68rem; font-weight:750; white-space:nowrap; }
    .class-form-card { overflow:hidden; background:var(--dash-surface); border:1px solid var(--dash-line); border-radius:16px; box-shadow:var(--bcp-shadow); }
    .class-form-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:17px 22px; border-bottom:1px solid var(--dash-line); background:linear-gradient(90deg,rgba(77,143,240,.1),transparent); }
    .class-form-head h3 { display:flex; align-items:center; gap:8px; margin:0; color:var(--dash-text); font-size:.84rem; letter-spacing:.06em; text-transform:uppercase; }
    .class-form-head h3 i { color:var(--dash-cyan); }
    .class-form-head span { color:var(--dash-muted); font-size:.68rem; }
    .class-form-section { padding:22px; }
    .class-form-section + .class-form-section { border-top:1px solid var(--dash-line); }
    .class-section-title { display:flex; align-items:center; gap:8px; margin:0 0 16px; color:var(--dash-text); font-size:.76rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .class-section-title i { color:var(--dash-cyan); }
    .class-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:15px; }
    .class-field { display:flex; flex-direction:column; gap:6px; min-width:0; }
    .class-field.full { grid-column:1 / -1; }
    .class-field label { color:var(--dash-muted); font-size:.72rem; font-weight:750; }
    .class-field .required { color:#fb7185; }
    .class-field input, .class-field select { width:100%; min-height:39px; padding:9px 11px; color:var(--dash-text); background:var(--dash-bg); border:1px solid var(--dash-line); border-radius:9px; font-size:.82rem; }
    .class-field input:focus, .class-field select:focus { outline:0; border-color:var(--dash-blue-light); box-shadow:0 0 0 3px rgba(77,143,240,.16); }
    .class-field input::placeholder { color:var(--dash-muted); opacity:.72; }
    .class-field .help { color:var(--dash-muted); font-size:.66rem; }
    .class-field .error { color:#fb7185; font-size:.68rem; }
    .class-form-footer { display:flex; justify-content:flex-end; gap:9px; padding:17px 22px; border-top:1px solid var(--dash-line); background:rgba(77,143,240,.04); }
    @media(max-width:680px) { .class-hero { align-items:flex-start; } .class-hero-badge { display:none; } .class-form-grid { grid-template-columns:1fr; } .class-form-section { padding:18px 15px; } .class-form-footer { flex-direction:column-reverse; padding:15px; } .class-form-footer .btn { width:100%; justify-content:center; } }
</style>

<div class="class-form-shell">
    <section class="class-hero">
        <span class="class-hero-icon"><i class="fa-solid fa-chalkboard-user"></i></span>
        <div>
            <h3><?php echo e($editing ? 'Update class details' : 'Set up a new class'); ?></h3>
            <p><?php echo e($editing ? 'Keep the class schedule, instructor, and capacity up to date.' : 'Connect a course, instructor, academic period, and classroom schedule.'); ?></p>
        </div>
        <span class="class-hero-badge"><i class="fa-solid fa-shield-check"></i> Admin setup</span>
    </section>

    <form class="class-form-card" method="POST" action="<?php echo e($formAction); ?>">
        <?php echo csrf_field(); ?>
        <?php if($editing): ?>
            <?php echo method_field('PUT'); ?>
        <?php endif; ?>

        <div class="class-form-head">
            <h3><i class="fa-solid fa-layer-group"></i> Class information</h3>
            <span><span style="color:#fb7185">*</span> Required field</span>
        </div>

        <section class="class-form-section">
            <h4 class="class-section-title"><i class="fa-solid fa-pen-to-square"></i> Identity and assignment</h4>
            <div class="class-form-grid">
                <div class="class-field">
                    <label for="code">Class code <span class="required">*</span></label>
                    <input id="code" type="text" name="code" value="<?php echo e($classValue('code')); ?>" required maxlength="50" placeholder="e.g. CS101-01">
                    <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="class-field">
                    <label for="course_id">Course <span class="required">*</span></label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Select a course</option>
                        <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($course->id); ?>" <?php if((string)$classValue('course_id') === (string)$course->id): echo 'selected'; endif; ?>><?php echo e($course->code); ?> — <?php echo e($course->title ?? $course->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['course_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="class-field">
                    <label for="academic_period_id">Academic period</label>
                    <select id="academic_period_id" name="academic_period_id">
                        <option value="">No period selected</option>
                        <?php $__currentLoopData = $periods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $period): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($period->id); ?>" <?php if((string)$classValue('academic_period_id') === (string)$period->id): echo 'selected'; endif; ?>><?php echo e($period->code); ?> — <?php echo e($period->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['academic_period_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="class-field">
                    <label for="instructor_id">Instructor <span class="required">*</span></label>
                    <select id="instructor_id" name="instructor_id" required>
                        <option value="">Select an instructor</option>
                        <?php $__currentLoopData = $instructors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $instructor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($instructor->id); ?>" <?php if((string)$classValue('instructor_id') === (string)$instructor->id): echo 'selected'; endif; ?>><?php echo e($instructor->full_name); ?> — <?php echo e($instructor->email); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['instructor_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
        </section>

        <section class="class-form-section">
            <h4 class="class-section-title"><i class="fa-solid fa-calendar-days"></i> Schedule and capacity</h4>
            <div class="class-form-grid">
                <div class="class-field">
                    <label for="schedule">Schedule</label>
                    <input id="schedule" type="text" name="schedule" value="<?php echo e($classValue('schedule')); ?>" maxlength="255" placeholder="e.g. Mon/Wed 10:00–11:30">
                    <?php $__errorArgs = ['schedule'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="class-field">
                    <label for="room">Room or location</label>
                    <input id="room" type="text" name="room" value="<?php echo e($classValue('room')); ?>" maxlength="100" placeholder="e.g. Room 101, Building A">
                    <?php $__errorArgs = ['room'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="class-field">
                    <label for="capacity">Maximum students</label>
                    <input id="capacity" type="number" name="capacity" value="<?php echo e($classValue('capacity', 30)); ?>" min="1" max="200" placeholder="30">
                    <span class="help">Set a practical limit for enrollment.</span>
                    <?php $__errorArgs = ['capacity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="class-field">
                    <label for="status">Status <span class="required">*</span></label>
                    <select id="status" name="status" required>
                        <?php $__currentLoopData = ['active' => 'Active', 'inactive' => 'Inactive', 'cancelled' => 'Cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if($classValue('status', 'active') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
        </section>

        <div class="class-form-footer">
            <a href="<?php echo e(route('admin.classes.index')); ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> <?php echo e($submitLabel); ?></button>
        </div>
    </form>
</div>
<?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\classes\_form.blade.php ENDPATH**/ ?>