<?php $__env->startSection('title', 'Attendance Report'); ?>
<?php ($activeNav = 'reports'); ?>
<?php $__env->startSection('content'); ?>
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-calendar-check"></i> Attendance report</h3>
        <span class="dash-section-kicker">Attendance records and rates</span>
    </div>
    <a class="btn btn-secondary" href="<?php echo e(route('admin.reports.index')); ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="<?php echo e(route('admin.reports.attendance')); ?>">
        <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Search student..." aria-label="Search attendance">
        <select name="status" aria-label="Filter status">
            <option value="">All statuses</option>
            <option value="present" <?php if(request('status')==='present'): echo 'selected'; endif; ?>>Present</option>
            <option value="late" <?php if(request('status')==='late'): echo 'selected'; endif; ?>>Late</option>
            <option value="absent" <?php if(request('status')==='absent'): echo 'selected'; endif; ?>>Absent</option>
            <option value="excused" <?php if(request('status')==='excused'): echo 'selected'; endif; ?>>Excused</option>
        </select>
        <select name="class_id" aria-label="Filter class">
            <option value="">All classes</option>
            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($class->id); ?>" <?php if((string) request('class_id') === (string) $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="<?php echo e(route('admin.reports.attendance')); ?>">Clear</a>
        <a class="btn btn-primary" href="<?php echo e(route('admin.reports.export', array_merge(['type' => 'attendance'], request()->query()))); ?>"><i class="fa-solid fa-download"></i> Export</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Student</th>
                <th>Class</th>
                <th>Status</th>
                <th>Session</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $attendanceRecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($record->attendance_date?->format('M d, Y') ?? '—'); ?></td>
                    <td><?php echo e($record->student?->name ?? '—'); ?></td>
                    <td><?php echo e($record->class?->code ?? '—'); ?></td>
                    <td><span class="dash-meta-chip <?php echo e($record->status === 'present' ? 'm-green' : ($record->status === 'absent' ? 'm-rose' : 'm-amber')); ?>"><?php echo e(ucfirst($record->status)); ?></span></td>
                    <td><?php echo e($record->session_title ?? '—'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="search-no-results">No attendance records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php echo e($attendanceRecords->appends(request()->query())->links()); ?>

</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\reports\attendance.blade.php ENDPATH**/ ?>