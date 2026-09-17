<?php $__env->startSection('title', 'Student Grades'); ?>
<?php ($activeNav = 'gradebook'); ?>
<?php $__env->startSection('content'); ?>
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-user-graduate"></i> <?php echo e($student->name ?? $student->full_name); ?></h3>
        <span class="dash-section-kicker"><?php echo e($class->code); ?> · <?php echo e($class->course?->title ?? 'Gradebook'); ?></span>
    </div>
    <a class="btn btn-secondary" href="<?php echo e(route('instructor.classes.gradebook.index', $class)); ?>"><i class="fa-solid fa-arrow-left"></i> Back to gradebook</a>
</div>
<div class="dash-stats">
    <div class="dash-stat"><span class="dash-stat-label">Earned</span><span class="dash-stat-value"><?php echo e(number_format($earnedPoints, 1)); ?></span></div>
    <div class="dash-stat"><span class="dash-stat-label">Possible</span><span class="dash-stat-value"><?php echo e(number_format($totalPoints, 1)); ?></span></div>
</div>
<section class="dash-panel">
    <table class="dash-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>Score</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $gradeItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php ($grade = $item->grades->first()); ?>
                <tr>
                    <td><?php echo e($item->title); ?></td>
                    <td><?php echo e($item->category?->name ?? '—'); ?></td>
                    <td><?php echo e($grade ? number_format($grade->points, 1) . ' / ' . number_format($item->max_points, 1) : '—'); ?></td>
                    <td><?php echo e($item->is_released ? 'Released' : 'Hidden'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="4" class="search-no-results">No grade items for this class.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\gradebook\student.blade.php ENDPATH**/ ?>