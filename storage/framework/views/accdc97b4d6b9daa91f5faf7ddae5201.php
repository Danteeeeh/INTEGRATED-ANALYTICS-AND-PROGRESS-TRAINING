<?php $__env->startSection('title', 'Course Completion'); ?>
<?php ($activeNav = 'reports'); ?>
<?php $__env->startSection('content'); ?>
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-certificate"></i> Course completion</h3>
        <span class="dash-section-kicker">Completion progress by course</span>
    </div>
    <a class="btn btn-secondary" href="<?php echo e(route('admin.reports.index')); ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="<?php echo e(route('admin.reports.course-completion')); ?>">
        <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Search student..." aria-label="Search completions">
        <select name="course_id" aria-label="Filter course">
            <option value="">All courses</option>
            <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($course->id); ?>" <?php if((string) request('course_id') === (string) $course->id): echo 'selected'; endif; ?>><?php echo e($course->code); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="academic_period_id" aria-label="Filter academic period">
            <option value="">All periods</option>
            <?php $__currentLoopData = $academicPeriods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $period): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($period->id); ?>" <?php if((string) request('academic_period_id') === (string) $period->id): echo 'selected'; endif; ?>><?php echo e($period->code); ?> — <?php echo e($period->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="<?php echo e(route('admin.reports.course-completion')); ?>">Clear</a>
        <a class="btn btn-primary" href="<?php echo e(route('admin.reports.export', array_merge(['type' => 'course-completion'], request()->query()))); ?>"><i class="fa-solid fa-download"></i> Export</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Course</th>
                <th>Class</th>
                <th>Progress</th>
                <th>Final grade</th>
                <th>Completed</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $completions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $completion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($completion->student?->name ?? '—'); ?></td>
                    <td><?php echo e($completion->class?->course?->title ?? '—'); ?></td>
                    <td><?php echo e($completion->class?->code ?? '—'); ?></td>
                    <td><?php echo e(number_format($completion->completion_percent ?? 0, 1)); ?>%</td>
                    <td><?php echo e($completion->final_grade !== null ? number_format($completion->final_grade, 1) : '—'); ?></td>
                    <td><?php echo e($completion->completed_at?->format('M d, Y') ?? '—'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="search-no-results">No completions found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php echo e($completions->appends(request()->query())->links()); ?>

</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\reports\course_completion.blade.php ENDPATH**/ ?>