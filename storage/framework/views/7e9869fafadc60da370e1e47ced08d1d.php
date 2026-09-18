<?php $__env->startSection('title', 'Add Student'); ?>
<?php $activeNav = 'students'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page student-create-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Add Student','subtitle' => 'Create a secure student account and prepare it for learning access.','icon' => 'fa-user-graduate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Add Student','subtitle' => 'Create a secure student account and prepare it for learning access.','icon' => 'fa-user-graduate']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.students.index')); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Students
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
        <div class="student-form-alert" role="alert" aria-labelledby="student-form-error-title">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <div>
                <strong id="student-form-error-title">Please review the highlighted fields.</strong>
                <ul>
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="student-form-layout">
        <aside class="student-form-aside" aria-label="Student account setup guide">
            <div class="student-form-aside-icon"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></div>
            <span class="student-form-eyebrow">Account setup</span>
            <h2>Build a ready-to-learn profile.</h2>
            <p>Capture the student’s identity, contact details, and access settings in one focused form.</p>
            <ol class="student-form-steps">
                <li class="is-active"><span>01</span><div><strong>Personal</strong><small>Name, email, and password</small></div></li>
                <li><span>02</span><div><strong>Additional</strong><small>Contact and identification</small></div></li>
                <li><span>03</span><div><strong>Access</strong><small>Role and account status</small></div></li>
            </ol>
            <div class="student-form-tip">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                <span>Use a school email and a strong temporary password so the student can sign in safely.</span>
            </div>
        </aside>

        <div class="user-panel student-form-panel">
            <div class="student-form-panel-head">
                <div>
                    <span class="user-kicker"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> New student record</span>
                    <h2>Student information</h2>
                    <p>Required fields are marked with <span aria-hidden="true">*</span>.</p>
                </div>
                <span class="student-form-required"><i class="fa-solid fa-asterisk" aria-hidden="true"></i> Required</span>
            </div>

            <form action="<?php echo e(route('admin.students.store')); ?>" method="POST" id="studentForm" class="student-form" novalidate>
                <?php echo csrf_field(); ?>

                <section class="student-form-section" aria-labelledby="personal-heading">
                    <div class="student-section-heading">
                        <span class="student-section-icon"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
                        <div><h3 id="personal-heading">Personal information</h3><p>How this student will appear across the LMS.</p></div>
                    </div>
                    <div class="student-field-grid">
                        <div class="student-field <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <label for="first_name">First name <span aria-hidden="true">*</span></label>
                            <input id="first_name" type="text" name="first_name" value="<?php echo e(old('first_name')); ?>" required placeholder="e.g. Maria" autocomplete="given-name" aria-describedby="first_name_help first_name_error" <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                            <small id="first_name_help">Given name</small>
                            <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="first_name_error" class="student-field-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="student-field <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <label for="last_name">Last name <span aria-hidden="true">*</span></label>
                            <input id="last_name" type="text" name="last_name" value="<?php echo e(old('last_name')); ?>" required placeholder="e.g. Santos" autocomplete="family-name" aria-describedby="last_name_help last_name_error" <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                            <small id="last_name_help">Family name</small>
                            <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="last_name_error" class="student-field-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="student-field student-field-wide <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <label for="email">School email address <span aria-hidden="true">*</span></label>
                            <div class="student-input-with-icon"><i class="fa-regular fa-envelope" aria-hidden="true"></i><input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required placeholder="student@school.edu" autocomplete="email" aria-describedby="email_help email_error" <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>></div>
                            <small id="email_help">Use the official email address for account access.</small>
                            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="email_error" class="student-field-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="student-field student-field-wide <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <label for="password">Temporary password <span aria-hidden="true">*</span></label>
                            <div class="student-password-wrap"><input id="password" type="password" name="password" required minlength="8" placeholder="At least 8 characters" autocomplete="new-password" aria-describedby="password_help password_strength password_error" <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>><button type="button" class="student-password-toggle" id="togglePassword" aria-label="Show password" aria-pressed="false"><i class="fa-solid fa-eye" aria-hidden="true"></i></button></div>
                            <div id="password_strength" class="student-password-strength" aria-live="polite"><span class="student-strength-track"><span id="strengthBar"></span></span><span id="strengthText">Password strength</span></div>
                            <div id="password_help" class="student-password-requirements"><span data-req="length"><i class="fa-solid fa-circle" aria-hidden="true"></i> 8+ characters</span><span data-req="uppercase"><i class="fa-solid fa-circle" aria-hidden="true"></i> Uppercase</span><span data-req="number"><i class="fa-solid fa-circle" aria-hidden="true"></i> Number</span><span data-req="special"><i class="fa-solid fa-circle" aria-hidden="true"></i> Special character</span></div>
                            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="password_error" class="student-field-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </section>

                <section class="student-form-section" aria-labelledby="additional-heading">
                    <div class="student-section-heading">
                        <span class="student-section-icon"><i class="fa-solid fa-address-card" aria-hidden="true"></i></span>
                        <div><h3 id="additional-heading">Additional details</h3><p>Optional information to support student records.</p></div>
                    </div>
                    <div class="student-field-grid">
                        <div class="student-field <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <label for="phone">Phone number <span class="student-optional">Optional</span></label>
                            <div class="student-input-with-icon"><i class="fa-solid fa-phone" aria-hidden="true"></i><input id="phone" type="tel" name="phone" value="<?php echo e(old('phone')); ?>" placeholder="09XX XXX XXXX" autocomplete="tel" <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>></div>
                            <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="student-field-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="student-field <?php $__errorArgs = ['identifier'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <label for="identifier">Student ID <span class="student-optional">Optional</span></label>
                            <div class="student-input-with-icon"><i class="fa-solid fa-id-card" aria-hidden="true"></i><input id="identifier" type="text" name="identifier" value="<?php echo e(old('identifier')); ?>" placeholder="e.g. STU-2026-001" <?php $__errorArgs = ['identifier'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>></div>
                            <?php $__errorArgs = ['identifier'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="student-field-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="student-field student-field-wide <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <label for="address">Address <span class="student-optional">Optional</span></label>
                            <textarea id="address" name="address" rows="3" placeholder="Enter the student’s address" <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>><?php echo e(old('address')); ?></textarea>
                            <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="student-field-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </section>

                <section class="student-form-section" aria-labelledby="access-heading">
                    <div class="student-section-heading">
                        <span class="student-section-icon"><i class="fa-solid fa-sliders" aria-hidden="true"></i></span>
                        <div><h3 id="access-heading">Account access</h3><p>Choose the initial role and account state.</p></div>
                    </div>
                    <div class="student-field-grid">
                        <div class="student-field <?php $__errorArgs = ['role_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <label for="role_id">Role <span aria-hidden="true">*</span></label>
                            <select id="role_id" name="role_id" aria-describedby="role_help role_error" <?php $__errorArgs = ['role_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($role->id); ?>" <?php if(old('role_id', optional($roles->firstWhere('slug', 'student'))->id) == $role->id): echo 'selected'; endif; ?>><?php echo e(ucfirst($role->slug)); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <small id="role_help">Student is the recommended role for this page.</small>
                            <?php $__errorArgs = ['role_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="role_error" class="student-field-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="student-field <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> has-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <label for="status">Initial status <span aria-hidden="true">*</span></label>
                            <select id="status" name="status" aria-describedby="status_help status_error" <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                                <?php $__currentLoopData = ['active' => 'Active — can sign in', 'inactive' => 'Inactive — access disabled', 'suspended' => 'Suspended — needs review']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>" <?php if(old('status', 'active') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <small id="status_help">Active accounts can sign in immediately.</small>
                            <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span id="status_error" class="student-field-error" role="alert"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </section>

                <div class="student-form-actions">
                    <a href="<?php echo e(route('admin.students.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Create Student</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
.student-create-page{--student-accent:#62c9f5;--student-accent-strong:#2449c6;--student-accent-soft:rgba(98,201,245,.13);gap:16px}.student-create-page .user-hero{display:flex;align-items:flex-end;justify-content:space-between;box-sizing:border-box}.student-create-page .student-form,.student-create-page .student-form *{box-sizing:border-box}
.student-form-alert{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1px solid rgba(251,113,133,.45);border-radius:14px;background:rgba(244,63,94,.1);color:#fecdd3}.student-form-alert>i{margin-top:2px;color:#fb7185}.student-form-alert strong{display:block;color:#ffe4e6;font-size:.8rem}.student-form-alert ul{margin:5px 0 0;padding-left:18px;color:#fda4af;font-size:.72rem}
.student-form-layout{display:grid;grid-template-columns:minmax(205px,.36fr) minmax(0,1fr);gap:16px;align-items:start}.student-form-aside{position:sticky;top:18px;padding:22px 20px;border:1px solid rgba(98,201,245,.22);border-radius:16px;background:linear-gradient(160deg,rgba(36,73,198,.34),rgba(15,31,75,.78));box-shadow:0 16px 36px rgba(3,8,20,.18)}.student-form-aside-icon{display:grid;place-items:center;width:42px;height:42px;margin-bottom:18px;border:1px solid rgba(98,201,245,.32);border-radius:13px;color:var(--student-accent);background:var(--student-accent-soft);font-size:1rem}.student-form-eyebrow{color:var(--student-accent);font-size:.62rem;font-weight:850;letter-spacing:.12em;text-transform:uppercase}.student-form-aside h2{margin:7px 0 8px;color:#fff;font-size:1.18rem;line-height:1.15}.student-form-aside p{margin:0;color:rgba(238,244,255,.72);font-size:.72rem;line-height:1.55}.student-form-steps{display:grid;gap:12px;margin:23px 0 0;padding:0;list-style:none}.student-form-steps li{display:flex;align-items:center;gap:10px;color:rgba(238,244,255,.48)}.student-form-steps li>span{display:grid;place-items:center;width:28px;height:28px;border:1px solid rgba(219,234,254,.18);border-radius:9px;font-size:.6rem;font-weight:800}.student-form-steps li>div{display:grid;gap:2px}.student-form-steps strong{font-size:.7rem}.student-form-steps small{color:rgba(238,244,255,.52);font-size:.61rem}.student-form-steps li.is-active{color:#fff}.student-form-steps li.is-active>span{border-color:var(--student-accent);color:#071421;background:var(--student-accent)}.student-form-tip{display:flex;gap:8px;margin-top:24px;padding:11px;border:1px solid rgba(110,231,183,.2);border-radius:11px;color:#a7f3d0;background:rgba(16,185,129,.08);font-size:.64rem;line-height:1.45}.student-form-tip i{margin-top:2px}
.student-form-panel{overflow:visible}.student-form-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:20px 22px;border-bottom:1px solid var(--dash-line);background:linear-gradient(90deg,var(--student-accent-soft),transparent)}.student-form-panel-head h2{margin:3px 0 3px;color:var(--dash-text);font-size:1.08rem}.student-form-panel-head p{margin:0;color:var(--dash-muted);font-size:.7rem}.student-form-panel-head p span{color:var(--student-accent)}.student-form-required{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border:1px solid rgba(98,201,245,.22);border-radius:999px;color:var(--student-accent);font-size:.6rem;font-weight:800;white-space:nowrap}.student-form-required i{font-size:.48rem}
.student-form{padding:0 22px 22px}.student-form-section{padding:22px 0;border-bottom:1px solid var(--dash-line)}.student-section-heading{display:flex;align-items:center;gap:10px;margin-bottom:17px}.student-section-icon{display:grid;place-items:center;width:32px;height:32px;border-radius:10px;color:var(--student-accent);background:var(--student-accent-soft)}.student-section-heading h3{margin:0;color:var(--dash-text);font-size:.84rem}.student-section-heading p{margin:3px 0 0;color:var(--dash-muted);font-size:.67rem}.student-field-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.student-field{min-width:0}.student-field-wide{grid-column:1/-1}.student-field label{display:flex;align-items:center;gap:4px;margin-bottom:7px;color:var(--dash-text);font-size:.72rem;font-weight:750}.student-field label>span:not(.student-optional){color:var(--student-accent)}.student-optional{margin-left:auto;color:var(--dash-muted);font-size:.6rem;font-weight:500}.student-field input,.student-field select,.student-field textarea{display:block;width:100%;min-height:40px;padding:10px 12px;border:1px solid var(--dash-line);border-radius:9px;color:var(--dash-text);background:var(--dash-surface-raised);font:inherit;font-size:.74rem;outline:none;transition:border-color .16s,box-shadow .16s}.student-field textarea{min-height:82px;resize:vertical}.student-field input::placeholder,.student-field textarea::placeholder{color:var(--dash-muted);opacity:.72}.student-field input:focus,.student-field select:focus,.student-field textarea:focus{border-color:var(--student-accent);box-shadow:0 0 0 3px rgba(98,201,245,.12)}.student-field small{display:block;margin-top:5px;color:var(--dash-muted);font-size:.61rem;line-height:1.4}.student-field.has-error input,.student-field.has-error select,.student-field.has-error textarea{border-color:#fb7185}.student-field-error{display:block;margin-top:5px;color:#fb7185;font-size:.64rem;line-height:1.35}.student-input-with-icon{position:relative}.student-input-with-icon>i{position:absolute;top:50%;left:12px;z-index:1;transform:translateY(-50%);color:var(--student-accent);font-size:.72rem}.student-input-with-icon input{padding-left:34px}.student-password-wrap{position:relative}.student-password-wrap input{padding-right:42px}.student-password-toggle{position:absolute;top:50%;right:7px;display:grid;place-items:center;width:30px;height:30px;transform:translateY(-50%);border:0;border-radius:7px;color:var(--dash-muted);background:transparent;cursor:pointer}.student-password-toggle:hover,.student-password-toggle:focus-visible{color:var(--student-accent);background:var(--student-accent-soft)}.student-password-toggle:focus-visible{outline:2px solid var(--student-accent);outline-offset:1px}.student-password-strength{display:flex;align-items:center;gap:9px;margin-top:8px;color:var(--dash-muted);font-size:.61rem}.student-strength-track{flex:1;height:5px;overflow:hidden;border-radius:99px;background:rgba(153,174,214,.16)}.student-strength-track>span{display:block;width:0;height:100%;border-radius:inherit;background:#fb7185;transition:width .2s,background .2s}.student-password-requirements{display:flex;flex-wrap:wrap;gap:7px;margin-top:8px}.student-password-requirements span{display:inline-flex;align-items:center;gap:4px;color:var(--dash-muted);font-size:.6rem}.student-password-requirements i{color:var(--dash-muted);font-size:.38rem}.student-password-requirements span.is-valid{color:#6ee7b7}.student-password-requirements span.is-valid i{color:#34d399}
.student-form-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;padding-top:20px}.student-form-actions .btn{min-height:38px}.student-form-actions .btn-primary.is-loading{opacity:.75;pointer-events:none}.student-form-actions .btn-primary.is-loading i{animation:student-spin .8s linear infinite}@keyframes student-spin{to{transform:rotate(360deg)}}
body.light-mode .student-form-aside{background:linear-gradient(160deg,rgba(36,73,198,.94),rgba(15,31,75,.98))}.light-mode .student-form-alert strong{color:#881337}.light-mode .student-form-alert ul{color:#be123c}.light-mode .student-form-panel-head h2,.light-mode .student-section-heading h3,.light-mode .student-field label{color:var(--dash-text)}
@media(max-width:860px){.student-create-page .user-hero{align-items:flex-start;flex-direction:column;padding:20px}.student-form-layout{grid-template-columns:1fr}.student-form-aside{position:static}.student-form-steps{grid-template-columns:repeat(3,1fr);gap:7px}.student-form-steps li{align-items:flex-start;flex-direction:column;gap:5px}.student-form-tip{margin-top:18px}}
@media(max-width:620px){.student-form-panel-head{padding:17px 15px}.student-form{padding:0 15px 16px}.student-field-grid{grid-template-columns:1fr;gap:13px}.student-field-wide{grid-column:auto}.student-form-actions{align-items:stretch;flex-direction:column-reverse}.student-form-actions .btn{width:100%;justify-content:center}.student-form-steps{grid-template-columns:1fr;gap:9px}.student-form-steps li{align-items:center;flex-direction:row}.student-form-aside{padding:17px}.student-password-requirements{gap:6px}}
@media(prefers-reduced-motion:reduce){.student-field input,.student-field select,.student-field textarea,.student-password-toggle,.student-strength-track>span{transition:none}.student-form-actions .btn-primary.is-loading i{animation:none}}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const form = document.getElementById('studentForm');
    const password = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    const submit = document.getElementById('submitBtn');

    if (!form) return;

    if (toggle && password) {
        toggle.addEventListener('click', function () {
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            toggle.setAttribute('aria-pressed', visible ? 'false' : 'true');
            toggle.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
            const icon = toggle.querySelector('i');
            if (icon) icon.className = visible ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        });
    }

    function updateStrength() {
        if (!password || !strengthBar || !strengthText) return;
        const value = password.value;
        const checks = {
            length: value.length >= 8,
            uppercase: /[A-Z]/.test(value),
            number: /\d/.test(value),
            special: /[^A-Za-z0-9]/.test(value),
        };
        Object.keys(checks).forEach(function (key) {
            const item = document.querySelector('[data-req="' + key + '"]');
            if (item) item.classList.toggle('is-valid', checks[key]);
        });
        const score = Object.values(checks).filter(Boolean).length;
        const labels = ['Password strength', 'Weak password', 'Fair password', 'Good password', 'Strong password'];
        const colors = ['#fb7185', '#fb7185', '#fbbf24', '#67e8f9', '#34d399'];
        strengthBar.style.width = (score * 25) + '%';
        strengthBar.style.background = colors[score];
        strengthText.textContent = labels[score];
    }

    if (password) password.addEventListener('input', updateStrength);

    form.addEventListener('submit', function () {
        if (!submit || submit.disabled) return;
        submit.disabled = true;
        submit.classList.add('is-loading');
        submit.setAttribute('aria-busy', 'true');
        submit.innerHTML = '<i class="fa-solid fa-spinner" aria-hidden="true"></i> Creating...';
    });

    updateStrength();
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/admin/students/create.blade.php ENDPATH**/ ?>