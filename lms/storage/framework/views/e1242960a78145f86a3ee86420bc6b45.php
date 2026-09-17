<?php $__env->startSection('title', 'Competencies'); ?>
<?php $activeNav = 'competencies'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Competencies','subtitle' => 'Define competency frameworks and learning outcomes.','icon' => 'fa-compass']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Competencies','subtitle' => 'Define competency frameworks and learning outcomes.','icon' => 'fa-compass']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('competencies.create')): ?>
                <a href="<?php echo e(route('admin.competencies.create')); ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Competency</a>
            <?php endif; ?>
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
        <form class="user-toolbar" method="GET" action="<?php echo e(route('admin.competencies.index')); ?>">
            <input class="form-control" name="search" value="<?php echo e(request('search')); ?>" placeholder="Search competencies..." aria-label="Search competencies">
            <select class="form-control" name="framework_id" aria-label="Filter framework">
                <option value="">All Frameworks</option>
                <?php $__currentLoopData = $frameworks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $framework): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($framework->id); ?>" <?php if(request('framework_id') == $framework->id): echo 'selected'; endif; ?>><?php echo e($framework->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="<?php echo e(route('admin.competencies.index')); ?>"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            <?php if($competencies->count() > 0): ?>
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Competency</th>
                                <th>Framework</th>
                                <th>Parent</th>
                                <th>Mapped Courses</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $competencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $competency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td>
                                        <div class="user-name"><?php echo e($competency->code); ?> — <?php echo e($competency->name); ?></div>
                                        <div class="user-email"><?php echo e(Str::limit($competency->description ?? '', 50)); ?></div>
                                    </td>
                                    <td><?php echo e($competency->framework?->name ?? '—'); ?></td>
                                    <td><?php echo e($competency->parent?->name ?? '—'); ?></td>
                                    <td><?php echo e($competency->courseMappings->count()); ?></td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="<?php echo e(route('admin.competencies.show', $competency)); ?>" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="<?php echo e(route('admin.competencies.edit', $competency)); ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $competency)): ?>
                                                <form method="POST" action="<?php echo e(route('admin.competencies.destroy', $competency)); ?>" onsubmit="return confirm('Delete this competency?')">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            <?php endif; ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-compass','title' => 'No competencies found','description' => 'Create a competency to define measurable learning outcomes.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-compass','title' => 'No competencies found','description' => 'Create a competency to define measurable learning outcomes.']); ?>
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

            <?php if($competencies->hasPages()): ?>
                <div class="pagination"><?php echo e($competencies->appends(request()->query())->links()); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\competencies\index.blade.php ENDPATH**/ ?>