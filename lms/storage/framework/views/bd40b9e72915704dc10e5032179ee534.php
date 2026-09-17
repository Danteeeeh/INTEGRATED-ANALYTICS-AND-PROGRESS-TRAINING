<?php $__env->startSection('title', 'Virtual Classes - ' . $class->name); ?>
<?php
    $activeNav = 'classes';
    $pageTitle = 'Virtual Classes';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            Virtual Classes &mdash; <?php echo e($class->code); ?>

        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card">
        <div class="crud-header">
            <h3><?php echo e($class->name); ?> &mdash; Virtual Classes</h3>
            <a href="<?php echo e(route('instructor.classes.virtual_classes.create', $class)); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                New Virtual Class
            </a>
        </div>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date/Time</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Attendees</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $virtualClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($vc->title); ?></td>
                        <td>
                            <?php echo e($vc->meeting_date->format('M d, Y')); ?>

                            <br>
                            <small style="color:#64748b;"><?php echo e(\Carbon\Carbon::parse($vc->start_time)->format('g:i A')); ?> &ndash; <?php echo e(\Carbon\Carbon::parse($vc->end_time)->format('g:i A')); ?></small>
                        </td>
                        <td>
                            <?php switch($vc->meeting_provider):
                                case ('zoom'): ?>
                                    <i class="fa-solid fa-video" style="color:#2D8CFF;"></i> Zoom
                                    <?php break; ?>
                                <?php case ('google_meet'): ?>
                                    <i class="fa-solid fa-video" style="color:#EA4335;"></i> Google Meet
                                    <?php break; ?>
                                <?php case ('microsoft_teams'): ?>
                                    <i class="fa-solid fa-video" style="color:#6264A7;"></i> MS Teams
                                    <?php break; ?>
                                <?php default: ?>
                                    <i class="fa-solid fa-video"></i> Other
                            <?php endswitch; ?>
                        </td>
                        <td>
                            <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600;
                                <?php echo e($vc->status === 'ongoing' ? 'background: #dcfce7; color: #16a34a;' :
                                   $vc->status === 'scheduled' ? 'background: #dbeafe; color: #1e40af;' :
                                   $vc->status === 'completed' ? 'background: #f1f5f9; color: #64748b;' :
                                   'background: #fee2e2; color: #dc2626;'); ?>">
                                <?php echo e(ucfirst($vc->status)); ?>

                            </span>
                        </td>
                        <td>
                            <?php echo e($vc->attendees->count()); ?>

                            <?php if($class->enrollments && $class->enrollments->count() > 0): ?>
                                / <?php echo e($class->enrollments->count()); ?>

                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <?php if($vc->status === 'scheduled'): ?>
                                <form method="POST" action="<?php echo e(route('instructor.classes.virtual_classes.start', [$class, $vc])); ?>" style="display:inline;" onsubmit="return confirm('Start this virtual class?');">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn-icon btn-edit" title="Start">
                                        <i class="fa-solid fa-play"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="<?php echo e(route('instructor.classes.virtual_classes.show', [$class, $vc])); ?>" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No virtual classes scheduled yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if($virtualClasses->hasPages()): ?>
            <div style="padding: 16px 24px; border-top: 1px solid var(--border-color, #e2e8f0);">
                <?php echo e($virtualClasses->links()); ?>

            </div>
        <?php endif; ?>
    </div>

    <div style="margin: 0 24px 24px;">
        <a href="<?php echo e(route('instructor.classes.show', $class)); ?>" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            &larr; Back to Class
        </a>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\virtual_classes\index.blade.php ENDPATH**/ ?>