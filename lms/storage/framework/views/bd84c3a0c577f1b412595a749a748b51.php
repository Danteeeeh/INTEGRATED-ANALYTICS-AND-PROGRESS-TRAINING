<?php $__env->startSection('title', 'Virtual Class Details'); ?>
<?php
    $activeNav = 'courses';
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
        <h3>Virtual Class Information</h3>

        <div style="padding: 14px 0; display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="<?php echo e(route('admin.virtual_classes.edit', $virtualClass)); ?>" style="padding: 9px 20px; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #fff; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: opacity 0.2s;">
                <i class="fa-solid fa-pen-to-square"></i> Edit Class
            </a>
            <?php if($virtualClass->meeting_url): ?>
                <a href="<?php echo e($virtualClass->meeting_url); ?>" target="_blank" style="padding: 9px 20px; background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #fff; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: opacity 0.2s;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Meeting
                </a>
            <?php endif; ?>
            <form method="POST" action="<?php echo e(route('admin.virtual_classes.destroy', $virtualClass)); ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this virtual class?');">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" style="padding: 9px 20px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #fff; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: opacity 0.2s;">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            </form>
        </div>

        <div class="modal-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>
        <div class="modal-row"><span>Title:</span><span><?php echo e($virtualClass->title); ?></span></div>
        <div class="modal-row"><span>Description:</span><span><?php echo e($virtualClass->description ?? 'No description provided'); ?></span></div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-link"></i> Associations</div>
        <div class="modal-row"><span>Course:</span><span><?php echo e($virtualClass->course ? ($virtualClass->course->code . ' - ' . $virtualClass->course->title) : 'Not assigned'); ?></span></div>
        <div class="modal-row"><span>Class:</span><span><?php echo e($virtualClass->class ? ($virtualClass->class->code . ' - ' . ($virtualClass->class->name ?? '')) : 'Not assigned'); ?></span></div>
        <div class="modal-row"><span>Instructor:</span><span><?php echo e($virtualClass->instructor->name ?? 'Not assigned'); ?> (<?php echo e($virtualClass->instructor->email ?? 'N/A'); ?>)</span></div>
        <div class="modal-row"><span>Created By:</span><span><?php echo e($virtualClass->creator->name ?? 'Unknown'); ?></span></div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-calendar-days"></i> Schedule</div>
        <div class="modal-row"><span>Date:</span><span><?php echo e($virtualClass->meeting_date->format('l, F j, Y')); ?></span></div>
        <div class="modal-row"><span>Start Time:</span><span><?php echo e(\Carbon\Carbon::parse($virtualClass->start_time)->format('g:i A')); ?></span></div>
        <div class="modal-row"><span>End Time:</span><span><?php echo e(\Carbon\Carbon::parse($virtualClass->end_time)->format('g:i A')); ?></span></div>
        <?php
            $start = \Carbon\Carbon::parse($virtualClass->start_time);
            $end = \Carbon\Carbon::parse($virtualClass->end_time);
            $duration = $start->diff($end)->format('%h hr %i min');
        ?>
        <div class="modal-row"><span>Duration:</span><span><?php echo e($duration); ?></span></div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-video"></i> Meeting Details</div>
        <?php
            $providerLabel = match($virtualClass->meeting_provider) {
                'zoom' => 'Zoom',
                'google_meet' => 'Google Meet',
                'microsoft_teams' => 'Microsoft Teams',
                'other' => 'Other',
                default => ucfirst($virtualClass->meeting_provider),
            };
            $providerBadgeStyle = match($virtualClass->meeting_provider) {
                'zoom' => 'background: #e0e7ff; color: #4338ca;',
                'google_meet' => 'background: #fee2e2; color: #dc2626;',
                'microsoft_teams' => 'background: #dbeafe; color: #1e40af;',
                'other' => 'background: #f3f4f6; color: #374151;',
                default => 'background: #f3f4f6; color: #6b7280;',
            };
        ?>
        <div class="modal-row"><span>Provider:</span><span><span style="padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; <?php echo e($providerBadgeStyle); ?>"><?php echo e($providerLabel); ?></span></span></div>
        <div class="modal-row"><span>Meeting URL:</span><span>
            <?php if($virtualClass->meeting_url): ?>
                <a href="<?php echo e($virtualClass->meeting_url); ?>" target="_blank" style="color: #2563eb; text-decoration: none;"><?php echo e($virtualClass->meeting_url); ?> <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.75rem;"></i></a>
            <?php else: ?>
                Not provided
            <?php endif; ?>
        </span></div>
        <div class="modal-row"><span>Meeting ID:</span><span><?php echo e($virtualClass->meeting_id ?? 'Not provided'); ?></span></div>
        <div class="modal-row"><span>Password:</span><span><?php echo e($virtualClass->meeting_password ? '••••••' . $virtualClass->meeting_password : 'Not set'); ?></span></div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-flag"></i> Status</div>
        <?php
            $statusBadgeStyle = match($virtualClass->status) {
                'scheduled' => 'background: #dcfce7; color: #16a34a;',
                'ongoing' => 'background: #fef3c7; color: #d97706;',
                'completed' => 'background: #dbeafe; color: #1e40af;',
                'cancelled' => 'background: #fee2e2; color: #dc2626;',
                default => 'background: #f1f5f9; color: #64748b;',
            };
        ?>
        <div class="modal-row"><span>Status:</span><span><span style="padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; <?php echo e($statusBadgeStyle); ?>"><?php echo e(ucfirst($virtualClass->status)); ?></span></span></div>
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
                    <th>Left At</th>
                    <th>Duration</th>
                    <th>Attendance</th>
                </tr>
            </thead>
            <tbody>
                <?php if($virtualClass->attendees->count() > 0): ?>
                    <?php $__currentLoopData = $virtualClass->attendees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attendee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td style="font-weight: 500;"><?php echo e($attendee->student->name ?? 'Unknown'); ?></td>
                            <td><?php echo e($attendee->student->email ?? '-'); ?></td>
                            <td><?php echo e($attendee->joined_at ? $attendee->joined_at->format('M d, Y g:i A') : '-'); ?></td>
                            <td><?php echo e($attendee->left_at ? $attendee->left_at->format('M d, Y g:i A') : '-'); ?></td>
                            <td><?php echo e($attendee->duration_minutes ? $attendee->duration_minutes . ' min' : '-'); ?></td>
                            <td>
                                <?php
                                    $attBadgeStyle = match($attendee->attendance_status) {
                                        'present' => 'background: #dcfce7; color: #16a34a;',
                                        'late' => 'background: #fef3c7; color: #d97706;',
                                        'absent' => 'background: #fee2e2; color: #dc2626;',
                                        'excused' => 'background: #dbeafe; color: #1e40af;',
                                        default => 'background: #f1f5f9; color: #64748b;',
                                    };
                                ?>
                                <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; <?php echo e($attBadgeStyle); ?>">
                                    <?php echo e(ucfirst($attendee->attendance_status ?? 'registered')); ?>

                                </span>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No attendees yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin: 0 24px 24px;">
        <a href="<?php echo e(route('admin.virtual_classes.index')); ?>" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            ← Back to Virtual Classes
        </a>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\virtual_classes\show.blade.php ENDPATH**/ ?>