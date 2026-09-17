<?php $__env->startSection('title', 'Settings'); ?>
<?php ($activeNav = 'settings'); ?>
<?php $__env->startSection('content'); ?>
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-cog"></i> Settings</h3>
        <span class="dash-section-kicker">Login, security, and system defaults</span>
    </div>
</div>
<section class="dash-panel">
    <form method="POST" action="<?php echo e(route('admin.settings.update')); ?>" class="modal-body">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <div class="detail-grid" style="padding: 8px 4px 16px;">
            <div class="form-group">
                <label for="login_max_attempts">Login max attempts</label>
                <input id="login_max_attempts" class="form-control" type="number" name="login_max_attempts" min="1" max="100" value="<?php echo e(old('login_max_attempts', $settings['login_max_attempts'])); ?>" required>
            </div>
            <div class="form-group">
                <label for="password_min_length">Password min length</label>
                <input id="password_min_length" class="form-control" type="number" name="password_min_length" min="4" max="64" value="<?php echo e(old('password_min_length', $settings['password_min_length'])); ?>" required>
            </div>
            <div class="form-group">
                <label for="api_rate_limit">API rate limit</label>
                <input id="api_rate_limit" class="form-control" type="number" name="api_rate_limit" min="1" max="10000" value="<?php echo e(old('api_rate_limit', $settings['api_rate_limit'])); ?>" required>
            </div>
            <div class="form-group">
                <label for="export_chunk_size">Export chunk size</label>
                <input id="export_chunk_size" class="form-control" type="number" name="export_chunk_size" min="10" max="10000" value="<?php echo e(old('export_chunk_size', $settings['export_chunk_size'])); ?>" required>
            </div>
            <div class="form-group">
                <label for="registration_default_status">Registration default status</label>
                <select id="registration_default_status" class="form-control" name="registration_default_status" required>
                    <option value="pending" <?php if(old('registration_default_status', $settings['registration_default_status']) === 'pending'): echo 'selected'; endif; ?>>Pending</option>
                    <option value="active" <?php if(old('registration_default_status', $settings['registration_default_status']) === 'active'): echo 'selected'; endif; ?>>Active</option>
                </select>
            </div>
            <div class="form-group">
                <label for="dev_seed_password">Dev seed password</label>
                <input id="dev_seed_password" class="form-control" type="password" name="dev_seed_password" minlength="4" value="" placeholder="Leave blank to keep current">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save settings</button>
        </div>
    </form>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\settings\index.blade.php ENDPATH**/ ?>