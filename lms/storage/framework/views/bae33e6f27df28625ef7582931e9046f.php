<?php $__env->startSection('title', 'Classes'); ?>
<?php
    $activeNav = 'classes';
    $pageTitle = 'Classes';
    $pageIcon = '<i class="fa-solid fa-grid"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-grid"></i>
            Classes
        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card">
        <div class="crud-header">
            <h3>Classes</h3>
            <a href="<?php echo e(route('admin.classes.create')); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Add Class
            </a>
        </div>

        <form class="table-toolbar" method="GET" action="<?php echo e(route('admin.classes.index')); ?>">
            <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Search classes..." aria-label="Search classes">
            <select name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($course->id); ?>" <?php if((string) request('course_id') === (string) $course->id): echo 'selected'; endif; ?>><?php echo e($course->code); ?> — <?php echo e($course->title); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select name="status" aria-label="Filter status">
                <option value="">All Status</option>
                <?php $__currentLoopData = ['active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($value); ?>" <?php if(request('status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="<?php echo e(route('admin.classes.index')); ?>" class="btn btn-secondary">Clear</a>
        </form>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Instructor</th>
                    <th>Enrolled</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($class->code); ?></td>
                        <td><?php echo e($class->name); ?></td>
                        <td><?php echo e($class->course->name ?? '-'); ?></td>
                        <td><?php echo e($class->instructor->full_name ?? '-'); ?></td>
                        <td>
                            <?php echo e($class->enrolled_count ?? 0); ?> / <?php echo e($class->max_students); ?>

                            <?php if($class->enrolled_count >= $class->max_students): ?>
                                <span class="badge-inactive">Full</span>
                            <?php else: ?>
                                <span class="badge-active">Available</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <a href="<?php echo e(route('admin.classes.show', $class)); ?>" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('admin.classes.edit', $class)); ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="<?php echo e(route('admin.classes.destroy', $class)); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn-icon btn-delete" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No classes found. <a href="<?php echo e(route('admin.classes.create')); ?>" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if($classes->hasPages()): ?>
            <div class="pagination"><?php echo e($classes->appends(request()->query())->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/admin/classes/index.blade.php ENDPATH**/ ?>