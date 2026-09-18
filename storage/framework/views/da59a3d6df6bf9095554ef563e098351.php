<?php $__env->startSection('title', 'Users'); ?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('components.admin-sidebar', ['activeNav' => 'users'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $activeNav = 'users'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Users','subtitle' => 'Manage all user accounts, roles, and access.','icon' => 'fa-users']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Users','subtitle' => 'Manage all user accounts, roles, and access.','icon' => 'fa-users']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.create')): ?>
                <a href="<?php echo e(route('admin.users.create')); ?>" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Add User
                </a>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.import')): ?>
                <button type="button" onclick="document.getElementById('importModal').classList.add('active')" class="btn btn-secondary">
                    <i class="fa-solid fa-upload"></i> Import
                </button>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.export')): ?>
                <a href="<?php echo e(route('admin.users.export', request()->query())); ?>" class="btn btn-secondary">
                    <i class="fa-solid fa-download"></i> Export
                </a>
            <?php endif; ?>
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

    <div class="user-panel">
        <form method="GET" action="<?php echo e(route('admin.users.index')); ?>" class="user-toolbar">
            <input type="text" name="search" placeholder="Search users..." value="<?php echo e(request('search')); ?>" class="form-control" aria-label="Search users">
            <select name="role_slug" class="form-control" aria-label="Filter role">
                <option value="">All Roles</option>
                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($role->slug); ?>" <?php echo e(request('role_slug') == $role->slug ? 'selected' : ''); ?>>
                        <?php echo e(ucfirst($role->slug)); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select name="status" class="form-control" aria-label="Filter status">
                <option value="">All Status</option>
                <option value="active" <?php echo e(request('status') == 'active' ? 'selected' : ''); ?>>Active</option>
                <option value="inactive" <?php echo e(request('status') == 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                <option value="suspended" <?php echo e(request('status') == 'suspended' ? 'selected' : ''); ?>>Suspended</option>
                <option value="pending" <?php echo e(request('status') == 'pending' ? 'selected' : ''); ?>>Pending</option>
            </select>
            <select name="sort" class="form-control" aria-label="Sort users">
                <option value="newest" <?php if(request('sort', 'newest') === 'newest'): echo 'selected'; endif; ?>>Newest first</option>
                <option value="oldest" <?php if(request('sort') === 'oldest'): echo 'selected'; endif; ?>>Oldest first</option>
                <option value="name" <?php if(request('sort') === 'name'): echo 'selected'; endif; ?>>Name A–Z</option>
                <option value="last_login" <?php if(request('sort') === 'last_login'): echo 'selected'; endif; ?>>Recent login</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
            <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            <?php if($users->count() > 0): ?>
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Identifier</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($user->id); ?></td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar"><?php echo e(strtoupper(substr($user->name, 0, 1))); ?></div>
                                            <div>
                                                <div class="user-name"><?php echo e($user->name); ?></div>
                                                <div class="user-email"><?php echo e($user->email); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo e($user->email); ?></td>
                                    <td><?php echo e($user->identifier ?? '-'); ?></td>
                                    <td><span class="user-status"><?php echo e(ucfirst($user->role->slug ?? 'user')); ?></span></td>
                                    <td><?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($user->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($user->status).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $attributes = $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $component = $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?></td>
                                    <td><?php echo e($user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never'); ?></td>
                                    <td>
                                        <div class="user-actions">
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $user)): ?>
                                                <a href="<?php echo e(route('admin.users.show', $user)); ?>" class="btn btn-icon" title="View">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $user)): ?>
                                                <a href="<?php echo e(route('admin.users.edit', $user)); ?>" class="btn btn-icon" title="Edit">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if($user->isActive()): ?>
                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $user)): ?>
                                                    <form method="POST" action="<?php echo e(route('admin.users.deactivate', $user)); ?>" class="inline-form">
                                                        <?php echo csrf_field(); ?>
                                                        <button type="submit" class="btn btn-icon btn-danger" title="Deactivate" onclick="return confirm('Are you sure you want to deactivate this user?')">
                                                            <i class="fa-solid fa-ban"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $user)): ?>
                                                    <form method="POST" action="<?php echo e(route('admin.users.reactivate', $user)); ?>" class="inline-form">
                                                        <?php echo csrf_field(); ?>
                                                        <button type="submit" class="btn btn-icon btn-success" title="Reactivate" onclick="return confirm('Are you sure you want to reactivate this user?')">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('resetPassword', $user)): ?>
                                                <button type="button" onclick="document.getElementById('resetPasswordModal-<?php echo e($user->id); ?>').classList.add('active')" class="btn btn-icon" title="Reset Password">
                                                    <i class="fa-solid fa-key"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <?php if($users->hasPages()): ?>
                    <div class="pagination">
                        <?php echo e($users->appends(request()->query())->links()); ?>

                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-users','title' => 'No users found','description' => 'Get started by adding your first user.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-users','title' => 'No users found','description' => 'Get started by adding your first user.']); ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.create')): ?>
                         <?php $__env->slot('action', null, []); ?> 
                            <a href="<?php echo e(route('admin.users.create')); ?>" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Add User
                            </a>
                         <?php $__env->endSlot(); ?>
                    <?php endif; ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb3aac3110999f7435f4950a3fe8df251)): ?>
<?php $attributes = $__attributesOriginalb3aac3110999f7435f4950a3fe8df251; ?>
<?php unset($__attributesOriginalb3aac3110999f7435f4950a3fe8df251); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb3aac3110999f7435f4950a3fe8df251)): ?>
<?php $component = $__componentOriginalb3aac3110999f7435f4950a3fe8df251; ?>
<?php unset($__componentOriginalb3aac3110999f7435f4950a3fe8df251); ?>
<?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Import Modal -->
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.import')): ?>
<div class="modal" id="importModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import Users</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('importModal').classList.remove('active')">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <form method="POST" action="<?php echo e(route('admin.users.import')); ?>" enctype="multipart/form-data" class="modal-body">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>CSV File</label>
                <input type="file" name="file" accept=".csv" required class="form-control">
                <small class="form-text">Upload a CSV file with columns: first_name, last_name, email, identifier, role_slug</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('importModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Reset Password Modals -->
<?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('resetPassword', $user)): ?>
        <div class="modal" id="resetPasswordModal-<?php echo e($user->id); ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Reset Password</h3>
                    <button type="button" class="modal-close" onclick="document.getElementById('resetPasswordModal-<?php echo e($user->id); ?>').classList.remove('active')">
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
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('resetPasswordModal-<?php echo e($user->id); ?>').classList.remove('active')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__env->startPush('styles'); ?>
<style>
.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    flex: 0 0 auto;
}

.user-name {
    font-weight: 600;
    color: var(--bcp-ink);
}

.user-email {
    font-size: 0.78rem;
    color: var(--bcp-muted);
}
</style>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\users\index.blade.php ENDPATH**/ ?>