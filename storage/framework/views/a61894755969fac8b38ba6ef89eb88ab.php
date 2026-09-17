<?php $__env->startSection('title', $virtualClass->title . ' - ' . $class->name); ?>
<?php
    $activeNav = 'classes';
    $pageTitle = 'Virtual Class Details';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            <?php echo e($virtualClass->title); ?>

        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="form-card">
        <h3>Virtual Class Details</h3>

        <div style="margin-bottom: 16px; display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if($virtualClass->status === 'scheduled'): ?>
                <form method="POST" action="<?php echo e(route('instructor.classes.virtual_classes.start', [$class, $virtualClass])); ?>" onsubmit="return confirm('Start this virtual class now? This will change status to Ongoing.');">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn-submit" style="display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-play"></i>
                        Start Class
                    </button>
                </form>
            <?php endif; ?>

            <?php if($virtualClass->meeting_url): ?>
                <a href="<?php echo e($virtualClass->meeting_url); ?>" target="_blank" class="btn-add" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    Open Meeting
                </a>
            <?php endif; ?>

            <span class="btn btn-secondary" aria-disabled="true" title="Virtual class editing is not available from this view">
                <i class="fa-solid fa-lock"></i>
                Edit unavailable
            </span>
        </div>

        <div class="modal-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>
        <div class="modal-row"><span>Title:</span><span><?php echo e($virtualClass->title); ?></span></div>
        <div class="modal-row"><span>Class:</span><span><?php echo e($class->code); ?> &mdash; <?php echo e($class->name); ?></span></div>
        <div class="modal-row"><span>Course:</span><span><?php echo e($virtualClass->course->code ?? '-'); ?> &mdash; <?php echo e(($virtualClass->course->name ?? '-')); ?></span></div>
        <div class="modal-row"><span>Instructor:</span><span><?php echo e($virtualClass->instructor->full_name ?? '-'); ?></span></div>
        <div class="modal-row">
            <span>Status:</span>
            <span>
                <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600;
                    <?php echo e($virtualClass->status === 'ongoing' ? 'background: #dcfce7; color: #16a34a;' :
                       $virtualClass->status === 'scheduled' ? 'background: #dbeafe; color: #1e40af;' :
                       $virtualClass->status === 'completed' ? 'background: #f1f5f9; color: #64748b;' :
                       'background: #fee2e2; color: #dc2626;'); ?>">
                    <?php echo e(ucfirst($virtualClass->status)); ?>

                </span>
            </span>
        </div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-calendar-clock"></i> Schedule</div>
        <div class="modal-row"><span>Date:</span><span><?php echo e($virtualClass->meeting_date->format('l, F j, Y')); ?></span></div>
        <div class="modal-row"><span>Time:</span><span><?php echo e(\Carbon\Carbon::parse($virtualClass->start_time)->format('g:i A')); ?> &ndash; <?php echo e(\Carbon\Carbon::parse($virtualClass->end_time)->format('g:i A')); ?></span></div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-plug"></i> Meeting Details</div>
        <div class="modal-row">
            <span>Provider:</span>
            <span>
                <?php switch($virtualClass->meeting_provider):
                    case ('zoom'): ?>
                        <i class="fa-solid fa-video" style="color:#2D8CFF;"></i> Zoom
                        <?php break; ?>
                    <?php case ('google_meet'): ?>
                        <i class="fa-solid fa-video" style="color:#EA4335;"></i> Google Meet
                        <?php break; ?>
                    <?php case ('microsoft_teams'): ?>
                        <i class="fa-solid fa-video" style="color:#6264A7;"></i> Microsoft Teams
                        <?php break; ?>
                    <?php default: ?>
                        <i class="fa-solid fa-video"></i> Other
                <?php endswitch; ?>
            </span>
        </div>
        <?php if($virtualClass->meeting_url): ?>
            <div class="modal-row"><span>URL:</span><span><a href="<?php echo e($virtualClass->meeting_url); ?>" target="_blank" style="color:#2563eb;"><?php echo e($virtualClass->meeting_url); ?></a></span></div>
        <?php endif; ?>
        <?php if($virtualClass->meeting_id): ?>
            <div class="modal-row"><span>Meeting ID:</span><span><code style="background:#f1f5f9; padding:2px 8px; border-radius:4px;"><?php echo e($virtualClass->meeting_id); ?></code></span></div>
        <?php endif; ?>
        <?php if($virtualClass->meeting_password): ?>
            <div class="modal-row"><span>Password:</span><span><code style="background:#f1f5f9; padding:2px 8px; border-radius:4px;"><?php echo e($virtualClass->meeting_password); ?></code></span></div>
        <?php endif; ?>

        <?php if($virtualClass->description): ?>
            <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-align-left"></i> Description / Agenda</div>
            <div class="modal-row"><span></span><span style="white-space: pre-wrap;"><?php echo e($virtualClass->description); ?></span></div>
        <?php endif; ?>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Attendees (<?php echo e($virtualClass->attendees->count()); ?>)</h3>
        </div>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Joined At</th>
                    <th>Attendance</th>
                </tr>
            </thead>
            <tbody>
                <?php if($virtualClass->attendees->count() > 0): ?>
                    <?php $__currentLoopData = $virtualClass->attendees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attendee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($attendee->student->full_name ?? 'User #' . $attendee->user_id); ?></td>
                            <td><?php echo e($attendee->student->email ?? '-'); ?></td>
                            <td><?php echo e($attendee->joined_at ? $attendee->joined_at->format('M d, Y g:i A') : '-'); ?></td>
                            <td>
                                <?php
                                    $statusColors = [
                                        'present' => 'background: #dcfce7; color: #16a34a;',
                                        'late' => 'background: #fef3c7; color: #d97706;',
                                        'absent' => 'background: #fee2e2; color: #dc2626;',
                                        'excused' => 'background: #dbeafe; color: #1e40af;',
                                    ];
                                ?>
                                <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; <?php echo e($statusColors[$attendee->attendance_status] ?? 'background: #f1f5f9; color: #64748b;'); ?>">
                                    <?php echo e(ucfirst($attendee->attendance_status ?? 'Unknown')); ?>

                                </span>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:#aaa;">
                            No attendees have joined this virtual class yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin: 0 24px 24px;">
        <a href="<?php echo e(route('instructor.classes.virtual_classes.index', $class)); ?>" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            &larr; Back to Virtual Classes
        </a>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\virtual_classes\show.blade.php ENDPATH**/ ?>