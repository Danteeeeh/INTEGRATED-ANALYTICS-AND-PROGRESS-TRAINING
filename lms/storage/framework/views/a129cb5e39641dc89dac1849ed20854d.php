<?php $__env->startSection('title', 'Grade Distribution'); ?>
<?php ($activeNav = 'reports'); ?>
<?php $__env->startSection('content'); ?>
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-chart-pie"></i> Grade distribution</h3>
        <span class="dash-section-kicker">Grade ranges and distribution</span>
    </div>
    <a class="btn btn-secondary" href="<?php echo e(route('admin.reports.index')); ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="<?php echo e(route('admin.reports.grade-distribution')); ?>">
        <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Search student..." aria-label="Search grades">
        <select name="course_id" aria-label="Filter course">
            <option value="">All courses</option>
            <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($course->id); ?>" <?php if((string) request('course_id') === (string) $course->id): echo 'selected'; endif; ?>><?php echo e($course->code); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="class_id" aria-label="Filter class">
            <option value="">All classes</option>
            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($class->id); ?>" <?php if((string) request('class_id') === (string) $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="student_id" aria-label="Filter student">
            <option value="">All students</option>
            <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($student->id); ?>" <?php if((string) request('student_id') === (string) $student->id): echo 'selected'; endif; ?>><?php echo e($student->full_name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="letter_grade" aria-label="Filter letter grade">
            <option value="">All grades</option>
            <?php $__currentLoopData = ['A', 'B', 'C', 'D', 'F']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grade): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($grade); ?>" <?php if(request('letter_grade') === $grade): echo 'selected'; endif; ?>><?php echo e($grade); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="<?php echo e(route('admin.reports.grade-distribution')); ?>">Clear</a>
        <a class="btn btn-primary" href="<?php echo e(route('admin.reports.export', array_merge(['type' => 'grade-distribution'], request()->query()))); ?>"><i class="fa-solid fa-download"></i> Export</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Item</th>
                <th>Percent</th>
                <th>Letter</th>
                <th>Graded</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $grades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grade): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($grade->student?->name ?? '—'); ?></td>
                    <td><?php echo e($grade->item?->title ?? '—'); ?></td>
                    <td><?php echo e($grade->score_percent !== null ? number_format($grade->score_percent, 1) . '%' : '—'); ?></td>
                    <td><?php echo e($grade->letter_grade ?? '—'); ?></td>
                    <td><?php echo e($grade->graded_at?->format('M d, Y') ?? '—'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="search-no-results">No grades found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php echo e($grades->appends(request()->query())->links()); ?>

</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\reports\grade_distribution.blade.php ENDPATH**/ ?>