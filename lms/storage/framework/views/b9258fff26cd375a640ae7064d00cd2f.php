<?php $__env->startSection('title', 'Student Details'); ?>
<?php
    $activeNav = 'students';
    $pageTitle = 'Student Details';
    $pageIcon = '<i class="fa-solid fa-user-graduate"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-user-graduate"></i>
            Student Details
        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="form-card">
        <h3>Student Information</h3>
        <div class="modal-section-title"><i class="fa-solid fa-user"></i> Personal Information</div>
        <div class="modal-row"><span>Name:</span><span><?php echo e($student->full_name); ?></span></div>
        <div class="modal-row"><span>Email:</span><span><?php echo e($student->email); ?></span></div>
        <div class="modal-row"><span>Phone:</span><span><?php echo e($student->phone_number ?? 'Not provided'); ?></span></div>
        <div class="modal-row"><span>Address:</span><span><?php echo e($student->address ?? 'Not provided'); ?></span></div>
        <div class="modal-row"><span>Status:</span><span>
            <?php if($student->status === 'active'): ?>
                <span class="badge-active">Active</span>
            <?php else: ?>
                <span class="badge-inactive">Inactive</span>
            <?php endif; ?>
        </span></div>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Enrollments (<?php echo e($student->enrollments->count()); ?>)</h3>
        </div>
        
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Course</th>
                    <th>Instructor</th>
                    <th>Status</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php if($student->enrollments->count() > 0): ?>
                    <?php $__currentLoopData = $student->enrollments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $enrollment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($enrollment->class->code ?? '-'); ?> - <?php echo e($enrollment->class->name ?? '-'); ?></td>
                            <td><?php echo e($enrollment->class->course->name ?? '-'); ?></td>
                            <td><?php echo e($enrollment->class->instructor->full_name ?? '-'); ?></td>
                            <td>
                                <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600;
                                    <?php echo e($enrollment->status === 'active' ? 'background: #dcfce7; color: #16a34a;' : 
                                       ($enrollment->status === 'completed' ? 'background: #dbeafe; color: #1e40af;' : 
                                       ($enrollment->status === 'dropped' ? 'background: #fee2e2; color: #dc2626;' : 'background: #f1f5f9; color: #64748b;'))); ?>>
                                    <?php echo e(ucfirst($enrollment->status)); ?>

                                </span>
                            </td>
                            <td><?php echo e($enrollment->final_grade ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:24px;color:#aaa;">
                            No enrollments found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin: 0 24px 24px;">
        <a href="<?php echo e(route('admin.students.edit', $student)); ?>" class="btn-add" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; margin-right: 8px;">
            <i class="fa-solid fa-pen-to-square"></i> Edit Student
        </a>
        <a href="<?php echo e(route('admin.students.index')); ?>" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            ← Back to Students
        </a>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/admin/students/show.blade.php ENDPATH**/ ?>