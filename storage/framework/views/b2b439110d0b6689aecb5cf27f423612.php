<?php $__env->startSection('title', 'User Details'); ?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('components.admin-sidebar', ['activeNav' => 'users'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $activeNav = 'users'; ?>
<?php $__env->startSection('page-title-bar'); ?>
<div class="page-title-bar">
    <h2 class="page-title">
        <i class="fa-solid fa-user"></i>
        User Details
    </h2>
    <div class="page-actions">
        <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Users
        </a>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $user)): ?>
            <a href="<?php echo e(route('admin.users.edit', $user)); ?>" class="btn btn-primary">
                <i class="fa-solid fa-edit"></i>
                Edit User
            </a>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-body">
        <div class="user-profile">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                </div>
                <div class="profile-info">
                    <h2><?php echo e($user->name); ?></h2>
                    <p><?php echo e($user->email); ?></p>
                    <div class="profile-badges">
                        <span class="badge badge-<?php echo e($user->role->slug); ?>">
                            <?php echo e(ucfirst($user->role->slug)); ?>

                        </span>
                        <span class="badge badge-<?php echo e($user->status); ?>">
                            <?php echo e(ucfirst($user->status)); ?>

                        </span>
                    </div>
                </div>
            </div>

            <div class="profile-details">
                <div class="detail-section">
                    <h3>Personal Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Full Name</label>
                            <span><?php echo e($user->name); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Email</label>
                            <span><?php echo e($user->email); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Student/Employee ID</label>
                            <span><?php echo e($user->identifier ?? '-'); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Phone</label>
                            <span><?php echo e($user->phone ?? '-'); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Address</label>
                            <span><?php echo e($user->address ?? '-'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Account Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Role</label>
                            <span><?php echo e(ucfirst($user->role->slug)); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Status</label>
                            <span><?php echo e(ucfirst($user->status)); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Created At</label>
                            <span><?php echo e($user->created_at->format('M d, Y')); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Last Login</label>
                            <span><?php echo e($user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never'); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Email Verified</label>
                            <span><?php echo e($user->email_verified_at ? 'Yes' : 'No'); ?></span>
                        </div>
                    </div>
                </div>

                <?php if($user->isStudent()): ?>
                    <div class="detail-section">
                        <h3>Student Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Enrolled Classes</label>
                                <span><?php echo e($user->enrolledClasses()->count()); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Active Enrollments</label>
                                <span><?php echo e($user->enrollments()->active()->count()); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Completed Courses</label>
                                <span><?php echo e($user->enrollments()->completed()->count()); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if($user->isInstructor()): ?>
                    <div class="detail-section">
                        <h3>Instructor Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Assigned Classes</label>
                                <span><?php echo e($user->classesInstructing()->count()); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Total Students</label>
                                <span><?php echo e($user->classesInstructing()->withCount('enrollments')->get()->sum('enrollments_count')); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="detail-section">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <?php if($user->isActive()): ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $user)): ?>
                                <form method="POST" action="<?php echo e(route('admin.users.deactivate', $user)); ?>" class="inline-form">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to deactivate this user?')">
                                        <i class="fa-solid fa-ban"></i>
                                        Deactivate User
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $user)): ?>
                                <form method="POST" action="<?php echo e(route('admin.users.reactivate', $user)); ?>" class="inline-form">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to reactivate this user?')">
                                        <i class="fa-solid fa-check"></i>
                                        Reactivate User
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('resetPassword', $user)): ?>
                            <button type="button" onclick="document.getElementById('resetPasswordModal').classList.add('active')" class="btn btn-warning">
                                <i class="fa-solid fa-key"></i>
                                Reset Password
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('resetPassword', $user)): ?>
<div class="modal" id="resetPasswordModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reset Password</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('resetPasswordModal').classList.remove('active')">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <form method="POST" action="<?php echo e(route('admin.users.reset-password', $user)); ?>" class="modal-body">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" required minlength="8" class="form-control">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" required minlength="8" class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('resetPasswordModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php $__env->startPush('scripts'); ?>
<style>
.user-profile {
    max-width: 800px;
    margin: 0 auto;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 32px;
}

.profile-info h2 {
    margin: 0 0 5px 0;
    color: #333;
}

.profile-info p {
    margin: 0 0 10px 0;
    color: #666;
}

.profile-badges {
    display: flex;
    gap: 10px;
}

.badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge-admin { background: #e74c3c; color: white; }
.badge-instructor { background: #3498db; color: white; }
.badge-student { background: #2ecc71; color: white; }

.badge-active { background: #2ecc71; color: white; }
.badge-inactive { background: #95a5a6; color: white; }
.badge-suspended { background: #e74c3c; color: white; }
.badge-pending { background: #f39c12; color: white; }

.detail-section {
    margin-bottom: 30px;
}

.detail-section h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 16px;
    font-weight: 600;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
    font-weight: 500;
}

.detail-item span {
    font-size: 14px;
    color: #333;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    opacity: 0.9;
}

.btn-secondary {
    background: #f5f5f5;
    color: #333;
}

.btn-secondary:hover {
    background: #e5e5e5;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.btn-success {
    background: #2ecc71;
    color: white;
}

.btn-success:hover {
    background: #27ae60;
}

.btn-warning {
    background: #f39c12;
    color: white;
}

.btn-warning:hover {
    background: #e67e22;
}

.inline-form {
    display: inline;
}
</style>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/admin/users/show.blade.php ENDPATH**/ ?>