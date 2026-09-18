<?php $__env->startSection('title', 'Courses'); ?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('components.admin-sidebar', ['activeNav' => 'courses'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="page-title-bar">
    <h2 class="page-title">
        <i class="fa-solid fa-book"></i>
        Courses
    </h2>
    <div class="page-actions">
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.create')): ?>
            <a href="<?php echo e(route('admin.courses.create')); ?>" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Add Course
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" action="<?php echo e(route('admin.courses.index')); ?>" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <input type="text" name="search" placeholder="Search courses..." value="<?php echo e(request('search')); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="draft" <?php echo e(request('status') == 'draft' ? 'selected' : ''); ?>>Draft</option>
                        <option value="published" <?php echo e(request('status') == 'published' ? 'selected' : ''); ?>>Published</option>
                        <option value="archived" <?php echo e(request('status') == 'archived' ? 'selected' : ''); ?>>Archived</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-filter"></i>
                        Filter
                    </button>
                    <a href="<?php echo e(route('admin.courses.index')); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i>
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="card-body">
        <?php if($courses->count() > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Classes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><strong><?php echo e($course->code); ?></strong></td>
                                <td>
                                    <div><?php echo e($course->title); ?></div>
                                    <small class="text-muted"><?php echo e(Str::limit($course->description, 50)); ?></small>
                                </td>
                                <td><?php echo e($course->category?->name ?? '-'); ?></td>
                                <td><?php echo e($course->duration_weeks ? $course->duration_weeks . ' weeks' : '-'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo e($course->status); ?>">
                                        <?php echo e(ucfirst($course->status)); ?>

                                    </span>
                                </td>
                                <td><?php echo e($course->classes()->count()); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.view')): ?>
                                            <a href="<?php echo e(route('admin.courses.show', $course)); ?>" class="btn btn-sm btn-icon" title="View">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.update')): ?>
                                            <a href="<?php echo e(route('admin.courses.edit', $course)); ?>" class="btn btn-sm btn-icon" title="Edit">
                                                <i class="fa-solid fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.publish')): ?>
                                            <?php if($course->status === 'draft'): ?>
                                                <form method="POST" action="<?php echo e(route('admin.courses.publish', $course)); ?>" class="inline-form">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-sm btn-icon btn-success" title="Publish" onclick="return confirm('Are you sure you want to publish this course?')">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.archive')): ?>
                                            <?php if($course->status === 'published'): ?>
                                                <form method="POST" action="<?php echo e(route('admin.courses.archive', $course)); ?>" class="inline-form">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-sm btn-icon btn-warning" title="Archive" onclick="return confirm('Are you sure you want to archive this course?')">
                                                        <i class="fa-solid fa-archive"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.duplicate')): ?>
                                            <form method="POST" action="<?php echo e(route('admin.courses.duplicate', $course)); ?>" class="inline-form">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-sm btn-icon" title="Duplicate" onclick="return confirm('Are you sure you want to duplicate this course?')">
                                                    <i class="fa-solid fa-copy"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            <?php if($courses->hasPages()): ?>
                <div class="pagination">
                    <?php echo e($courses->appends(request()->query())->links()); ?>

                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-book"></i>
                <h3>No courses found</h3>
                <p>Get started by adding your first course.</p>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.create')): ?>
                    <a href="<?php echo e(route('admin.courses.create')); ?>" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        Add Course
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<style>
.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.badge-draft { background: #95a5a6; color: white; }
.badge-published { background: #2ecc71; color: white; }
.badge-archived { background: #e74c3c; color: white; }

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    border: 1px solid #ddd;
    background: white;
    color: #666;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: #f5f5f5;
    color: #333;
}

.btn-success:hover {
    background: #efe;
    color: #2ecc71;
    border-color: #2ecc71;
}

.btn-warning:hover {
    background: #ffe;
    color: #f39c12;
    border-color: #f39c12;
}

.inline-form {
    display: inline;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 20px;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: #666;
}

.empty-state p {
    margin: 0 0 20px 0;
}

.text-muted {
    color: #666;
    font-size: 12px;
}
</style>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/admin/courses/index.blade.php ENDPATH**/ ?>