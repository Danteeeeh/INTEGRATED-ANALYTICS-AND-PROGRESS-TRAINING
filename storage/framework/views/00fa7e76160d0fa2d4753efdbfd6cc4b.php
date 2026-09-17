<?php $__env->startSection('title', 'Instructor Performance'); ?>
<?php ($activeNav = 'reports'); ?>
<?php $__env->startSection('content'); ?>
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-chalkboard-user"></i> Instructor performance</h3>
        <span class="dash-section-kicker">Teaching activity overview</span>
    </div>
    <a class="btn btn-secondary" href="<?php echo e(route('admin.reports.index')); ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="<?php echo e(route('admin.reports.instructor-performance')); ?>">
        <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Search instructor..." aria-label="Search instructors">
        <select name="academic_period_id" aria-label="Filter academic period">
            <option value="">All periods</option>
            <?php $__currentLoopData = $academicPeriods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $period): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($period->id); ?>" <?php if((string) request('academic_period_id') === (string) $period->id): echo 'selected'; endif; ?>><?php echo e($period->code); ?> — <?php echo e($period->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="<?php echo e(route('admin.reports.instructor-performance')); ?>">Clear</a>
        <a class="btn btn-primary" href="<?php echo e(route('admin.reports.export', array_merge(['type' => 'instructor-performance'], request()->query()))); ?>"><i class="fa-solid fa-download"></i> Export</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Instructor</th>
                <th>Email</th>
                <th>Classes</th>
                <th>Students</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $instructors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $instructor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($instructor->name); ?></td>
                    <td><?php echo e($instructor->email); ?></td>
                    <td><?php echo e($instructor->classesInstructing->count()); ?></td>
                    <td><?php echo e($instructor->classesInstructing->sum(fn ($class) => $class->enrollments->count())); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="4" class="search-no-results">No instructors found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php echo e($instructors->appends(request()->query())->links()); ?>

</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\reports\instructor_performance.blade.php ENDPATH**/ ?>