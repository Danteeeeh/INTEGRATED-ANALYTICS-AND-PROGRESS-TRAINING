<?php $__env->startSection('title', 'Announcements'); ?>
<?php $activeNav = 'announcements'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Announcements','subtitle' => 'Create and manage announcements across institution, courses, and classes.','icon' => 'fa-bullhorn']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Announcements','subtitle' => 'Create and manage announcements across institution, courses, and classes.','icon' => 'fa-bullhorn']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.announcements.create')); ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Announcement</a>
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
        <form class="user-toolbar" method="GET" action="<?php echo e(route('admin.announcements.index')); ?>">
            <input class="form-control" name="search" value="<?php echo e(request('search')); ?>" placeholder="Search announcements..." aria-label="Search announcements">
            <select class="form-control" name="audience_type" aria-label="Filter audience">
                <option value="">All Audiences</option>
                <?php $__currentLoopData = ['institution' => 'Institution', 'course' => 'Course', 'class' => 'Class', 'role' => 'Role', 'users' => 'Users']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($value); ?>" <?php if(request('audience_type') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                <?php $__currentLoopData = ['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published', 'archived' => 'Archived']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($value); ?>" <?php if(request('status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="<?php echo e(route('admin.announcements.index')); ?>"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            <?php if($announcements->count() > 0): ?>
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Audience</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $announcements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $announcement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-name">
                                                <?php if($announcement->is_pinned): ?><i class="fa-solid fa-thumbtack" style="color:var(--user-accent)" aria-label="Pinned"></i><?php endif; ?>
                                                <?php echo e($announcement->title); ?>

                                            </div>
                                            <div class="user-email"><?php echo e(Str::limit($announcement->body, 60)); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="user-status"><?php echo e(ucfirst($announcement->audience_type)); ?></span>
                                        <?php if($announcement->course): ?>
                                            <div class="user-email"><?php echo e($announcement->course->title); ?></div>
                                        <?php elseif($announcement->class): ?>
                                            <div class="user-email"><?php echo e($announcement->class->code); ?></div>
                                        <?php elseif($announcement->targetRole): ?>
                                            <div class="user-email"><?php echo e($announcement->targetRole->name); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($announcement->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($announcement->status).'']); ?>
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
                                        <div><?php echo e($announcement->created_at?->format('M j, Y')); ?></div>
                                        <div class="user-email"><?php echo e($announcement->creator?->name ?? '—'); ?></div>
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            <?php if($announcement->status === 'published'): ?>
                                                <form method="POST" action="<?php echo e(route('admin.announcements.publish', $announcement)); ?>" onsubmit="return confirm('Unpublish this announcement?')">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-icon" title="Unpublish"><i class="fa-solid fa-eye-slash"></i></button>
                                                </form>
                                            <?php elseif(in_array($announcement->status, ['draft', 'scheduled'], true)): ?>
                                                <form method="POST" action="<?php echo e(route('admin.announcements.publish', $announcement)); ?>">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-icon" title="Publish"><i class="fa-solid fa-paper-plane"></i></button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" action="<?php echo e(route('admin.announcements.pin', $announcement)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-icon" title="<?php echo e($announcement->is_pinned ? 'Unpin' : 'Pin'); ?>">
                                                    <i class="fa-solid <?php echo e($announcement->is_pinned ? 'fa-thumbtack' : 'fa-regular fa-thumbtack'); ?>"></i>
                                                </button>
                                            </form>
                                            <a class="btn btn-icon" href="<?php echo e(route('admin.announcements.show', $announcement)); ?>" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="<?php echo e(route('admin.announcements.edit', $announcement)); ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form method="POST" action="<?php echo e(route('admin.announcements.destroy', $announcement)); ?>" onsubmit="return confirm('Delete this announcement?')">
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-bullhorn','title' => 'No announcements found','description' => 'Create an announcement to reach students, classes, or the whole institution.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-bullhorn','title' => 'No announcements found','description' => 'Create an announcement to reach students, classes, or the whole institution.']); ?>
                     <?php $__env->slot('action', null, []); ?> 
                        <a class="btn btn-primary" href="<?php echo e(route('admin.announcements.create')); ?>"><i class="fa-solid fa-plus"></i> New Announcement</a>
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

            <?php if($announcements->hasPages()): ?>
                <div class="pagination"><?php echo e($announcements->appends(request()->query())->links()); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/admin/announcements/index.blade.php ENDPATH**/ ?>