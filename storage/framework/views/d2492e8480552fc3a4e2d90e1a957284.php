<?php $__env->startSection('title', 'Virtual Class'); ?>
<?php
    $activeNav = 'virtual-classes';
    $pageTitle = 'Virtual Class';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            <?php echo e($virtualClass->title); ?>

        </h2>
        <div style="display: flex; gap: 8px;">
            <a href="<?php echo e(route('student.virtual-classes.index', $class)); ?>" 
               class="btn-modal-cancel" style="display: inline-flex; align-items: center; padding: 8px 16px; border-radius: 6px; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> Back to Classes
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- Meeting Status Card -->
    <div class="form-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h3 style="margin: 0 0 8px 0;"><i class="fa-solid fa-video"></i> <?php echo e($virtualClass->title); ?></h3>
                <div style="opacity: 0.9; font-size: 0.9rem;">
                    <?php if($virtualClass->status === 'scheduled'): ?>
                        <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">
                            <i class="fa-solid fa-calendar"></i> Scheduled
                        </span>
                    <?php elseif($virtualClass->status === 'ongoing'): ?>
                        <span style="background: rgba(34, 197, 94, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">
                            <i class="fa-solid fa-circle fa-spin" style="font-size: 0.6rem;"></i> Live Now
                        </span>
                    <?php elseif($virtualClass->status === 'completed'): ?>
                        <span style="background: rgba(148, 163, 184, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">
                            <i class="fa-solid fa-check-circle"></i> Completed
                        </span>
                    <?php else: ?>
                        <span style="background: rgba(239, 68, 68, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">
                            <i class="fa-solid fa-times-circle"></i> Cancelled
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if($virtualClass->status === 'ongoing' || ($virtualClass->status === 'scheduled' && now()->between($virtualClass->meeting_date->setTimeFromTimeString($virtualClass->start_time), $virtualClass->meeting_date->setTimeFromTimeString($virtualClass->end_time)))): ?>
                <form action="<?php echo e(route('student.virtual-classes.join', [$class, $virtualClass])); ?>" method="POST" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn-add" style="background: #22c55e; color: white; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 600; border: none;">
                        <i class="fa-solid fa-sign-in-alt"></i> Join Meeting
                    </button>
                </form>
            <?php elseif($virtualClass->status === 'scheduled' && now()->lt($virtualClass->meeting_date->setTimeFromTimeString($virtualClass->start_time))): ?>
                <button disabled class="btn-modal-cancel" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; border-radius: 8px; cursor: not-allowed; font-size: 1rem; border: none;">
                    <i class="fa-solid fa-clock"></i> Not Started Yet
                </button>
            <?php else: ?>
                <button disabled class="btn-modal-cancel" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; border-radius: 8px; cursor: not-allowed; font-size: 1rem; border: none;">
                    <i class="fa-solid fa-check-circle"></i> Ended
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Meeting Details -->
    <div class="form-card">
        <h3><i class="fa-solid fa-info-circle"></i> Meeting Details</h3>
        
        <div class="modal-row">
            <span>Date:</span>
            <span><?php echo e($virtualClass->meeting_date->format('l, F d, Y')); ?></span>
        </div>
        
        <div class="modal-row">
            <span>Time:</span>
            <span><?php echo e($virtualClass->start_time); ?> - <?php echo e($virtualClass->end_time); ?></span>
        </div>
        
        <div class="modal-row">
            <span>Duration:</span>
            <span>
                <?php
                    $startTime = \Carbon\Carbon::parse($virtualClass->start_time);
                    $endTime = \Carbon\Carbon::parse($virtualClass->end_time);
                    $duration = $startTime->diff($endTime);
                ?>
                <?php echo e($duration->h); ?>h <?php echo e($duration->i); ?>m
            </span>
        </div>
        
        <div class="modal-row">
            <span>Instructor:</span>
            <span><?php echo e($virtualClass->instructor->full_name); ?></span>
        </div>
        
        <div class="modal-row">
            <span>Platform:</span>
            <span>
                <?php if($virtualClass->meeting_provider === 'zoom'): ?>
                    <i class="fa-solid fa-video" style="color: #3b82f6; margin-right: 4px;"></i> Zoom
                <?php elseif($virtualClass->meeting_provider === 'google_meet'): ?>
                    <i class="fa-solid fa-google" style="color: #ef4444; margin-right: 4px;"></i> Google Meet
                <?php elseif($virtualClass->meeting_provider === 'microsoft_teams'): ?>
                    <i class="fa-solid fa-microsoft" style="color: #0ea5e9; margin-right: 4px;"></i> Microsoft Teams
                <?php else: ?>
                    <i class="fa-solid fa-video" style="color: #64748b; margin-right: 4px;"></i> <?php echo e(ucfirst($virtualClass->meeting_provider)); ?>

                <?php endif; ?>
            </span>
        </div>
        
        <?php if($virtualClass->description): ?>
            <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-align-left"></i> Description</div>
            <div class="modal-row"><span></span><span><?php echo e($virtualClass->description); ?></span></div>
        <?php endif; ?>
        
        <?php if($virtualClass->meeting_password): ?>
            <div class="modal-row" style="margin-top: 8px;">
                <span>Password:</span>
                <span style="font-family: monospace; background: #f1f5f9; padding: 4px 8px; border-radius: 4px;">
                    <?php echo e($virtualClass->meeting_password); ?>

                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Meeting Link -->
    <?php if($virtualClass->meeting_url): ?>
        <div class="form-card">
            <h3><i class="fa-solid fa-link"></i> Meeting Link</h3>
            <div style="display: flex; gap: 8px; margin-top: 16px;">
                <input type="text" value="<?php echo e($virtualClass->meeting_url); ?>" readonly
                       style="flex: 1; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc;">
                <button type="button" onclick="copyMeetingLink()" class="btn-add" style="padding: 12px 20px; border-radius: 8px; cursor: pointer;">
                    <i class="fa-solid fa-copy"></i> Copy
                </button>
                <a href="<?php echo e($virtualClass->meeting_url); ?>" target="_blank" 
                   class="btn-add" style="padding: 12px 20px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center;">
                    <i class="fa-solid fa-external-link-alt"></i> Open
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Your Attendance -->
    <div class="form-card">
        <h3><i class="fa-solid fa-user-check"></i> Your Attendance</h3>
        
        <?php if($myAttendance): ?>
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-top: 16px;">
                <div class="modal-row">
                    <span>Status:</span>
                    <span>
                        <?php if($myAttendance->attendance_status === 'present'): ?>
                            <span style="background: #dcfce7; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                <i class="fa-solid fa-check-circle"></i> Present
                            </span>
                        <?php elseif($myAttendance->attendance_status === 'late'): ?>
                            <span style="background: #fef3c7; color: #d97706; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                <i class="fa-solid fa-clock"></i> Late
                            </span>
                        <?php else: ?>
                            <span style="background: #fee2e2; color: #dc2626; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                <i class="fa-solid fa-times-circle"></i> Absent
                            </span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if($myAttendance->joined_at): ?>
                    <div class="modal-row">
                        <span>Joined At:</span>
                        <span><?php echo e($myAttendance->joined_at->format('M d, Y g:i A')); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if($myAttendance->left_at): ?>
                    <div class="modal-row">
                        <span>Left At:</span>
                        <span><?php echo e($myAttendance->left_at->format('M d, Y g:i A')); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if($myAttendance->duration_minutes): ?>
                    <div class="modal-row">
                        <span>Duration:</span>
                        <span><?php echo e($myAttendance->duration_minutes); ?> minutes</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="padding: 20px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 8px; margin-top: 16px;">
                <i class="fa-solid fa-user-clock" style="font-size: 2rem; margin-bottom: 8px;"></i>
                <div>Attendance not yet recorded</div>
                <div style="font-size: 0.85rem; margin-top: 4px;">Join the meeting to record your attendance</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Meeting Guidelines -->
    <div class="form-card">
        <h3><i class="fa-solid fa-lightbulb"></i> Meeting Guidelines</h3>
        <ul style="margin-top: 16px; padding-left: 20px; color: #475569;">
            <li style="margin-bottom: 8px;">Join the meeting 5-10 minutes before the scheduled start time.</li>
            <li style="margin-bottom: 8px;">Test your microphone and camera before joining.</li>
            <li style="margin-bottom: 8px;">Ensure you have a stable internet connection.</li>
            <li style="margin-bottom: 8px;">Keep your microphone muted when not speaking.</li>
            <li style="margin-bottom: 8px;">Use a quiet environment with minimal background noise.</li>
            <li style="margin-bottom: 8px;">Have any required materials ready before the meeting starts.</li>
            <li style="margin-bottom: 8px;">Take notes during the session for better retention.</li>
        </ul>
    </div>

    <!-- Recurring Information -->
    <?php if($virtualClass->recurrence && is_array($virtualClass->recurrence)): ?>
        <div class="form-card">
            <h3><i class="fa-solid fa-redo"></i> Recurring Meeting</h3>
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-top: 16px;">
                <div class="modal-row">
                    <span>Frequency:</span>
                    <span><?php echo e(ucfirst($virtualClass->recurrence['frequency'] ?? 'weekly')); ?></span>
                </div>
                
                <?php if(isset($virtualClass->recurrence['end_date'])): ?>
                    <div class="modal-row">
                        <span>Until:</span>
                        <span><?php echo e(\Carbon\Carbon::parse($virtualClass->recurrence['end_date'])->format('M d, Y')); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($virtualClass->recurrence['days'])): ?>
                    <div class="modal-row">
                        <span>Days:</span>
                        <span><?php echo e(implode(', ', array_map('ucfirst', $virtualClass->recurrence['days']))); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add to Calendar -->
    <div class="form-card">
        <h3><i class="fa-solid fa-calendar-plus"></i> Add to Calendar</h3>
        <div style="display: flex; gap: 8px; margin-top: 16px;">
            <button type="button" onclick="addToGoogleCalendar()" class="btn-add" style="flex: 1; padding: 12px 20px; border-radius: 8px; cursor: pointer;">
                <i class="fa-solid fa-google"></i> Google Calendar
            </button>
            <button type="button" onclick="downloadICS()" class="btn-modal-cancel" style="flex: 1; padding: 12px 20px; border-radius: 8px; cursor: pointer;">
                <i class="fa-solid fa-download"></i> Download ICS
            </button>
        </div>
    </div>

    <script>
        function copyMeetingLink() {
            const link = '<?php echo e($virtualClass->meeting_url); ?>';
            navigator.clipboard.writeText(link).then(() => {
                alert('Meeting link copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }

        function addToGoogleCalendar() {
            const title = encodeURIComponent('<?php echo e($virtualClass->title); ?>');
            const start = encodeURIComponent('<?php echo e($virtualClass->meeting_date->format('Ymd')); ?>T<?php echo e(str_replace(':', '', $virtualClass->start_time)); ?>00');
            const end = encodeURIComponent('<?php echo e($virtualClass->meeting_date->format('Ymd')); ?>T<?php echo e(str_replace(':', '', $virtualClass->end_time)); ?>00');
            const description = encodeURIComponent('<?php echo e($virtualClass->description ?? "Virtual class meeting"); ?>');
            const location = encodeURIComponent('<?php echo e($virtualClass->meeting_url ?? ""); ?>');
            
            const url = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${start}/${end}&details=${description}&location=${location}`;
            window.open(url, '_blank');
        }

        function downloadICS() {
            const title = '<?php echo e($virtualClass->title); ?>';
            const start = '<?php echo e($virtualClass->meeting_date->format('Ymd')); ?>T<?php echo e(str_replace(':', '', $virtualClass->start_time)); ?>00';
            const end = '<?php echo e($virtualClass->meeting_date->format('Ymd')); ?>T<?php echo e(str_replace(':', '', $virtualClass->end_time)); ?>00';
            const description = '<?php echo e($virtualClass->description ?? "Virtual class meeting"); ?>';
            const location = '<?php echo e($virtualClass->meeting_url ?? ""); ?>';
            
            const icsContent = `BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
DTSTART:${start}
DTEND:${end}
SUMMARY:${title}
DESCRIPTION:${description}
LOCATION:${location}
END:VEVENT
END:VCALENDAR`;
            
            const blob = new Blob([icsContent], { type: 'text/calendar' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${title.replace(/\s+/g, '_')}.ics`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Countdown timer for meeting start
        <?php if($virtualClass->status === 'scheduled' && now()->lt($virtualClass->meeting_date->setTimeFromTimeString($virtualClass->start_time))): ?>
            const meetingStart = new Date('<?php echo e($virtualClass->meeting_date->setTimeFromTimeString($virtualClass->start_time)->toIso8601String()); ?>');
            
            function updateCountdown() {
                const now = new Date();
                const diff = meetingStart - now;
                
                if (diff > 0) {
                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                    
                    let countdownText = '';
                    if (days > 0) countdownText += days + 'd ';
                    countdownText += hours + 'h ' + minutes + 'm ' + seconds + 's';
                    
                    const countdownElement = document.getElementById('countdown');
                    if (countdownElement) {
                        countdownElement.textContent = countdownText;
                    }
                }
            }
            
            setInterval(updateCountdown, 1000);
            updateCountdown();
        <?php endif; ?>
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\virtual_classes\show.blade.php ENDPATH**/ ?>