<?php $__env->startSection('title', 'Virtual Classes'); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Virtual Classes';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            Virtual Classes
        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card">
        <div class="crud-header">
            <h3>All Virtual Classes</h3>
            <a href="<?php echo e(route('admin.virtual_classes.create')); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                New Virtual Class
            </a>
        </div>

        <div style="padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #fafafa;">
            <form method="GET" action="<?php echo e(route('admin.virtual_classes.index')); ?>" data-filter-form style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; align-items: end;">
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Course</label>
                    <select name="course_id">
                        <option value="">All Courses</option>
                        <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($course->id); ?>" <?php echo e(request('course_id') == $course->id ? 'selected' : ''); ?>>
                                <?php echo e($course->code); ?> - <?php echo e($course->title); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Class</label>
                    <select name="class_id">
                        <option value="">All Classes</option>
                        <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($class->id); ?>" <?php echo e(request('class_id') == $class->id ? 'selected' : ''); ?>>
                                <?php echo e($class->code); ?> (<?php echo e($class->course->code ?? 'N/A'); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Instructor</label>
                    <select name="instructor_id">
                        <option value="">All Instructors</option>
                        <?php $__currentLoopData = $instructors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $instructor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($instructor->id); ?>" <?php echo e(request('instructor_id') == $instructor->id ? 'selected' : ''); ?>>
                                <?php echo e($instructor->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Provider</label>
                    <select name="meeting_provider">
                        <option value="">All Providers</option>
                        <option value="zoom" <?php echo e(request('meeting_provider') == 'zoom' ? 'selected' : ''); ?>>Zoom</option>
                        <option value="google_meet" <?php echo e(request('meeting_provider') == 'google_meet' ? 'selected' : ''); ?>>Google Meet</option>
                        <option value="microsoft_teams" <?php echo e(request('meeting_provider') == 'microsoft_teams' ? 'selected' : ''); ?>>Microsoft Teams</option>
                        <option value="other" <?php echo e(request('meeting_provider') == 'other' ? 'selected' : ''); ?>>Other</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Status</label>
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="scheduled" <?php echo e(request('status') == 'scheduled' ? 'selected' : ''); ?>>Scheduled</option>
                        <option value="ongoing" <?php echo e(request('status') == 'ongoing' ? 'selected' : ''); ?>>Ongoing</option>
                        <option value="completed" <?php echo e(request('status') == 'completed' ? 'selected' : ''); ?>>Completed</option>
                        <option value="cancelled" <?php echo e(request('status') == 'cancelled' ? 'selected' : ''); ?>>Cancelled</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Search</label>
                    <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Title, desc, ID...">
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <a href="<?php echo e(route('admin.virtual_classes.index')); ?>" style="padding: 8px 16px; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; display: inline-flex; align-items: center;">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Course</th>
                    <th>Class</th>
                    <th>Instructor</th>
                    <th>Date/Time</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $virtualClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #111827;"><?php echo e($vc->title); ?></div>
                            <?php if($vc->meeting_id): ?>
                                <div style="font-size: 0.75rem; color: #6b7280;">ID: <?php echo e($vc->meeting_id); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($vc->course->code ?? '-'); ?> - <?php echo e(($vc->course->title ?? '-')); ?></td>
                        <td><?php echo e($vc->class->code ?? '-'); ?></td>
                        <td><?php echo e($vc->instructor->name ?? '-'); ?></td>
                        <td>
                            <div style="font-weight: 500;"><?php echo e($vc->meeting_date->format('M d, Y')); ?></div>
                            <div style="font-size: 0.78rem; color: #6b7280;">
                                <?php echo e(\Carbon\Carbon::parse($vc->start_time)->format('g:i A')); ?>

                                -
                                <?php echo e(\Carbon\Carbon::parse($vc->end_time)->format('g:i A')); ?>

                            </div>
                        </td>
                        <td>
                            <?php
                                $providerBadgeStyle = match($vc->meeting_provider) {
                                    'zoom' => 'background: #e0e7ff; color: #4338ca;',
                                    'google_meet' => 'background: #fee2e2; color: #dc2626;',
                                    'microsoft_teams' => 'background: #dbeafe; color: #1e40af;',
                                    'other' => 'background: #f3f4f6; color: #374151;',
                                    default => 'background: #f3f4f6; color: #6b7280;',
                                };
                                $providerLabel = match($vc->meeting_provider) {
                                    'zoom' => 'Zoom',
                                    'google_meet' => 'Google Meet',
                                    'microsoft_teams' => 'Teams',
                                    'other' => 'Other',
                                    default => ucfirst($vc->meeting_provider),
                                };
                            ?>
                            <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; <?php echo e($providerBadgeStyle); ?>">
                                <?php echo e($providerLabel); ?>

                            </span>
                        </td>
                        <td>
                            <?php
                                $statusBadgeStyle = match($vc->status) {
                                    'scheduled' => 'background: #dcfce7; color: #16a34a;',
                                    'ongoing' => 'background: #fef3c7; color: #d97706;',
                                    'completed' => 'background: #dbeafe; color: #1e40af;',
                                    'cancelled' => 'background: #fee2e2; color: #dc2626;',
                                    default => 'background: #f1f5f9; color: #64748b;',
                                };
                            ?>
                            <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; <?php echo e($statusBadgeStyle); ?>">
                                <?php echo e(ucfirst($vc->status)); ?>

                            </span>
                        </td>
                        <td class="actions-cell">
                            <a href="<?php echo e(route('admin.virtual_classes.show', $vc)); ?>" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('admin.virtual_classes.edit', $vc)); ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <?php if($vc->meeting_url): ?>
                                <a href="<?php echo e($vc->meeting_url); ?>" target="_blank" class="btn-icon btn-view" title="Open Meeting" style="background: #dbeafe; color: #1e40af;">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            <?php endif; ?>
                            <form method="POST" action="<?php echo e(route('admin.virtual_classes.destroy', $vc)); ?>" class="inline" onsubmit="return confirm('Are you sure you want to delete this virtual class?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn-icon btn-delete" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:32px;color:#aaa;">
                            No virtual classes found. <a href="<?php echo e(route('admin.virtual_classes.create')); ?>" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if($virtualClasses->hasPages()): ?>
            <div style="padding: 16px 24px; border-top: 1px solid #e5e7eb;">
                <?php echo e($virtualClasses->appends(request()->query())->links()); ?>

            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/admin/virtual_classes/index.blade.php ENDPATH**/ ?>