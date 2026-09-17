<?php $__env->startSection('title', 'Grade History'); ?>
<?php ($activeNav = 'gradebook'); ?>
<?php $__env->startSection('content'); ?>
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Grade history</h3>
        <span class="dash-section-kicker">Track grade changes by class and student</span>
    </div>
    <a class="btn btn-secondary" href="<?php echo e(route('admin.gradebook.index')); ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET">
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
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>When</th>
                <th>Student</th>
                <th>Item</th>
                <th>Changed by</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $gradeHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($history->changed_at?->diffForHumans() ?? $history->created_at?->diffForHumans()); ?></td>
                    <td><?php echo e($history->grade?->student?->name ?? '—'); ?></td>
                    <td><?php echo e($history->grade?->item?->title ?? '—'); ?></td>
                    <td><?php echo e($history->changedBy?->name ?? '—'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="4" class="search-no-results">No grade history found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php echo e($gradeHistory->links()); ?>

</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\gradebook\grade-history.blade.php ENDPATH**/ ?>