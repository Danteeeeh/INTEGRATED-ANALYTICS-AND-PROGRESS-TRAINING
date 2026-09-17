<style>
    .tab-action-row { display: flex; justify-content: flex-end; margin-bottom: 14px; }
    .module-mini-card { padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; gap: 14px; }
    .module-mini-icon { width: 36px; height: 36px; border-radius: 8px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .module-mini-body { flex: 1; }
    .module-mini-title { font-weight: 600; font-size: 0.9rem; }
    .module-mini-meta { font-size: 0.76rem; color: #64748b; margin-top: 2px; }
    .badge-published { padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; background: #dcfce7; color: #16a34a; }
    .badge-draft { padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; background: #fee2e2; color: #dc2626; }
</style>

<div class="crud-card" style="margin: 0 24px 24px;">
    <div class="crud-header">
        <h3>Modules (<?php echo e($course->modules->count() ?? 0); ?>)</h3>
        <div class="tab-action-row">
            <a href="<?php echo e(route('instructor.courses.modules.create', $course)); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Create Module
            </a>
        </div>
    </div>
    <div style="padding: 16px 24px;">
        <?php if(($course->modules->count() ?? 0) > 0): ?>
            <?php $__currentLoopData = $course->modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="module-mini-card">
                    <div class="module-mini-icon"><i class="fa-solid fa-layer-group"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title">#<?php echo e($module->order ?? $loop->iteration); ?> — <?php echo e($module->title); ?></div>
                        <div class="module-mini-meta">
                            <i class="fa-solid fa-book-open"></i> <?php echo e($module->lessons->count() ?? 0); ?> lessons
                            <span style="margin: 0 8px;">·</span>
                            <?php echo $module->is_published
                                ? '<span class="badge-published">Published</span>'
                                : '<span class="badge-draft">Draft</span>'; ?>

                        </div>
                    </div>
                    <a href="<?php echo e(route('instructor.courses.modules.show', [$course, $module])); ?>" class="btn-icon btn-view" title="View">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <div style="margin-top: 10px; text-align: center;">
                <a href="<?php echo e(route('instructor.courses.modules.index', $course)); ?>" style="color:#2563eb;text-decoration:underline;font-weight:600;font-size:0.84rem;">
                    View All Modules →
                </a>
            </div>
        <?php else: ?>
            <div style="text-align:center;padding:32px;color:#aaa;">
                <div style="font-size: 2.5rem; margin-bottom: 10px;"><i class="fa-solid fa-layer-group"></i></div>
                No modules yet. <a href="<?php echo e(route('instructor.courses.modules.create', $course)); ?>" style="color:#2563eb;text-decoration:underline;">Create one</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\modules\_tab_content.blade.php ENDPATH**/ ?>