<?php $__env->startSection('title', 'Course Details'); ?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('components.admin-sidebar', ['activeNav' => 'courses'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="page-title-bar">
    <h2 class="page-title">
        <i class="fa-solid fa-book"></i>
        <?php echo e($course->title); ?>

    </h2>
    <div class="page-actions">
        <a href="<?php echo e(route('admin.courses.index')); ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Courses
        </a>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.update')): ?>
            <a href="<?php echo e(route('admin.courses.edit', $course)); ?>" class="btn btn-primary">
                <i class="fa-solid fa-edit"></i>
                Edit Course
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="course-profile">
            <div class="profile-header">
                <div class="course-thumbnail">
                    <?php if($course->thumbnail): ?>
                        <img src="<?php echo e(asset('storage/' . $course->thumbnail)); ?>" alt="<?php echo e($course->title); ?>">
                    <?php else: ?>
                        <div class="placeholder-thumbnail">
                            <i class="fa-solid fa-book"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="course-info">
                    <h2><?php echo e($course->title); ?></h2>
                    <p class="course-code"><?php echo e($course->code); ?></p>
                    <div class="course-badges">
                        <span class="badge badge-<?php echo e($course->status); ?>">
                            <?php echo e(ucfirst($course->status)); ?>

                        </span>
                        <?php if($course->category): ?>
                            <span class="badge badge-category">
                                <?php echo e($course->category->name); ?>

                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="course-details">
                <div class="detail-section">
                    <h3>Basic Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Course Code</label>
                            <span><?php echo e($course->code); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Title</label>
                            <span><?php echo e($course->title); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Category</label>
                            <span><?php echo e($course->category?->name ?? '-'); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Duration</label>
                            <span><?php echo e($course->duration_weeks ? $course->duration_weeks . ' weeks' : '-'); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Academic Period</label>
                            <span><?php echo e($course->academicPeriod?->name ?? '-'); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Status</label>
                            <span><?php echo e(ucfirst($course->status)); ?></span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Description</h3>
                    <div class="detail-content">
                        <?php echo e($course->description ?? 'No description provided.'); ?>

                    </div>
                </div>

                <?php if($course->objectives): ?>
                    <div class="detail-section">
                        <h3>Learning Objectives</h3>
                        <div class="detail-content">
                            <?php echo e($course->objectives); ?>

                        </div>
                    </div>
                <?php endif; ?>

                <?php if($course->prerequisites): ?>
                    <div class="detail-section">
                        <h3>Prerequisites</h3>
                        <div class="detail-content">
                            <?php echo e($course->prerequisites); ?>

                        </div>
                    </div>
                <?php endif; ?>

                <div class="detail-section">
                    <h3>Course Statistics</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Total Classes</label>
                            <span><?php echo e($course->classes()->count()); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Total Modules</label>
                            <span><?php echo e($course->modules()->count()); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Total Lessons</label>
                            <span><?php echo e($course->lessons()->count()); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Total Enrollments</label>
                            <span><?php echo e($course->enrollments()->count()); ?></span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.publish')): ?>
                            <?php if($course->status === 'draft'): ?>
                                <form method="POST" action="<?php echo e(route('admin.courses.publish', $course)); ?>" class="inline-form">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to publish this course?')">
                                        <i class="fa-solid fa-check"></i>
                                        Publish Course
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.archive')): ?>
                            <?php if($course->status === 'published'): ?>
                                <form method="POST" action="<?php echo e(route('admin.courses.archive', $course)); ?>" class="inline-form">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to archive this course?')">
                                        <i class="fa-solid fa-archive"></i>
                                        Archive Course
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('courses.duplicate')): ?>
                            <form method="POST" action="<?php echo e(route('admin.courses.duplicate', $course)); ?>" class="inline-form">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to duplicate this course?')">
                                    <i class="fa-solid fa-copy"></i>
                                    Duplicate Course
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Course Content</h3>
        <div class="card-actions">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('modules.create')): ?>
                <a href="<?php echo e(route('admin.courses.modules.create', $course)); ?>" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Add Module
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if($course->modules()->count() > 0): ?>
            <div class="modules-list">
                <?php $__currentLoopData = $course->modules()->orderBy('position')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="module-item">
                        <div class="module-header">
                            <h4><?php echo e($module->title); ?></h4>
                            <div class="module-stats">
                                <span><?php echo e($module->lessons()->count()); ?> lessons</span>
                            </div>
                        </div>
                        <p><?php echo e(Str::limit($module->description, 100)); ?></p>
                        <div class="module-actions">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('modules.view')): ?>
                                <a href="<?php echo e(route('admin.courses.modules.show', [$course, $module])); ?>" class="btn btn-sm btn-secondary">
                                    <i class="fa-solid fa-eye"></i>
                                    View
                                </a>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('modules.update')): ?>
                                <a href="<?php echo e(route('admin.courses.modules.edit', [$course, $module])); ?>" class="btn btn-sm btn-secondary">
                                    <i class="fa-solid fa-edit"></i>
                                    Edit
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-layer-group"></i>
                <h3>No modules yet</h3>
                <p>Start building your course content by adding modules.</p>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('modules.create')): ?>
                    <a href="<?php echo e(route('admin.courses.modules.create', $course)); ?>" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        Add Module
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<style>
.course-profile {
    max-width: 900px;
    margin: 0 auto;
}

.profile-header {
    display: flex;
    gap: 20px;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.course-thumbnail {
    width: 120px;
    height: 120px;
    border-radius: 8px;
    overflow: hidden;
    background: #f5f5f5;
}

.course-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.placeholder-thumbnail {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 32px;
}

.course-info h2 {
    margin: 0 0 5px 0;
    color: #333;
}

.course-code {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
}

.course-badges {
    display: flex;
    gap: 10px;
}

.badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge-draft { background: #95a5a6; color: white; }
.badge-published { background: #2ecc71; color: white; }
.badge-archived { background: #e74c3c; color: white; }
.badge-category { background: #3498db; color: white; }

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

.detail-content {
    line-height: 1.6;
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

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.inline-form {
    display: inline;
}

.modules-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.module-item {
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 15px;
    background: #fafafa;
}

.module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.module-header h4 {
    margin: 0;
    color: #333;
}

.module-stats {
    font-size: 12px;
    color: #666;
}

.module-item p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 14px;
}

.module-actions {
    display: flex;
    gap: 10px;
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
</style>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\courses\show.blade.php ENDPATH**/ ?>