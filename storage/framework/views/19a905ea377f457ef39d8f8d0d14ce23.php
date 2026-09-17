<?php $__env->startSection('title', 'Modules - ' . $course->name); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Modules';
    $pageIcon = '<i class="fa-solid fa-layer-group"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-layer-group"></i>
            Modules for <?php echo e($course->name); ?>

        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card">
        <div class="crud-header">
            <h3>Modules</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="<?php echo e(route('admin.courses.show', $course)); ?>" class="btn-add" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Course
                </a>
                <a href="<?php echo e(route('admin.courses.modules.create', $course)); ?>" class="btn-add">
                    <i class="fa-solid fa-plus"></i>
                    Add Module
                </a>
            </div>
        </div>

        <?php if($modules->count() > 0): ?>
            <form id="reorderForm" method="POST" action="<?php echo e(route('admin.courses.modules.reorder', $course)); ?>">
                <?php echo csrf_field(); ?>
            </form>
        <?php endif; ?>

        <table class="crud-table" id="sortable-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Position</th>
                    <th>Title</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 280px;">Actions</th>
                </tr>
            </thead>
            <tbody id="sortable-tbody">
                <?php $__empty_1 = true; $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr data-id="<?php echo e($module->id); ?>" style="cursor: move;">
                        <td class="position-cell">
                            <i class="fa-solid fa-grip-vertical" style="color: #94a3b8; margin-right: 8px;"></i>
                            <span class="position-num"><?php echo e($loop->iteration); ?></span>
                            <input type="hidden" name="order[]" form="reorderForm" value="<?php echo e($module->id); ?>">
                        </td>
                        <td>
                            <strong><?php echo e($module->title); ?></strong>
                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">
                                <?php echo e($module->lessons->count()); ?> lesson(s)
                            </div>
                        </td>
                        <td>
                            <?php if($module->status === 'published'): ?>
                                <span class="badge-active">Published</span>
                            <?php else: ?>
                                <span class="badge-inactive">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <a href="<?php echo e(route('admin.courses.modules.show', [$course, $module])); ?>" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('admin.courses.modules.lessons.index', [$course, $module])); ?>" class="btn-icon btn-edit" title="Manage Lessons" style="background: #0ea5e9;">
                                <i class="fa-solid fa-book-open"></i>
                            </a>
                            <a href="<?php echo e(route('admin.courses.modules.edit', [$course, $module])); ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <?php if($module->status === 'published'): ?>
                                <form method="POST" action="<?php echo e(route('admin.courses.modules.unpublish', [$course, $module])); ?>" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn-icon" title="Unpublish" style="background: #f59e0b;">
                                        <i class="fa-solid fa-eye-slash"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="<?php echo e(route('admin.courses.modules.publish', [$course, $module])); ?>" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn-icon" title="Publish" style="background: #22c55e;">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" action="<?php echo e(route('admin.courses.modules.destroy', [$course, $module])); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this module and all its lessons?');">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:#aaa;">
                            No modules found for this course. <a href="<?php echo e(route('admin.courses.modules.create', $course)); ?>" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if($modules->count() > 1): ?>
            <div style="padding: 16px 24px; border-top: 1px solid var(--border-color, #e2e8f0); display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: #64748b;">
                    <i class="fa-solid fa-info-circle"></i> Drag and drop rows to reorder modules
                </span>
                <button type="submit" form="reorderForm" class="btn-submit" style="padding: 9px 24px; font-size: 0.85rem;">
                    <i class="fa-solid fa-save"></i> Save Order
                </button>
            </div>
        <?php endif; ?>

        <?php if($modules->hasPages()): ?>
            <div style="padding: 16px 24px;">
                <?php echo e($modules->appends(request()->query())->links()); ?>

            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.1/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('sortable-tbody');
    if (tbody) {
        Sortable.create(tbody, {
            animation: 150,
            handle: '.position-cell',
            onEnd: function() {
                const rows = tbody.querySelectorAll('tr[data-id]');
                rows.forEach((row, index) => {
                    const posNum = row.querySelector('.position-num');
                    if (posNum) posNum.textContent = index + 1;
                });
            }
        });
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\modules\index.blade.php ENDPATH**/ ?>