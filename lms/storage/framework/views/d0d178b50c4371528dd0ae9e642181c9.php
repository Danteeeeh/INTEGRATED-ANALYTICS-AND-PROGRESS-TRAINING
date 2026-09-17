<?php $__env->startSection('title', 'Gradebook'); ?>
<?php ($activeNav = 'gradebook'); ?>
<?php $__env->startSection('content'); ?>
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-graduation-cap"></i> Gradebook</h3>
        <span class="dash-section-kicker">Open a class to view and release grades</span>
    </div>
    <a class="btn btn-secondary" href="<?php echo e(route('admin.gradebook.grades.history')); ?>"><i class="fa-solid fa-clock-rotate-left"></i> Grade history</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="<?php echo e(route('admin.gradebook.index')); ?>">
        <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Search class or course..." aria-label="Search gradebook">
        <select name="course_id" aria-label="Filter course">
            <option value="">All courses</option>
            <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($course->id); ?>" <?php if((string) request('course_id') === (string) $course->id): echo 'selected'; endif; ?>><?php echo e($course->code); ?> — <?php echo e($course->title); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="<?php echo e(route('admin.gradebook.index')); ?>">Clear</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Class</th>
                <th>Course</th>
                <th>Instructor</th>
                <th>Students</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($class->code); ?></td>
                    <td><?php echo e($class->course?->title ?? '—'); ?></td>
                    <td><?php echo e($class->instructor?->name ?? $class->instructor?->full_name ?? '—'); ?></td>
                    <td><?php echo e($class->enrollments->where('status', 'active')->count()); ?></td>
                    <td class="action-buttons">
                        <a class="btn btn-sm btn-primary" href="<?php echo e(route('admin.gradebook.class', $class)); ?>"><i class="fa-solid fa-table"></i> Open</a>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="search-no-results">No classes found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php echo e($classes->appends(request()->query())->links()); ?>

</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\gradebook\index.blade.php ENDPATH**/ ?>