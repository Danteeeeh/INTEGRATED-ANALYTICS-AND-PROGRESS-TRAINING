<?php $__env->startSection('title', 'Enrollment Report'); ?>
<?php ($activeNav = 'reports'); ?>
<?php $__env->startSection('content'); ?>
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-user-plus"></i> Enrollment report</h3>
        <span class="dash-section-kicker">Active and completed enrollments</span>
    </div>
    <a class="btn btn-secondary" href="<?php echo e(route('admin.reports.index')); ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="<?php echo e(route('admin.reports.enrollment')); ?>">
        <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Search student..." aria-label="Search enrollments">
        <select name="status" aria-label="Filter status">
            <option value="">All statuses</option>
            <option value="active" <?php if(request('status')==='active'): echo 'selected'; endif; ?>>Active</option>
            <option value="completed" <?php if(request('status')==='completed'): echo 'selected'; endif; ?>>Completed</option>
            <option value="dropped" <?php if(request('status')==='dropped'): echo 'selected'; endif; ?>>Dropped</option>
            <option value="pending" <?php if(request('status')==='pending'): echo 'selected'; endif; ?>>Pending</option>
        </select>
        <select name="course_id" aria-label="Filter course">
            <option value="">All courses</option>
            <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($course->id); ?>" <?php if((string) request('course_id') === (string) $course->id): echo 'selected'; endif; ?>><?php echo e($course->code); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="<?php echo e(route('admin.reports.enrollment')); ?>">Clear</a>
        <a class="btn btn-primary" href="<?php echo e(route('admin.reports.export', array_merge(['type' => 'enrollment'], request()->query()))); ?>"><i class="fa-solid fa-download"></i> Export</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Course</th>
                <th>Status</th>
                <th>Grade</th>
                <th>Enrolled</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $enrollments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $enrollment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($enrollment->student?->name ?? $enrollment->student?->email ?? '—'); ?></td>
                    <td><?php echo e($enrollment->class?->code ?? '—'); ?></td>
                    <td><?php echo e($enrollment->class?->course?->title ?? '—'); ?></td>
                    <td><span class="dash-meta-chip <?php echo e($enrollment->status === 'active' ? 'm-green' : 'm-gray'); ?>"><?php echo e(ucfirst($enrollment->status)); ?></span></td>
                    <td><?php echo e($enrollment->final_grade !== null ? number_format($enrollment->final_grade, 1) : '—'); ?></td>
                    <td><?php echo e($enrollment->enrolled_at?->format('M d, Y') ?? '—'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="search-no-results">No enrollments found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php echo e($enrollments->appends(request()->query())->links()); ?>

</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\reports\enrollment.blade.php ENDPATH**/ ?>