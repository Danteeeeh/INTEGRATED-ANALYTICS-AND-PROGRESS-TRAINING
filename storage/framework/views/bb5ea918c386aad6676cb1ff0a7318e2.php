<?php $__env->startSection('title', 'Create Calendar Event'); ?>
<?php
    $activeNav = 'calendar';
    $pageTitle = 'Create Calendar Event';
    $pageIcon = '<i class="fa-solid fa-calendar-plus"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title"><i class="fa-solid fa-calendar-plus"></i> Create Calendar Event</h2>
        <div class="page-actions">
            <a href="<?php echo e(route('instructor.classes.calendar.index', $class)); ?>" class="btn-modal-cancel" style="padding:10px 18px;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;">
                <i class="fa-solid fa-arrow-left"></i> Back to Calendar
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<style>
    .cal-create-hero{position:relative;overflow:hidden;display:flex;align-items:center;gap:16px;padding:20px 26px;margin-bottom:22px;border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:16px;background:radial-gradient(circle at 90% 10%,rgba(251,191,36,.28),transparent 42%),linear-gradient(135deg,rgba(180,83,9,.82),rgba(10,16,32,.96));box-shadow:var(--bcp-shadow,0 16px 36px rgba(3,8,20,.3))}.cal-create-icon{width:50px;height:50px;flex:none;display:grid;place-items:center;background:linear-gradient(135deg,#fbbf24,#d97706);color:#1a1205;font-size:21px;border-radius:13px;box-shadow:0 10px 22px rgba(251,191,36,.3)}.cal-create-copy{min-width:0}.cal-kicker{display:block;color:#fcd34d;font-size:.64rem;font-weight:800;letter-spacing:.15em;text-transform:uppercase;margin-bottom:4px}.cal-create-copy h3{margin:0;color:#fff;font-size:1.05rem;font-weight:800}.cal-create-copy p{margin:4px 0 0;color:#c8d9f8;font-size:.78rem}.cal-class-badge{margin-left:auto;flex:none;padding:6px 13px;border-radius:999px;background:rgba(251,191,36,.13);border:1px solid rgba(251,191,36,.3);color:#fcd34d;font-size:.72rem;font-weight:750}.cal-card{max-width:960px;margin:0 auto;background:var(--bcp-card,var(--dash-surface,#151c2c));border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:16px;overflow:hidden;box-shadow:var(--bcp-shadow,0 18px 40px rgba(3,8,20,.3))}.cal-card-head{display:flex;align-items:center;gap:10px;padding:16px 24px;border-bottom:1px solid var(--bcp-line,rgba(153,174,214,.18));background:linear-gradient(90deg,rgba(251,191,36,.1),transparent)}.cal-card-head h3{margin:0;color:var(--bcp-ink,#eef4ff);font-size:.85rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase}.cal-card-head h3 i{color:#fcd34d;margin-right:8px}.cal-section{padding:22px 24px}.cal-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.cal-field{display:flex;flex-direction:column;gap:6px}.cal-field.full{grid-column:1/-1}.cal-field label{color:var(--bcp-muted,#98a7c4);font-size:.72rem;font-weight:750}.cal-field label .req{color:#fda4af}.cal-field input,.cal-field select,.cal-field textarea{width:100%;min-height:42px;padding:9px 12px;border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:9px;background:#101625;color:var(--bcp-ink,#eef4ff);font-size:.88rem;transition:border-color .18s,box-shadow .18s}.cal-field input:focus,.cal-field select:focus,.cal-field textarea:focus{outline:0;border-color:#fcd34d;box-shadow:0 0 0 3px rgba(251,191,36,.15)}.cal-field textarea{resize:vertical;min-height:90px}.cal-field select option{background:#151c2c;color:#eef4ff}.cal-hint{color:var(--bcp-muted,#98a7c4);font-size:.68rem}.cal-check{display:flex;align-items:center;gap:9px;color:var(--bcp-ink,#eef4ff);font-size:.8rem;font-weight:650}.cal-check input{width:17px;height:17px;accent-color:#fbbf24}.cal-footer{display:flex;justify-content:flex-end;gap:10px;padding:18px 24px;border-top:1px solid var(--bcp-line,rgba(153,174,214,.18));background:rgba(251,191,36,.04)}.cal-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 24px;border-radius:9px;font-size:.84rem;font-weight:700;text-decoration:none;cursor:pointer;transition:transform .15s,background .15s}.cal-btn:hover{transform:translateY(-1px)}.cal-btn-cancel{color:var(--bcp-text,#eef4ff);background:var(--dash-surface-raised,#1b2437);border:1px solid var(--bcp-line,rgba(153,174,214,.18))}.cal-btn-save{color:#1a1205;background:linear-gradient(135deg,#fbbf24,#d97706);border:1px solid #d97706;box-shadow:0 8px 20px rgba(251,191,36,.25)}@media(max-width:680px){.cal-grid{grid-template-columns:1fr}.cal-create-hero{padding:16px 18px;flex-wrap:wrap}.cal-class-badge{margin-left:0}.cal-footer{flex-direction:column-reverse}.cal-btn{justify-content:center}}
</style>

<section class="cal-create-hero">
    <div class="cal-create-icon"><i class="fa-solid fa-calendar-days"></i></div>
    <div class="cal-create-copy">
        <span class="cal-kicker">Class schedule</span>
        <h3>New Event — <?php echo e($class->code); ?></h3>
        <p><?php echo e($class->course->name ?? $class->course->title ?? 'Class calendar'); ?> · Magdagdag ng importanteng event</p>
    </div>
    <span class="cal-class-badge"><i class="fa-solid fa-school"></i> <?php echo e($class->code); ?></span>
</section>

<div class="cal-card">
    <div class="cal-card-head"><h3><i class="fa-solid fa-calendar-plus"></i> Event Details</h3></div>
    <form method="POST" action="<?php echo e(route('instructor.classes.calendar.store', $class)); ?>" id="calendarForm">
        <?php echo csrf_field(); ?>
        <div class="cal-section">
            <div class="cal-grid">
                <div class="cal-field full">
                    <label>Event Title <span class="req">*</span></label>
                    <input type="text" name="title" value="<?php echo e(old('title')); ?>" required maxlength="255" placeholder="e.g. Midterm Exam">
                    <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="cal-field">
                    <label>Event Type <span class="req">*</span></label>
                    <select name="event_type" required>
                        <?php $__currentLoopData = $eventTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($key); ?>" <?php echo e(old('event_type') === $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['event_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="cal-field">
                    <label>Visibility <span class="req">*</span></label>
                    <select name="visibility" required>
                        <?php $__currentLoopData = $visibilityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($key); ?>" <?php echo e(old('visibility', 'class') === $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['visibility'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="cal-field">
                    <label>Start <span class="req">*</span></label>
                    <input type="datetime-local" name="start_at" value="<?php echo e(old('start_at')); ?>" required>
                    <?php $__errorArgs = ['start_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="cal-field">
                    <label>End</label>
                    <input type="datetime-local" name="end_at" value="<?php echo e(old('end_at')); ?>">
                    <span class="cal-hint">Optional; dapat ay pagkatapos ng start.</span>
                    <?php $__errorArgs = ['end_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="cal-field">
                    <label>Location</label>
                    <input type="text" name="location" value="<?php echo e(old('location')); ?>" maxlength="255" placeholder="Room / online link">
                    <?php $__errorArgs = ['location'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-message"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="cal-field" style="justify-content:center;">
                    <label class="cal-check"><input type="checkbox" name="is_all_day" value="1" <?php echo e(old('is_all_day') ? 'checked' : ''); ?>> All-day event</label>
                </div>
                <div class="cal-field full">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Optional details para sa event..."><?php echo e(old('description')); ?></textarea>
                    <?php $__errorArgs = ['description'];
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
        <div class="cal-footer">
            <a href="<?php echo e(route('instructor.classes.calendar.index', $class)); ?>" class="cal-btn cal-btn-cancel"><i class="fa-solid fa-xmark"></i> Cancel</a>
            <button type="submit" class="cal-btn cal-btn-save"><i class="fa-solid fa-calendar-check"></i> Create Event</button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\calendar\create.blade.php ENDPATH**/ ?>