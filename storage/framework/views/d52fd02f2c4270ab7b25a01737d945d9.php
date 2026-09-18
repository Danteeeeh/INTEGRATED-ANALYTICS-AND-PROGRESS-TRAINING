<?php $__env->startSection('title', 'Instructor Details'); ?>
<?php $activeNav = 'instructors'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => ''.e($instructor->name).'','subtitle' => 'Instructor account overview, contact details, and assigned classes.','icon' => 'fa-chalkboard-user']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($instructor->name).'','subtitle' => 'Instructor account overview, contact details, and assigned classes.','icon' => 'fa-chalkboard-user']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <span class="live-dot"></span>
            <span><?php echo e($instructor->role?->name ?? 'Instructor'); ?></span>
            <span>·</span>
            <span><?php echo e(ucfirst($instructor->status ?? 'active')); ?></span>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.instructors.edit', $instructor)); ?>" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="<?php echo e(route('admin.instructors.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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

    <div class="user-stat-grid">
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Classes','value' => ''.e($instructor->classesInstructing->count()).'','icon' => 'fa-school','footer' => 'Assigned classes']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Classes','value' => ''.e($instructor->classesInstructing->count()).'','icon' => 'fa-school','footer' => 'Assigned classes']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $attributes = $__attributesOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__attributesOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $component = $__componentOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__componentOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Status','value' => ''.e(ucfirst($instructor->status ?? 'active')).'','icon' => 'fa-circle-check','footer' => 'Account state']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Status','value' => ''.e(ucfirst($instructor->status ?? 'active')).'','icon' => 'fa-circle-check','footer' => 'Account state']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $attributes = $__attributesOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__attributesOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $component = $__componentOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__componentOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Email','value' => ''.e($instructor->email).'','icon' => 'fa-envelope','footer' => 'Contact']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Email','value' => ''.e($instructor->email).'','icon' => 'fa-envelope','footer' => 'Contact']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $attributes = $__attributesOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__attributesOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $component = $__componentOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__componentOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Joined','value' => ''.e($instructor->created_at?->format('M Y') ?? '—').'','icon' => 'fa-calendar','footer' => 'Member since']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Joined','value' => ''.e($instructor->created_at?->format('M Y') ?? '—').'','icon' => 'fa-calendar','footer' => 'Member since']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $attributes = $__attributesOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__attributesOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $component = $__componentOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__componentOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-address-card"></i> Contact Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Email</label>
                    <div><?php echo e($instructor->email); ?></div>
                </div>
                <div class="form-field">
                    <label>Phone</label>
                    <div><?php echo e($instructor->phone ?? '—'); ?></div>
                </div>
                <div class="form-field full">
                    <label>Address</label>
                    <div><?php echo e($instructor->address ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Instructor ID</label>
                    <div><?php echo e($instructor->identifier ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Last Login</label>
                    <div><?php echo e($instructor->last_login_at?->format('M j, Y g:i A') ?? 'Never'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-school"></i> Assigned Classes</h3>
            <span class="user-status active"><?php echo e($instructor->classesInstructing->count()); ?> classes</span>
        </div>
        <div class="user-panel-body">
            <?php if($instructor->classesInstructing->count() > 0): ?>
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Course</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $instructor->classesInstructing; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><strong><?php echo e($class->code); ?></strong></td>
                                    <td><?php echo e($class->course?->title ?? '—'); ?></td>
                                    <td><?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($class->status ?? 'active').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($class->status ?? 'active').'']); ?>
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
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-school','title' => 'No classes assigned','description' => 'This instructor has not been assigned to any classes yet.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-school','title' => 'No classes assigned','description' => 'This instructor has not been assigned to any classes yet.']); ?>
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
        </div>
    </div>

    <div class="user-actions">
        <form action="<?php echo e(route('admin.instructors.destroy', $instructor)); ?>" method="POST" onsubmit="return confirm('Deactivate this instructor?')">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-user-slash"></i> Deactivate</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\instructors\show.blade.php ENDPATH**/ ?>