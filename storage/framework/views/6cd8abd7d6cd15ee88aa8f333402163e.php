<?php $__env->startSection('title', 'Create Enrollment'); ?>
<?php $activeNav = 'enrollment'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page enrollment-create-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'New Enrollment','subtitle' => 'Place an active student into a class and start tracking their learning progress.','icon' => 'fa-user-plus']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'New Enrollment','subtitle' => 'Place an active student into a class and start tracking their learning progress.','icon' => 'fa-user-plus']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.enrollments.index')); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Enrollments
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

    <?php if($errors->any() || session('error')): ?>
        <div class="enrollment-create-alert" role="alert" aria-labelledby="enrollment-create-error-title">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <div>
                <strong id="enrollment-create-error-title">Enrollment could not be created.</strong>
                <?php if(session('error')): ?><p><?php echo e(session('error')); ?></p><?php endif; ?>
                <?php if($errors->any()): ?>
                    <ul>
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="enrollment-create-layout">
        <aside class="enrollment-create-aside" aria-label="Enrollment setup guide">
            <div class="enrollment-create-icon"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></div>
            <span class="enrollment-create-kicker">Enrollment setup</span>
            <h2>Connect a student to a class.</h2>
            <p>Select the right student and class, then choose how the enrollment should begin.</p>
            <ol class="enrollment-create-steps">
                <li class="is-active"><span>01</span><div><strong>Student</strong><small>Choose an active account</small></div></li>
                <li><span>02</span><div><strong>Class</strong><small>Assign a learning space</small></div></li>
                <li><span>03</span><div><strong>Details</strong><small>Set status and notes</small></div></li>
            </ol>
            <div class="enrollment-create-tip">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span>Duplicate enrollments and full classes are checked automatically when you submit.</span>
            </div>
        </aside>

        <div class="user-panel enrollment-create-panel">
            <div class="enrollment-create-panel-head">
                <div>
                    <span class="user-kicker"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> New enrollment record</span>
                    <h2>Enrollment information</h2>
                    <p>Required fields are marked with <span aria-hidden="true">*</span>.</p>
                </div>
                <span class="enrollment-create-required"><i class="fa-solid fa-asterisk" aria-hidden="true"></i> Required</span>
            </div>

            <form action="<?php echo e(route('admin.enrollments.store')); ?>" method="POST" id="enrollmentCreateForm" class="enrollment-create-form">
                <?php echo csrf_field(); ?>

                <section class="enrollment-create-section" aria-labelledby="create-student-heading">
                    <div class="enrollment-section-heading">
                        <span class="enrollment-section-icon"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i></span>
                        <div><h3 id="create-student-heading">Student selection</h3><p>Choose an active student account to enroll.</p></div>
                    </div>
                    <div class="enrollment-create-field <?php $__errorArgs = ['student_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <label for="student_id">Student <span aria-hidden="true">*</span></label>
                        <select id="student_id" name="student_id" required aria-describedby="student-help student-error" <?php $__errorArgs = ['student_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                            <option value="">Select a student</option>
                            <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($student->id); ?>" data-email="<?php echo e($student->email); ?>" data-identifier="<?php echo e($student->identifier ?? ''); ?>" <?php if((string) old('student_id') === (string) $student->id): echo 'selected'; endif; ?>><?php echo e($student->full_name); ?> — <?php echo e($student->email); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <small id="student-help">Only active student accounts are listed.</small>
                        <div id="studentPreview" class="enrollment-create-preview" hidden><span class="enrollment-preview-avatar" id="studentPreviewInitial">S</span><span><strong id="studentPreviewName">Selected student</strong><small id="studentPreviewMeta">student@email.com</small></span><span class="enrollment-preview-check"><i class="fa-solid fa-check" aria-hidden="true"></i></span></div>
                        <?php $__errorArgs = ['student_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="student-error" class="enrollment-create-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </section>

                <section class="enrollment-create-section" aria-labelledby="create-class-heading">
                    <div class="enrollment-section-heading">
                        <span class="enrollment-section-icon"><i class="fa-solid fa-school" aria-hidden="true"></i></span>
                        <div><h3 id="create-class-heading">Class assignment</h3><p>Select where the student will learn.</p></div>
                    </div>
                    <div class="enrollment-create-field <?php $__errorArgs = ['class_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <label for="class_id">Class <span aria-hidden="true">*</span></label>
                        <select id="class_id" name="class_id" required aria-describedby="class-help class-error" <?php $__errorArgs = ['class_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                            <option value="">Select a class</option>
                            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" data-course="<?php echo e($class->course?->title ?? $class->course?->name ?? ''); ?>" data-capacity="<?php echo e($class->max_students ?? '—'); ?>" <?php if((string) old('class_id') === (string) $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?> — <?php echo e($class->course?->title ?? $class->course?->name ?? 'Class'); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <small id="class-help">Only active classes are listed. Capacity is checked on submit.</small>
                        <div id="classPreview" class="enrollment-create-class-preview" hidden><div><span class="enrollment-preview-label">Selected class</span><strong id="classPreviewName">Class code</strong><small id="classPreviewCourse">Course title</small></div><span class="enrollment-class-capacity"><i class="fa-solid fa-users" aria-hidden="true"></i><span id="classPreviewCapacity">Capacity —</span></span></div>
                        <?php $__errorArgs = ['class_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="class-error" class="enrollment-create-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </section>

                <section class="enrollment-create-section" aria-labelledby="create-details-heading">
                    <div class="enrollment-section-heading">
                        <span class="enrollment-section-icon"><i class="fa-solid fa-sliders" aria-hidden="true"></i></span>
                        <div><h3 id="create-details-heading">Enrollment details</h3><p>Define the initial status and keep staff context.</p></div>
                    </div>
                    <div class="enrollment-create-field-grid">
                        <div class="enrollment-create-field <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <label for="status">Initial status <span aria-hidden="true">*</span></label>
                            <select id="status" name="status" required aria-describedby="status-help status-error" <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                                <?php $__currentLoopData = ['pending' => 'Pending — awaiting confirmation', 'active' => 'Active — currently enrolled', 'completed' => 'Completed — course finished', 'dropped' => 'Dropped — no longer enrolled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>" <?php if(old('status', 'active') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <small id="status-help">Active enrollments count toward class capacity.</small>
                            <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="status-error" class="enrollment-create-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="enrollment-create-field enrollment-create-field-note <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <label for="notes">Internal notes <span class="enrollment-create-optional">Optional</span></label>
                            <textarea id="notes" name="notes" rows="4" maxlength="5000" placeholder="Add context for authorized staff..." aria-describedby="notes-help notes-error" <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>><?php echo e(old('notes')); ?></textarea>
                            <div class="enrollment-create-notes-meta"><small id="notes-help">Visible to authorized staff only.</small><span id="notesCount">0 / 5000</span></div>
                            <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="notes-error" class="enrollment-create-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </section>

                <div class="enrollment-create-actions">
                    <a href="<?php echo e(route('admin.enrollments.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary" id="enrollmentCreateSubmit"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Create Enrollment</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
.enrollment-create-page{--enrollment-accent:#62c9f5;--enrollment-accent-strong:#2449c6;--enrollment-accent-soft:rgba(98,201,245,.13);gap:16px}.enrollment-create-page .user-hero{display:flex;align-items:flex-end;justify-content:space-between;box-sizing:border-box}.enrollment-create-page *{box-sizing:border-box}.enrollment-create-alert{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1px solid rgba(251,113,133,.45);border-radius:14px;background:rgba(244,63,94,.1);color:#fecdd3}.enrollment-create-alert>i{margin-top:2px;color:#fb7185}.enrollment-create-alert strong{display:block;color:#ffe4e6;font-size:.8rem}.enrollment-create-alert p{margin:5px 0 0;color:#fda4af;font-size:.72rem}.enrollment-create-alert ul{margin:5px 0 0;padding-left:18px;color:#fda4af;font-size:.72rem}
.enrollment-create-layout{display:grid;grid-template-columns:minmax(220px,.38fr) minmax(0,1fr);gap:16px;align-items:start}.enrollment-create-aside{position:sticky;top:18px;padding:22px 20px;border:1px solid rgba(98,201,245,.24);border-radius:16px;background:linear-gradient(160deg,rgba(36,73,198,.36),rgba(15,31,75,.82));box-shadow:0 16px 36px rgba(3,8,20,.18)}.enrollment-create-icon{display:grid;place-items:center;width:42px;height:42px;margin-bottom:18px;border:1px solid rgba(98,201,245,.3);border-radius:13px;color:var(--enrollment-accent);background:var(--enrollment-accent-soft)}.enrollment-create-kicker{color:var(--enrollment-accent);font-size:.62rem;font-weight:850;letter-spacing:.12em;text-transform:uppercase}.enrollment-create-aside h2{margin:7px 0 8px;color:#fff;font-size:1.18rem;line-height:1.15}.enrollment-create-aside>p{margin:0;color:rgba(238,244,255,.72);font-size:.72rem;line-height:1.55}.enrollment-create-steps{display:grid;gap:12px;margin:23px 0 0;padding:0;list-style:none}.enrollment-create-steps li{display:flex;align-items:center;gap:10px;color:rgba(238,244,255,.48)}.enrollment-create-steps li>span{display:grid;place-items:center;width:28px;height:28px;border:1px solid rgba(219,234,254,.18);border-radius:9px;font-size:.6rem;font-weight:800}.enrollment-create-steps li>div{display:grid;gap:2px}.enrollment-create-steps strong{font-size:.7rem}.enrollment-create-steps small{color:rgba(238,244,255,.52);font-size:.61rem}.enrollment-create-steps li.is-active{color:#fff}.enrollment-create-steps li.is-active>span{border-color:var(--enrollment-accent);color:#071421;background:var(--enrollment-accent)}.enrollment-create-tip{display:flex;gap:8px;margin-top:24px;padding:11px;border:1px solid rgba(98,201,245,.2);border-radius:11px;color:#bae6fd;background:rgba(98,201,245,.08);font-size:.64rem;line-height:1.45}.enrollment-create-tip i{margin-top:2px}
.enrollment-create-panel{overflow:visible}.enrollment-create-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:20px 22px;border-bottom:1px solid var(--dash-line);background:linear-gradient(90deg,var(--enrollment-accent-soft),transparent)}.enrollment-create-panel-head h2{margin:3px 0;color:var(--dash-text);font-size:1.08rem}.enrollment-create-panel-head p{margin:0;color:var(--dash-muted);font-size:.7rem}.enrollment-create-panel-head p span{color:var(--enrollment-accent)}.enrollment-create-required{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border:1px solid rgba(98,201,245,.25);border-radius:999px;color:var(--enrollment-accent);font-size:.6rem;font-weight:800;white-space:nowrap}.enrollment-create-required i{font-size:.48rem}.enrollment-create-form{padding:0 22px 22px}.enrollment-create-section{padding:22px 0;border-bottom:1px solid var(--dash-line)}.enrollment-section-heading{display:flex;align-items:center;gap:10px;margin-bottom:17px}.enrollment-section-icon{display:grid;place-items:center;width:32px;height:32px;border-radius:10px;color:var(--enrollment-accent);background:var(--enrollment-accent-soft)}.enrollment-section-heading h3{margin:0;color:var(--dash-text);font-size:.84rem}.enrollment-section-heading p{margin:3px 0 0;color:var(--dash-muted);font-size:.67rem}.enrollment-create-field{max-width:700px}.enrollment-create-field-grid{display:grid;grid-template-columns:minmax(0,.82fr) minmax(0,1.18fr);gap:15px}.enrollment-create-field label{display:flex;align-items:center;gap:4px;margin-bottom:7px;color:var(--dash-text);font-size:.72rem;font-weight:750}.enrollment-create-field label>span:not(.enrollment-create-optional){color:var(--enrollment-accent)}.enrollment-create-optional{margin-left:auto;color:var(--dash-muted);font-size:.6rem;font-weight:500}.enrollment-create-field select,.enrollment-create-field textarea{display:block;width:100%;min-height:40px;padding:10px 12px;border:1px solid var(--dash-line);border-radius:9px;color:var(--dash-text);background:var(--dash-surface-raised);font:inherit;font-size:.74rem;outline:none;transition:border-color .16s,box-shadow .16s}.enrollment-create-field textarea{min-height:95px;resize:vertical}.enrollment-create-field select:focus,.enrollment-create-field textarea:focus{border-color:var(--enrollment-accent);box-shadow:0 0 0 3px rgba(98,201,245,.12)}.enrollment-create-field.has-error select,.enrollment-create-field.has-error textarea{border-color:#fb7185}.enrollment-create-field>small{display:block;margin-top:6px;color:var(--dash-muted);font-size:.62rem;line-height:1.45}.enrollment-create-error{display:block;margin-top:5px;color:#fb7185;font-size:.64rem;line-height:1.35}.enrollment-create-preview,.enrollment-create-class-preview{display:flex;align-items:center;gap:10px;margin-top:11px;padding:10px 12px;border:1px solid rgba(98,201,245,.2);border-radius:10px;background:rgba(98,201,245,.055)}.enrollment-create-preview[hidden],.enrollment-create-class-preview[hidden]{display:none}.enrollment-preview-avatar{display:grid;place-items:center;width:30px;height:30px;border-radius:9px;color:#071421;background:var(--enrollment-accent);font-size:.68rem;font-weight:850}.enrollment-create-preview>span:nth-child(2){display:grid;gap:2px;min-width:0}.enrollment-create-preview strong{color:var(--dash-text);font-size:.7rem}.enrollment-create-preview small,.enrollment-create-class-preview small{color:var(--dash-muted);font-size:.62rem;overflow-wrap:anywhere}.enrollment-preview-check{margin-left:auto;color:#34d399}.enrollment-create-class-preview{justify-content:space-between}.enrollment-create-class-preview>div{display:grid;gap:3px;min-width:0}.enrollment-preview-label{color:var(--dash-muted);font-size:.58rem;text-transform:uppercase;letter-spacing:.08em}.enrollment-create-class-preview strong{color:var(--dash-text);font-size:.74rem}.enrollment-class-capacity{display:inline-flex;align-items:center;gap:5px;color:var(--enrollment-accent);font-size:.62rem;white-space:nowrap}.enrollment-create-notes-meta{display:flex;justify-content:space-between;gap:8px}.enrollment-create-notes-meta span{color:var(--dash-muted);font-size:.6rem}.enrollment-create-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;padding-top:20px}.enrollment-create-actions .btn{min-height:38px}.enrollment-create-actions .btn-primary.is-loading{opacity:.75;pointer-events:none}.enrollment-create-actions .btn-primary.is-loading i{animation:enrollment-create-spin .8s linear infinite}@keyframes enrollment-create-spin{to{transform:rotate(360deg)}}body.light-mode .enrollment-create-aside{background:linear-gradient(160deg,rgba(36,73,198,.94),rgba(15,31,75,.98))}.light-mode .enrollment-create-alert strong{color:#881337}.light-mode .enrollment-create-alert p,.light-mode .enrollment-create-alert ul{color:#be123c}
@media(max-width:860px){.enrollment-create-page .user-hero{align-items:flex-start;flex-direction:column;padding:20px}.enrollment-create-layout{grid-template-columns:1fr}.enrollment-create-aside{position:static}.enrollment-create-steps{grid-template-columns:repeat(3,1fr);gap:7px}.enrollment-create-steps li{align-items:flex-start;flex-direction:column;gap:5px}}@media(max-width:620px){.enrollment-create-panel-head{padding:17px 15px}.enrollment-create-form{padding:0 15px 16px}.enrollment-create-field-grid{grid-template-columns:1fr;gap:13px}.enrollment-create-actions{align-items:stretch;flex-direction:column-reverse}.enrollment-create-actions .btn{width:100%;justify-content:center}.enrollment-create-steps{grid-template-columns:1fr;gap:9px}.enrollment-create-steps li{align-items:center;flex-direction:row}.enrollment-create-aside{padding:17px}.enrollment-create-class-preview{align-items:flex-start;flex-direction:column}.enrollment-class-capacity{white-space:normal}}@media(prefers-reduced-motion:reduce){.enrollment-create-field select,.enrollment-create-field textarea,.enrollment-create-preview,.enrollment-create-class-preview{transition:none}.enrollment-create-actions .btn-primary.is-loading i{animation:none}}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const form = document.getElementById('enrollmentCreateForm');
    const student = document.getElementById('student_id');
    const studentPreview = document.getElementById('studentPreview');
    const studentInitial = document.getElementById('studentPreviewInitial');
    const studentName = document.getElementById('studentPreviewName');
    const studentMeta = document.getElementById('studentPreviewMeta');
    const classSelect = document.getElementById('class_id');
    const classPreview = document.getElementById('classPreview');
    const className = document.getElementById('classPreviewName');
    const classCourse = document.getElementById('classPreviewCourse');
    const classCapacity = document.getElementById('classPreviewCapacity');
    const notes = document.getElementById('notes');
    const notesCount = document.getElementById('notesCount');
    const submit = document.getElementById('enrollmentCreateSubmit');

    function updateStudentPreview() {
        const option = student?.selectedOptions[0];
        if (!option || !option.value) { if (studentPreview) studentPreview.hidden = true; return; }
        if (studentPreview) studentPreview.hidden = false;
        if (studentName) studentName.textContent = option.textContent.split(' — ')[0];
        if (studentMeta) studentMeta.textContent = option.dataset.email || 'Student account';
        if (studentInitial) studentInitial.textContent = (option.textContent.trim()[0] || 'S').toUpperCase();
    }

    function updateClassPreview() {
        const option = classSelect?.selectedOptions[0];
        if (!option || !option.value) { if (classPreview) classPreview.hidden = true; return; }
        if (classPreview) classPreview.hidden = false;
        if (className) className.textContent = option.textContent.split(' — ')[0];
        if (classCourse) classCourse.textContent = option.dataset.course || 'Course assignment';
        if (classCapacity) classCapacity.textContent = 'Capacity ' + (option.dataset.capacity || '—');
    }

    function updateNotes() { if (notes && notesCount) notesCount.textContent = notes.value.length + ' / 5000'; }
    if (student) student.addEventListener('change', updateStudentPreview);
    if (classSelect) classSelect.addEventListener('change', updateClassPreview);
    if (notes) notes.addEventListener('input', updateNotes);
    if (form) form.addEventListener('submit', function () {
        if (!submit || submit.disabled) return;
        submit.disabled = true;
        submit.classList.add('is-loading');
        submit.setAttribute('aria-busy', 'true');
        submit.innerHTML = '<i class="fa-solid fa-spinner" aria-hidden="true"></i> Creating...';
    });
    updateStudentPreview();
    updateClassPreview();
    updateNotes();
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\enrollments\create.blade.php ENDPATH**/ ?>