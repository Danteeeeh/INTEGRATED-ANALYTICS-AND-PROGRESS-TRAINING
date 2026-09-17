<?php $__env->startSection('title', 'Profile Settings'); ?>
<?php $__env->startSection('sidebar'); ?>
    <?php if(auth()->user()->isAdmin()): ?> <?php echo $__env->make('components.admin-sidebar', ['activeNav' => 'profile'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php elseif(auth()->user()->isInstructor()): ?> <?php echo $__env->make('components.instructor-sidebar', ['activeNav' => 'profile'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php elseif(auth()->user()->isRegistrar()): ?> <?php echo $__env->make('components.registrar-sidebar', ['activeNav' => 'profile'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php else: ?> <?php echo $__env->make('components.student-sidebar', ['activeNav' => 'profile'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<section class="profile-page-header"><div><span class="dashboard-kicker">Account center</span><h1>Profile & security</h1><p>Manage your personal details, profile photo, and password.</p></div><a href="<?php echo e(url()->previous()); ?>" class="dashboard-action"><i class="fa-solid fa-arrow-left"></i> Back</a></section>
<?php if(session('status')): ?><div class="profile-alert success" role="status"><i class="fa-solid fa-circle-check"></i> <?php echo e(session('status')); ?></div><?php endif; ?>
<?php if($errors->any()): ?><div class="profile-alert error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <?php echo e($errors->first()); ?></div><?php endif; ?>

<div class="profile-grid profile-grid-expanded">
    <section class="profile-card profile-summary">
        <?php if($user->profile_photo_path): ?><img class="profile-avatar profile-avatar-image" src="<?php echo e(asset('storage/'.$user->profile_photo_path)); ?>" alt="<?php echo e($user->name); ?> profile photo">
        <?php else: ?><div class="profile-avatar"><?php echo e(strtoupper(substr($user->first_name ?: $user->email, 0, 1))); ?></div><?php endif; ?>
        <h2><?php echo e($user->name); ?></h2><p><?php echo e($user->email); ?></p><span class="profile-role"><?php echo e($user->role?->name ?? $user->role?->slug ?? 'User'); ?></span>
        <dl><div><dt>Identifier</dt><dd><?php echo e($user->identifier ?: 'Not set'); ?></dd></div><div><dt>Account status</dt><dd><?php echo e(ucfirst($user->status ?? 'active')); ?></dd></div><div><dt>Member since</dt><dd><?php echo e($user->created_at?->format('M Y')); ?></dd></div><div><dt>Last login</dt><dd><?php echo e($user->last_login_at?->diffForHumans() ?? 'Not yet recorded'); ?></dd></div></dl>
    </section>

    <section class="profile-card">
        <div class="profile-card-heading"><div><span class="dashboard-kicker">Personal details</span><h2>Edit profile</h2></div><i class="fa-solid fa-user-pen"></i></div>
        <form method="POST" action="<?php echo e(route('profile.update')); ?>" enctype="multipart/form-data" class="profile-form"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
            <div class="profile-photo-picker" data-photo-picker>
                <div class="photo-preview-wrap">
                    <?php if($user->profile_photo_path): ?><img class="photo-preview" data-photo-preview src="<?php echo e(asset('storage/'.$user->profile_photo_path)); ?>" alt="Current profile photo">
                    <?php else: ?><div class="photo-preview photo-preview-placeholder" data-photo-preview><i class="fa-solid fa-user"></i></div><?php endif; ?>
                </div>
                <div class="photo-picker-copy">
                    <b>Profile photo</b><span data-photo-status>JPG, PNG, or WEBP · max 2 MB</span>
                    <div class="profile-photo-control"><label class="photo-upload"><i class="fa-solid fa-camera"></i> Choose photo<input type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp" data-photo-input></label><button type="button" class="photo-clear" data-photo-clear hidden><i class="fa-solid fa-xmark"></i> Clear selection</button><?php if($user->profile_photo_path): ?><label class="photo-remove"><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label><?php endif; ?></div>
                </div>
            </div>
            <div class="profile-form-grid"><label>First name<input name="first_name" value="<?php echo e(old('first_name', $user->first_name)); ?>" required></label><label>Last name<input name="last_name" value="<?php echo e(old('last_name', $user->last_name)); ?>" required></label><label>Email address<input value="<?php echo e($user->email); ?>" disabled></label><label>Phone<input name="phone" value="<?php echo e(old('phone', $user->phone)); ?>" placeholder="Optional"></label><label class="full">Address<textarea name="address" rows="3" placeholder="Optional"><?php echo e(old('address', $user->address)); ?></textarea></label></div>
            <button class="profile-save" type="submit"><i class="fa-solid fa-check"></i> Save profile</button>
        </form>
    </section>

    <section class="profile-card profile-preferences-card">
        <div class="profile-card-heading"><div><span class="dashboard-kicker">Notifications</span><h2>Notification preferences</h2></div><i class="fa-solid fa-bell"></i></div>
        <form method="POST" action="<?php echo e(route('profile.preferences.update')); ?>" class="profile-form"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
            <div class="preference-list">
                <label class="preference-toggle"><span><b>Email notifications</b><small>Receive important account and course updates by email.</small></span><input type="checkbox" name="email_notifications" value="1" <?php if(($preferences['email_notifications'] ?? '1') === '1'): echo 'checked'; endif; ?>></label>
                <label class="preference-toggle"><span><b>In-app notifications</b><small>Show notices and updates inside the LMS.</small></span><input type="checkbox" name="in_app_notifications" value="1" <?php if(($preferences['in_app_notifications'] ?? '1') === '1'): echo 'checked'; endif; ?>></label>
                <label class="preference-toggle"><span><b>Weekly summary</b><small>Receive a weekly learning and activity summary.</small></span><input type="checkbox" name="weekly_summary" value="1" <?php if(($preferences['weekly_summary'] ?? '0') === '1'): echo 'checked'; endif; ?>></label>
            </div>
            <button class="profile-save" type="submit"><i class="fa-solid fa-bell"></i> Save preferences</button>
        </form>
    </section>

    <section class="profile-card profile-security-card">
        <div class="profile-card-heading"><div><span class="dashboard-kicker">Security</span><h2>Change password</h2></div><i class="fa-solid fa-shield-halved"></i></div>
        <form method="POST" action="<?php echo e(route('profile.password.update')); ?>" class="profile-form"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
            <div class="profile-form-grid"><label class="full">Current password<input type="password" name="current_password" autocomplete="current-password" required></label><label>New password<input type="password" name="password" autocomplete="new-password" minlength="8" required></label><label>Confirm new password<input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required></label></div>
            <p class="form-hint">Use at least 8 characters. Your current password is required to save this change.</p><button class="profile-save" type="submit"><i class="fa-solid fa-key"></i> Update password</button>
        </form>
    </section>
</div>
<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-photo-picker]').forEach(function (picker) {
        const input = picker.querySelector('[data-photo-input]');
        let preview = picker.querySelector('[data-photo-preview]');
        const status = picker.querySelector('[data-photo-status]');
        const clear = picker.querySelector('[data-photo-clear]');
        if (!input || !preview) return;
        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/') || file.size > 2 * 1024 * 1024) {
                status.textContent = 'Choose a JPG, PNG, or WEBP image under 2 MB.';
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function (event) {
                if (preview.tagName === 'IMG') preview.src = event.target.result;
                else { preview.outerHTML = '<img class="photo-preview" data-photo-preview src="' + event.target.result + '" alt="Selected profile photo">'; preview = picker.querySelector('[data-photo-preview]'); }
                status.textContent = file.name + ' · ' + Math.ceil(file.size / 1024) + ' KB';
                clear.hidden = false;
            };
            reader.readAsDataURL(file);
        });
        clear.addEventListener('click', function () { input.value = ''; status.textContent = 'JPG, PNG, or WEBP · max 2 MB'; clear.hidden = true; });
    });
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/profile/edit.blade.php ENDPATH**/ ?>