<?php $__env->startSection('title', 'Edit Enrollment'); ?>
<?php $activeNav = 'enrollment'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page enrollment-edit-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Edit Enrollment','subtitle' => 'Update progress, status, and notes for this student’s enrollment.','icon' => 'fa-graduation-cap']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Edit Enrollment','subtitle' => 'Update progress, status, and notes for this student’s enrollment.','icon' => 'fa-graduation-cap']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.enrollments.show', $enrollment)); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> View Enrollment
            </a>
            <a href="<?php echo e(route('admin.enrollments.index')); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-list" aria-hidden="true"></i> All Enrollments
            </a>
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

    <?php if($errors->any()): ?>
        <div class="enrollment-edit-alert" role="alert" aria-labelledby="enrollment-edit-error-title">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <div>
                <strong id="enrollment-edit-error-title">Please review the highlighted fields.</strong>
                <ul>
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="enrollment-edit-layout">
        <aside class="enrollment-summary" aria-label="Enrollment summary">
            <div class="enrollment-summary-top">
                <span class="enrollment-summary-icon"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i></span>
                <span class="user-status <?php echo e($enrollment->status); ?>"><?php echo e(ucfirst($enrollment->status)); ?></span>
            </div>
            <span class="enrollment-summary-kicker">Enrollment #<?php echo e($enrollment->id); ?></span>
            <h2><?php echo e($enrollment->student?->full_name ?? 'Unknown student'); ?></h2>
            <p><?php echo e($enrollment->student?->email ?? 'No email available'); ?></p>

            <dl class="enrollment-summary-list">
                <div><dt><i class="fa-solid fa-school" aria-hidden="true"></i> Class</dt><dd><?php echo e($enrollment->class?->code ?? '—'); ?></dd></div>
                <div><dt><i class="fa-solid fa-book" aria-hidden="true"></i> Course</dt><dd><?php echo e($enrollment->class?->course?->title ?? $enrollment->class?->course?->name ?? '—'); ?></dd></div>
                <div><dt><i class="fa-solid fa-calendar" aria-hidden="true"></i> Enrolled</dt><dd><?php echo e($enrollment->enrolled_at?->format('M d, Y') ?? '—'); ?></dd></div>
            </dl>

            <a class="enrollment-summary-link" href="<?php echo e(route('admin.students.show', $enrollment->student)); ?>">
                View student profile <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
        </aside>

        <div class="user-panel enrollment-edit-panel">
            <div class="enrollment-edit-panel-head">
                <div>
                    <span class="user-kicker"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Enrollment controls</span>
                    <h2>Progress and access</h2>
                    <p>Changes are saved to this enrollment record only.</p>
                </div>
                <span class="enrollment-edit-id">ID <?php echo e($enrollment->id); ?></span>
            </div>

            <form action="<?php echo e(route('admin.enrollments.update', $enrollment)); ?>" method="POST" id="enrollmentEditForm" class="enrollment-edit-form">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <section class="enrollment-edit-section" aria-labelledby="status-heading">
                    <div class="enrollment-section-heading">
                        <span class="enrollment-section-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
                        <div><h3 id="status-heading">Enrollment status</h3><p>Set the current academic state of this enrollment.</p></div>
                    </div>
                    <div class="enrollment-edit-field <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <label for="status">Status <span aria-hidden="true">*</span></label>
                        <select id="status" name="status" required aria-describedby="status-help status-error" <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                            <?php $__currentLoopData = ['pending' => 'Pending — awaiting confirmation', 'active' => 'Active — currently enrolled', 'completed' => 'Completed — course finished', 'dropped' => 'Dropped — no longer enrolled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('status', $enrollment->status) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <small id="status-help">Use Completed only when the student has finished the course requirements.</small>
                        <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="status-error" class="enrollment-field-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </section>

                <section class="enrollment-edit-section" aria-labelledby="grade-heading">
                    <div class="enrollment-section-heading">
                        <span class="enrollment-section-icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></span>
                        <div><h3 id="grade-heading">Academic result</h3><p>Record the final grade when it is available.</p></div>
                    </div>
                    <div class="enrollment-edit-field <?php $__errorArgs = ['final_grade'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <label for="final_grade">Final grade <span class="enrollment-edit-optional">Optional</span></label>
                        <div class="enrollment-grade-input"><input id="final_grade" type="number" name="final_grade" value="<?php echo e(old('final_grade', $enrollment->final_grade)); ?>" min="0" max="100" step="0.01" placeholder="0.00" aria-describedby="grade-help grade-error" <?php $__errorArgs = ['final_grade'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>><span>/ 100</span></div>
                        <div class="enrollment-grade-track" aria-hidden="true"><span id="gradeProgress"></span></div>
                        <small id="grade-help">Enter a value from 0 to 100. Leave blank if not yet graded.</small>
                        <?php $__errorArgs = ['final_grade'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="grade-error" class="enrollment-field-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </section>

                <section class="enrollment-edit-section" aria-labelledby="notes-heading">
                    <div class="enrollment-section-heading">
                        <span class="enrollment-section-icon"><i class="fa-solid fa-note-sticky" aria-hidden="true"></i></span>
                        <div><h3 id="notes-heading">Internal notes</h3><p>Keep useful context for staff reviewing this enrollment.</p></div>
                    </div>
                    <div class="enrollment-edit-field <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <label for="notes">Notes <span class="enrollment-edit-optional">Optional</span></label>
                        <textarea id="notes" name="notes" rows="5" maxlength="5000" placeholder="Add a note about this enrollment..." aria-describedby="notes-help notes-error" <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>><?php echo e(old('notes', $enrollment->notes)); ?></textarea>
                        <div class="enrollment-notes-meta"><small id="notes-help">Visible to authorized staff only.</small><span id="notesCount">0 / 5000</span></div>
                        <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="notes-error" class="enrollment-field-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </section>

                <div class="enrollment-edit-actions">
                    <a href="<?php echo e(route('admin.enrollments.show', $enrollment)); ?>" class="btn btn-secondary"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary" id="enrollmentSubmit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
.enrollment-edit-page{--enrollment-accent:#62c9f5;--enrollment-accent-strong:#2449c6;--enrollment-accent-soft:rgba(98,201,245,.13);gap:16px}.enrollment-edit-page .user-hero{display:flex;align-items:flex-end;justify-content:space-between;box-sizing:border-box}.enrollment-edit-page *{box-sizing:border-box}.enrollment-edit-alert{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1px solid rgba(251,113,133,.45);border-radius:14px;background:rgba(244,63,94,.1);color:#fecdd3}.enrollment-edit-alert>i{margin-top:2px;color:#fb7185}.enrollment-edit-alert strong{display:block;color:#ffe4e6;font-size:.8rem}.enrollment-edit-alert ul{margin:5px 0 0;padding-left:18px;color:#fda4af;font-size:.72rem}
.enrollment-edit-layout{display:grid;grid-template-columns:minmax(225px,.38fr) minmax(0,1fr);gap:16px;align-items:start}.enrollment-summary{position:sticky;top:18px;padding:20px;border:1px solid rgba(98,201,245,.24);border-radius:16px;background:linear-gradient(160deg,rgba(36,73,198,.36),rgba(15,31,75,.82));box-shadow:0 16px 36px rgba(3,8,20,.18)}.enrollment-summary-top{display:flex;align-items:center;justify-content:space-between;gap:10px}.enrollment-summary-icon{display:grid;place-items:center;width:42px;height:42px;border:1px solid rgba(98,201,245,.3);border-radius:13px;color:var(--enrollment-accent);background:var(--enrollment-accent-soft)}.enrollment-summary-kicker{display:block;margin-top:20px;color:var(--enrollment-accent);font-size:.62rem;font-weight:850;letter-spacing:.12em;text-transform:uppercase}.enrollment-summary h2{margin:7px 0 4px;color:#fff;font-size:1.15rem;line-height:1.15}.enrollment-summary>p{margin:0;color:rgba(238,244,255,.7);font-size:.7rem;overflow-wrap:anywhere}.enrollment-summary-list{display:grid;gap:12px;margin:21px 0 0;padding:17px 0;border-top:1px solid rgba(219,234,254,.13);border-bottom:1px solid rgba(219,234,254,.13)}.enrollment-summary-list div{display:grid;gap:4px}.enrollment-summary-list dt{color:rgba(238,244,255,.55);font-size:.62rem;text-transform:uppercase;letter-spacing:.06em}.enrollment-summary-list dt i{width:16px;color:var(--enrollment-accent)}.enrollment-summary-list dd{margin:0;color:#fff;font-size:.74rem;line-height:1.35}.enrollment-summary-link{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:17px;color:var(--enrollment-accent);font-size:.68rem;font-weight:750;text-decoration:none}.enrollment-summary-link:hover{text-decoration:underline}.enrollment-edit-panel{overflow:visible}.enrollment-edit-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:20px 22px;border-bottom:1px solid var(--dash-line);background:linear-gradient(90deg,var(--enrollment-accent-soft),transparent)}.enrollment-edit-panel-head h2{margin:3px 0;color:var(--dash-text);font-size:1.08rem}.enrollment-edit-panel-head p{margin:0;color:var(--dash-muted);font-size:.7rem}.enrollment-edit-id{padding:5px 8px;border:1px solid rgba(98,201,245,.25);border-radius:999px;color:var(--enrollment-accent);font-size:.61rem;font-weight:800;white-space:nowrap}
.enrollment-edit-form{padding:0 22px 22px}.enrollment-edit-section{padding:22px 0;border-bottom:1px solid var(--dash-line)}.enrollment-section-heading{display:flex;align-items:center;gap:10px;margin-bottom:17px}.enrollment-section-icon{display:grid;place-items:center;width:32px;height:32px;border-radius:10px;color:var(--enrollment-accent);background:var(--enrollment-accent-soft)}.enrollment-section-heading h3{margin:0;color:var(--dash-text);font-size:.84rem}.enrollment-section-heading p{margin:3px 0 0;color:var(--dash-muted);font-size:.67rem}.enrollment-edit-field{max-width:620px}.enrollment-edit-field label{display:flex;align-items:center;gap:4px;margin-bottom:7px;color:var(--dash-text);font-size:.72rem;font-weight:750}.enrollment-edit-field label>span:not(.enrollment-edit-optional){color:var(--enrollment-accent)}.enrollment-edit-optional{margin-left:auto;color:var(--dash-muted);font-size:.6rem;font-weight:500}.enrollment-edit-field select,.enrollment-edit-field input,.enrollment-edit-field textarea{display:block;width:100%;min-height:40px;padding:10px 12px;border:1px solid var(--dash-line);border-radius:9px;color:var(--dash-text);background:var(--dash-surface-raised);font:inherit;font-size:.74rem;outline:none;transition:border-color .16s,box-shadow .16s}.enrollment-edit-field textarea{min-height:105px;resize:vertical}.enrollment-edit-field input::placeholder,.enrollment-edit-field textarea::placeholder{color:var(--dash-muted);opacity:.72}.enrollment-edit-field select:focus,.enrollment-edit-field input:focus,.enrollment-edit-field textarea:focus{border-color:var(--enrollment-accent);box-shadow:0 0 0 3px rgba(98,201,245,.12)}.enrollment-edit-field.has-error select,.enrollment-edit-field.has-error input,.enrollment-edit-field.has-error textarea{border-color:#fb7185}.enrollment-edit-field small{display:block;margin-top:6px;color:var(--dash-muted);font-size:.62rem;line-height:1.45}.enrollment-field-error{display:block;margin-top:5px;color:#fb7185;font-size:.64rem;line-height:1.35}.enrollment-grade-input{display:flex;align-items:center;gap:9px}.enrollment-grade-input input{max-width:190px}.enrollment-grade-input>span{color:var(--dash-muted);font-size:.72rem}.enrollment-grade-track{height:6px;max-width:240px;overflow:hidden;margin-top:10px;border-radius:99px;background:rgba(153,174,214,.16)}.enrollment-grade-track>span{display:block;width:0;height:100%;border-radius:inherit;background:linear-gradient(90deg,#2449c6,#62c9f5);transition:width .2s}.enrollment-notes-meta{display:flex;justify-content:space-between;gap:8px}.enrollment-notes-meta span{color:var(--dash-muted);font-size:.6rem}.enrollment-edit-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;padding-top:20px}.enrollment-edit-actions .btn{min-height:38px}.enrollment-edit-actions .btn-primary.is-loading{opacity:.75;pointer-events:none}.enrollment-edit-actions .btn-primary.is-loading i{animation:enrollment-spin .8s linear infinite}@keyframes enrollment-spin{to{transform:rotate(360deg)}}body.light-mode .enrollment-summary{background:linear-gradient(160deg,rgba(36,73,198,.94),rgba(15,31,75,.98))}.light-mode .enrollment-edit-alert strong{color:#881337}.light-mode .enrollment-edit-alert ul{color:#be123c}
@media(max-width:860px){.enrollment-edit-page .user-hero{align-items:flex-start;flex-direction:column;padding:20px}.enrollment-edit-layout{grid-template-columns:1fr}.enrollment-summary{position:static}}@media(max-width:620px){.enrollment-edit-panel-head{padding:17px 15px}.enrollment-edit-form{padding:0 15px 16px}.enrollment-edit-actions{align-items:stretch;flex-direction:column-reverse}.enrollment-edit-actions .btn{width:100%;justify-content:center}.enrollment-summary{padding:17px}.enrollment-grade-input input{max-width:none}}@media(prefers-reduced-motion:reduce){.enrollment-edit-field select,.enrollment-edit-field input,.enrollment-edit-field textarea,.enrollment-grade-track>span{transition:none}.enrollment-edit-actions .btn-primary.is-loading i{animation:none}}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const form = document.getElementById('enrollmentEditForm');
    const grade = document.getElementById('final_grade');
    const progress = document.getElementById('gradeProgress');
    const notes = document.getElementById('notes');
    const notesCount = document.getElementById('notesCount');
    const submit = document.getElementById('enrollmentSubmit');

    function updateGrade() {
        if (!grade || !progress) return;
        const value = Math.max(0, Math.min(100, Number(grade.value) || 0));
        progress.style.width = (grade.value === '' ? 0 : value) + '%';
    }

    function updateNotes() {
        if (notes && notesCount) notesCount.textContent = notes.value.length + ' / 5000';
    }

    if (grade) grade.addEventListener('input', updateGrade);
    if (notes) notes.addEventListener('input', updateNotes);
    if (form) form.addEventListener('submit', function () {
        if (!submit || submit.disabled) return;
        submit.disabled = true;
        submit.classList.add('is-loading');
        submit.setAttribute('aria-busy', 'true');
        submit.innerHTML = '<i class="fa-solid fa-spinner" aria-hidden="true"></i> Saving...';
    });

    updateGrade();
    updateNotes();
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\enrollments\edit.blade.php ENDPATH**/ ?>