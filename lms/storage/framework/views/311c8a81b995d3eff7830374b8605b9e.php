<?php $__env->startSection('title', 'Rubrics'); ?>
<?php $activeNav = 'rubrics'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Rubrics','subtitle' => 'Create and manage grading rubrics for assignments.','icon' => 'fa-table-list']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Rubrics','subtitle' => 'Create and manage grading rubrics for assignments.','icon' => 'fa-table-list']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.rubrics.create')); ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Rubric</a>
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
        <form class="user-toolbar" method="GET" action="<?php echo e(route('admin.rubrics.index')); ?>">
            <input class="form-control" name="search" value="<?php echo e(request('search')); ?>" placeholder="Search rubrics..." aria-label="Search rubrics">
            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($course->id); ?>" <?php if(request('course_id') == $course->id): echo 'selected'; endif; ?>><?php echo e($course->code); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                <?php $__currentLoopData = ['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($value); ?>" <?php if(request('status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="<?php echo e(route('admin.rubrics.index')); ?>"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            <?php if($rubrics->count() > 0): ?>
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Rubric</th>
                                <th>Course / Class</th>
                                <th>Status</th>
                                <th>Shared</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $rubrics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rubric): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td>
                                        <div class="user-name"><?php echo e($rubric->title); ?></div>
                                        <div class="user-email"><?php echo e(Str::limit($rubric->description ?? '', 50)); ?></div>
                                    </td>
                                    <td>
                                        <?php if($rubric->course): ?>
                                            <?php echo e($rubric->course->code); ?>

                                        <?php elseif($rubric->class): ?>
                                            <?php echo e($rubric->class->code); ?>

                                        <?php else: ?>
                                            General
                                        <?php endif; ?>
                                    </td>
                                    <td><?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($rubric->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($rubric->status).'']); ?>
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
                                    <td><?php echo e($rubric->is_shared ? 'Yes' : 'No'); ?></td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="<?php echo e(route('admin.rubrics.show', $rubric)); ?>" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="<?php echo e(route('admin.rubrics.edit', $rubric)); ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form method="POST" action="<?php echo e(route('admin.rubrics.destroy', $rubric)); ?>" onsubmit="return confirm('Delete this rubric?')">
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-table-list','title' => 'No rubrics found','description' => 'Create a rubric to standardize assignment grading.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-table-list','title' => 'No rubrics found','description' => 'Create a rubric to standardize assignment grading.']); ?>
                     <?php $__env->slot('action', null, []); ?> 
                        <a class="btn btn-primary" href="<?php echo e(route('admin.rubrics.create')); ?>"><i class="fa-solid fa-plus"></i> New Rubric</a>
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

            <?php if($rubrics->hasPages()): ?>
                <div class="pagination"><?php echo e($rubrics->appends(request()->query())->links()); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\rubrics\index.blade.php ENDPATH**/ ?>