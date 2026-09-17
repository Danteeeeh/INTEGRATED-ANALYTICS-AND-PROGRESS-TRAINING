<?php $__env->startSection('title', 'Attendance'); ?>
<?php $activeNav = 'attendance'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Attendance','subtitle' => 'Record and review attendance across classes and virtual sessions.','icon' => 'fa-clipboard-user']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Attendance','subtitle' => 'Record and review attendance across classes and virtual sessions.','icon' => 'fa-clipboard-user']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.attendance.create')); ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Record Attendance</a>
            <a href="<?php echo e(route('admin.attendance.export')); ?>" class="btn btn-secondary"><i class="fa-solid fa-download"></i> Export</a>
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5)): ?>
<?php $attributes = $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5; ?>
<?php unset($__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8fc5d82814dad270c8dc67128a2a98d5)): ?>
<?php $component = $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5; ?>
<?php unset($__componentOriginal8fc5d82814dad270c8dc67128a2a98d5); ?>
<?php endif; ?>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="<?php echo e(route('admin.attendance.index')); ?>">
            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($course->id); ?>" <?php if(request('course_id') == $course->id): echo 'selected'; endif; ?>><?php echo e($course->code); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select class="form-control" name="class_id" aria-label="Filter class">
                <option value="">All Classes</option>
                <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($class->id); ?>" <?php if(request('class_id') == $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                <?php $__currentLoopData = ['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'excused' => 'Excused']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($value); ?>" <?php if(request('status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <input class="form-control" type="date" name="attendance_date" value="<?php echo e(request('attendance_date')); ?>" aria-label="Filter by date">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="<?php echo e(route('admin.attendance.index')); ?>"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            <?php if($attendanceRecords->count() > 0): ?>
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Session</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $attendanceRecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($record->attendance_date?->format('M j, Y')); ?></td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-name"><?php echo e($record->student?->name ?? '—'); ?></div>
                                            <div class="user-email"><?php echo e($record->student?->identifier ?? ''); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo e($record->class?->code ?? '—'); ?></td>
                                    <td>
                                        <?php if($record->session_title): ?>
                                            <?php echo e($record->session_title); ?>

                                        <?php elseif($record->virtualClass): ?>
                                            <?php echo e($record->virtualClass->title); ?>

                                        <?php else: ?>
                                            — 
                                        <?php endif; ?>
                                    </td>
                                    <td><?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($record->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($record->status).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $attributes = $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $component = $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?></td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="<?php echo e(route('admin.attendance.show', $record)); ?>" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="<?php echo e(route('admin.attendance.edit', $record)); ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-clipboard-user','title' => 'No attendance records found','description' => 'Record attendance for a class to see it here.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-clipboard-user','title' => 'No attendance records found','description' => 'Record attendance for a class to see it here.']); ?>
                     <?php $__env->slot('action', null, []); ?> 
                        <a class="btn btn-primary" href="<?php echo e(route('admin.attendance.create')); ?>"><i class="fa-solid fa-plus"></i> Record Attendance</a>
                     <?php $__env->endSlot(); ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb3aac3110999f7435f4950a3fe8df251)): ?>
<?php $attributes = $__attributesOriginalb3aac3110999f7435f4950a3fe8df251; ?>
<?php unset($__attributesOriginalb3aac3110999f7435f4950a3fe8df251); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb3aac3110999f7435f4950a3fe8df251)): ?>
<?php $component = $__componentOriginalb3aac3110999f7435f4950a3fe8df251; ?>
<?php unset($__componentOriginalb3aac3110999f7435f4950a3fe8df251); ?>
<?php endif; ?>
            <?php endif; ?>

            <?php if($attendanceRecords->hasPages()): ?>
                <div class="pagination"><?php echo e($attendanceRecords->appends(request()->query())->links()); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\attendance\index.blade.php ENDPATH**/ ?>