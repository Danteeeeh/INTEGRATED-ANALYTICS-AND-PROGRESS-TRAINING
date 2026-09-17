<?php $__env->startSection('title', 'Students'); ?>
<?php $activeNav = 'students'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Students','subtitle' => 'Manage student accounts, enrollment status, and CSV import/export.','icon' => 'fa-user-graduate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Students','subtitle' => 'Manage student accounts, enrollment status, and CSV import/export.','icon' => 'fa-user-graduate']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.students.create')); ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Student</a>
            <a href="<?php echo e(route('admin.students.export', request()->query())); ?>" class="btn btn-secondary"><i class="fa-solid fa-download"></i> Export</a>
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
        <form method="GET" action="<?php echo e(route('admin.students.index')); ?>" class="user-toolbar">
            <input type="search" name="search" placeholder="Search students..." value="<?php echo e(request('search')); ?>" class="form-control" aria-label="Search students">
            <select name="status" class="form-control" aria-label="Filter status">
                <option value="">All Status</option>
                <?php $__currentLoopData = ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended', 'pending' => 'Pending']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($value); ?>" <?php if(request('status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="<?php echo e(route('admin.students.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            <?php if($students->count() > 0): ?>
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Identifier</th>
                                <th>Status</th>
                                <th>Enrollments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar"><?php echo e(strtoupper(substr($student->first_name, 0, 1))); ?></div>
                                            <div>
                                                <div class="user-name"><?php echo e($student->full_name); ?></div>
                                                <div class="user-email">Student account</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo e($student->email); ?></td>
                                    <td><?php echo e($student->identifier ?: '—'); ?></td>
                                    <td><?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($student->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($student->status).'']); ?>
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
                                    <td><?php echo e($student->enrollments->count()); ?></td>
                                    <td>
                                        <div class="user-actions">
                                            <a href="<?php echo e(route('admin.students.show', $student)); ?>" class="btn btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a href="<?php echo e(route('admin.students.edit', $student)); ?>" class="btn btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form action="<?php echo e(route('admin.students.destroy', $student)); ?>" method="POST" onsubmit="return confirm('Delete this student?')">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                            </form>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-user-graduate','title' => 'No students found','description' => 'Add a student account to get started.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-user-graduate','title' => 'No students found','description' => 'Add a student account to get started.']); ?>
                     <?php $__env->slot('action', null, []); ?> 
                        <a href="<?php echo e(route('admin.students.create')); ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Student</a>
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

            <?php if($students->hasPages()): ?>
                <div class="pagination"><?php echo e($students->appends(request()->query())->links()); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\students\index.blade.php ENDPATH**/ ?>