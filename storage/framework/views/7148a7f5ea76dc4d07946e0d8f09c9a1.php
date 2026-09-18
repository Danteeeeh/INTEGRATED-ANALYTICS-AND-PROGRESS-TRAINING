<?php $__env->startSection('title', 'Virtual Classes'); ?>
<?php
    $activeNav = 'classes';
    $pageTitle = $class->name . ' — Virtual Classes';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            Virtual Classes — <?php echo e($class->name); ?>

        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="form-card">
        <h3>Class Information</h3>
        <div class="modal-section-title"><i class="fa-solid fa-info-circle"></i> Overview</div>
        <div class="modal-row"><span>Code:</span><span><?php echo e($class->code); ?></span></div>
        <div class="modal-row"><span>Course:</span><span><?php echo e($class->course->code ?? '-'); ?> — <?php echo e(($class->course->name ?? '-')); ?></span></div>
        <div class="modal-row"><span>Instructor:</span><span><?php echo e($class->instructor->full_name ?? '-'); ?></span></div>
        <div class="modal-row"><span>Schedule:</span><span><?php echo e($class->schedule ?? 'TBD'); ?></span></div>
        <div class="modal-row"><span>Enrollment Status:</span><span>
            <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600;
                <?php echo e($enrollment->status === 'active' ? 'background: #dcfce7; color: #16a34a;' :
                   ($enrollment->status === 'completed' ? 'background: #dbeafe; color: #1e40af;' :
                   ($enrollment->status === 'dropped' ? 'background: #fee2e2; color: #dc2626;' : 'background: #f1f5f9; color: #64748b;'))); ?>">
                <?php echo e(ucfirst($enrollment->status)); ?>

            </span>
        </span></div>
    </div>

    <div class="table-card">
        <h3>Virtual Classes (<?php echo e($virtualClasses->total()); ?>)</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Start/End</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>My Attendance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $virtualClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $myAtt = $vc->attendees->first();
                        $canJoin = in_array($vc->status, [\App\Models\VirtualClass::STATUS_SCHEDULED, \App\Models\VirtualClass::STATUS_ONGOING]) && !empty($vc->meeting_url);
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo e(route('student.classes.virtual_classes.show', [$class, $vc])); ?>" style="text-decoration:none;color:#2563eb;font-weight:600;">
                                <?php echo e($vc->title); ?>

                            </a>
                            <?php if($vc->description): ?>
                                <div style="font-size:0.78rem;color:#94a3b8;margin-top:2px;max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?php echo e(Str::limit($vc->description, 60)); ?>

                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($vc->meeting_date ? $vc->meeting_date->format('M d, Y') : 'TBD'); ?></td>
                        <td>
                            <?php if($vc->start_time && $vc->end_time): ?>
                                <?php echo e(\Carbon\Carbon::parse($vc->start_time)->format('g:i A')); ?>

                                —
                                <?php echo e(\Carbon\Carbon::parse($vc->end_time)->format('g:i A')); ?>

                            <?php else: ?>
                                TBD
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php switch($vc->meeting_provider):
                                case (\App\Models\VirtualClass::PROVIDER_ZOOM): ?>
                                    <span style="display:inline-flex;align-items:center;gap:5px;">
                                        <i class="fa-solid fa-video" style="color:#2D8CFF;"></i>
                                        Zoom
                                    </span>
                                    <?php break; ?>
                                <?php case (\App\Models\VirtualClass::PROVIDER_GOOGLE_MEET): ?>
                                    <span style="display:inline-flex;align-items:center;gap:5px;">
                                        <i class="fa-brands fa-google" style="color:#34a853;"></i>
                                        Meet
                                    </span>
                                    <?php break; ?>
                                <?php case (\App\Models\VirtualClass::PROVIDER_MICROSOFT_TEAMS): ?>
                                    <span style="display:inline-flex;align-items:center;gap:5px;">
                                        <i class="fa-brands fa-microsoft" style="color:#6264a7;"></i>
                                        Teams
                                    </span>
                                    <?php break; ?>
                                <?php case (\App\Models\VirtualClass::PROVIDER_OTHER): ?>
                                    <span style="display:inline-flex;align-items:center;gap:5px;">
                                        <i class="fa-solid fa-link" style="color:#64748b;"></i>
                                        Other
                                    </span>
                                    <?php break; ?>
                                <?php default: ?>
                                    <span style="color:#94a3b8;">N/A</span>
                            <?php endswitch; ?>
                        </td>
                        <td>
                            <?php switch($vc->status):
                                case (\App\Models\VirtualClass::STATUS_SCHEDULED): ?>
                                    <span class="badge" style="background:#dbeafe;color:#1e40af;">Scheduled</span>
                                    <?php break; ?>
                                <?php case (\App\Models\VirtualClass::STATUS_ONGOING): ?>
                                    <span class="badge" style="background:#dcfce7;color:#16a34a;">
                                        <span style="display:inline-block;width:7px;height:7px;background:#16a34a;border-radius:50%;margin-right:5px;animation:pulse 1.2s infinite;"></span>
                                        Live
                                    </span>
                                    <?php break; ?>
                                <?php case (\App\Models\VirtualClass::STATUS_COMPLETED): ?>
                                    <span class="badge" style="background:#f1f5f9;color:#64748b;">Completed</span>
                                    <?php break; ?>
                                <?php case (\App\Models\VirtualClass::STATUS_CANCELLED): ?>
                                    <span class="badge" style="background:#fee2e2;color:#dc2626;">Cancelled</span>
                                    <?php break; ?>
                                <?php default: ?>
                                    <span class="badge" style="background:#f1f5f9;color:#64748b;"><?php echo e(ucfirst($vc->status)); ?></span>
                            <?php endswitch; ?>
                        </td>
                        <td>
                            <?php if($myAtt): ?>
                                <?php switch($myAtt->attendance_status):
                                    case (\App\Models\VirtualClassAttendee::STATUS_PRESENT): ?>
                                        <span class="badge" style="background:#dcfce7;color:#16a34a;">
                                            <i class="fa-solid fa-check"></i> Present
                                        </span>
                                        <?php break; ?>
                                    <?php case (\App\Models\VirtualClassAttendee::STATUS_LATE): ?>
                                        <span class="badge" style="background:#fef3c7;color:#b45309;">
                                            <i class="fa-solid fa-clock"></i> Late
                                        </span>
                                        <?php break; ?>
                                    <?php case (\App\Models\VirtualClassAttendee::STATUS_ABSENT): ?>
                                        <span class="badge" style="background:#fee2e2;color:#dc2626;">
                                            <i class="fa-solid fa-xmark"></i> Absent
                                        </span>
                                        <?php break; ?>
                                    <?php case (\App\Models\VirtualClassAttendee::STATUS_EXCUSED): ?>
                                        <span class="badge" style="background:#e0e7ff;color:#4338ca;">
                                            <i class="fa-solid fa-umbrella"></i> Excused
                                        </span>
                                        <?php break; ?>
                                    <?php default: ?>
                                        <span class="badge" style="background:#f1f5f9;color:#64748b;">
                                            <?php echo e(ucfirst($myAtt->attendance_status)); ?>

                                        </span>
                                <?php endswitch; ?>
                                <?php if($myAtt->joined_at): ?>
                                    <div style="font-size:0.72rem;color:#94a3b8;margin-top:3px;">
                                        Joined <?php echo e($myAtt->joined_at->format('g:i A')); ?>

                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if($vc->status === \App\Models\VirtualClass::STATUS_COMPLETED): ?>
                                    <span class="badge" style="background:#fee2e2;color:#dc2626;">
                                        <i class="fa-solid fa-xmark"></i> Absent
                                    </span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;font-size:0.82rem;">— Not joined —</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <a href="<?php echo e(route('student.classes.virtual_classes.show', [$class, $vc])); ?>" class="btn-icon btn-view" title="View Details">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <?php if($canJoin): ?>
                                <form action="<?php echo e(route('student.classes.virtual_classes.join', [$class, $vc])); ?>" method="POST" style="display:inline;" onsubmit="return confirm('Join this virtual class? Your attendance will be recorded.');">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn-icon" title="Join Meeting" style="background:#16a34a;color:white;border:none;padding:6px 9px;border-radius:6px;cursor:pointer;">
                                        <i class="fa-solid fa-right-to-bracket"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:24px;color:#aaa;">
                            <i class="fa-solid fa-video" style="font-size:2rem;display:block;margin-bottom:8px;opacity:0.4;"></i>
                            No virtual classes scheduled for this class yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if($virtualClasses->hasPages()): ?>
            <div style="display:flex;justify-content:flex-end;padding:16px 20px 0;">
                <?php echo e($virtualClasses->links()); ?>

            </div>
        <?php endif; ?>
    </div>

    <div style="margin: 0 24px 24px;">
        <a href="<?php echo e(route('student.classes.show', $class)); ?>" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            ← Back to Class
        </a>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\virtual_classes\index.blade.php ENDPATH**/ ?>