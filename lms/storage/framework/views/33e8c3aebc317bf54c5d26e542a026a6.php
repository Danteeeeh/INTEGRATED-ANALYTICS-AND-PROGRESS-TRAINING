<?php $__env->startSection('title', 'Enrollments'); ?>
<?php
    $activeNav = 'enrollment';
    $pageTitle = 'Enrollment Management';
    $pageIcon = '<i class="fa-solid fa-graduation-cap"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-graduation-cap"></i>
            Enrollment Management
        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card">
        <div class="crud-header">
            <h3>All Enrollments</h3>
            <a href="<?php echo e(route('admin.enrollments.create')); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                New Enrollment
            </a>
        </div>

        <form class="table-toolbar" method="GET" action="<?php echo e(route('admin.enrollments.index')); ?>">
            <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Search student..." aria-label="Search enrollments">
            <select name="student_id" aria-label="Filter student">
                <option value="">All Students</option>
                <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($student->id); ?>" <?php if((string) request('student_id') === (string) $student->id): echo 'selected'; endif; ?>><?php echo e($student->full_name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select name="class_id" aria-label="Filter class">
                <option value="">All Classes</option>
                <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($class->id); ?>" <?php if((string) request('class_id') === (string) $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?> — <?php echo e($class->course?->title); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select name="status" aria-label="Filter status">
                <option value="">All Status</option>
                <?php $__currentLoopData = ['pending' => 'Pending', 'active' => 'Active', 'completed' => 'Completed', 'dropped' => 'Dropped']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($value); ?>" <?php if(request('status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="<?php echo e(route('admin.enrollments.index')); ?>" class="btn btn-secondary">Clear</a>
        </form>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Grade</th>
                    <th>Enrolled Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $enrollments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $enrollment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($enrollment->student->full_name ?? '-'); ?></td>
                        <td><?php echo e($enrollment->class->code ?? '-'); ?> - <?php echo e($enrollment->class->name ?? '-'); ?></td>
                        <td><?php echo e($enrollment->class->course->name ?? '-'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo e($enrollment->status); ?>"><?php echo e(ucfirst($enrollment->status)); ?></span>
                        </td>
                        <td><?php echo e($enrollment->final_grade ?? '-'); ?></td>
                        <td><?php echo e($enrollment->enrolled_at->format('M d, Y')); ?></td>
                        <td class="actions-cell action-buttons">
                            <?php if($enrollment->status === 'pending'): ?>
                                <form method="POST" action="<?php echo e(route('admin.enrollments.approve', $enrollment)); ?>" class="inline-form"><?php echo csrf_field(); ?><button type="submit" class="btn-icon btn-success" title="Approve"><i class="fa-solid fa-check"></i></button></form>
                                <form method="POST" action="<?php echo e(route('admin.enrollments.reject', $enrollment)); ?>" class="inline-form"><?php echo csrf_field(); ?><button type="submit" class="btn-icon btn-danger" title="Reject" onclick="return confirm('Reject this enrollment request?')"><i class="fa-solid fa-xmark"></i></button></form>
                            <?php elseif(in_array($enrollment->status, ['active', 'completed'])): ?>
                                <form method="POST" action="<?php echo e(route('admin.enrollments.drop', $enrollment)); ?>" class="inline-form"><?php echo csrf_field(); ?><button type="submit" class="btn-icon btn-warning" title="Drop" onclick="return confirm('Drop this enrollment?')"><i class="fa-solid fa-user-minus"></i></button></form>
                            <?php endif; ?>
                            <a href="<?php echo e(route('admin.enrollments.show', $enrollment)); ?>" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('admin.enrollments.edit', $enrollment)); ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="<?php echo e(route('admin.enrollments.destroy', $enrollment)); ?>" method="POST" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this enrollment?');">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:24px;color:#aaa;">
                            No enrollments found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if($enrollments->hasPages()): ?>
            <div class="pagination"><?php echo e($enrollments->appends(request()->query())->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\enrollments\index.blade.php ENDPATH**/ ?>