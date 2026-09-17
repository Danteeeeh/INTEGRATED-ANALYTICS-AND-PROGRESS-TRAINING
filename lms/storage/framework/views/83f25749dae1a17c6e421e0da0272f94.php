<?php $__env->startSection('title', 'Class Gradebook'); ?>
<?php ($activeNav = 'gradebook'); ?>
<?php $__env->startSection('content'); ?>
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-graduation-cap"></i> <?php echo e($class->code); ?> gradebook</h3>
        <span class="dash-section-kicker"><?php echo e($class->course?->title ?? 'Class grades'); ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="<?php echo e(route('admin.gradebook.index')); ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <a class="btn btn-secondary" href="<?php echo e(route('admin.gradebook.grades.history', ['class_id' => $class->id])); ?>"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
    </div>
</div>
<section class="dash-panel">
    <table class="dash-table">
        <thead>
            <tr>
                <th>Student</th>
                <?php $__currentLoopData = $gradeItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <th><?php echo e($item->title); ?><br><small><?php echo e($item->max_points); ?> pts</small></th>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <th>Released</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($student?->name ?? $student?->full_name ?? 'Student'); ?></td>
                    <?php $__currentLoopData = $gradeItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php ($grade = $grades->get($item->id . '-' . $student->id)); ?>
                        <td><?php echo e($grade ? number_format($grade->score_percent, 1) . '%' : '—'); ?></td>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <td><?php echo e($gradeItems->where('is_released', true)->count()); ?>/<?php echo e($gradeItems->count()); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="<?php echo e(2 + $gradeItems->count()); ?>" class="search-no-results">No active students in this class.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\gradebook\class-view.blade.php ENDPATH**/ ?>