<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-bullhorn"></i> Announcements (<?php echo e($announcements->count()); ?>)</h3>
        <a href="<?php echo e(route('instructor.courses.announcements.create', $course)); ?>" class="btn-add">
            <i class="fa-solid fa-plus"></i>
            Create Announcement
        </a>
    </div>
    <div style="padding: 16px 24px;">
        <?php if($announcements->isNotEmpty()): ?>
            <?php $__currentLoopData = $announcements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $announcement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(244,63,94,.13);color:#fda4af"><i class="fa-solid fa-bullhorn"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title"><?php echo e($announcement->title); ?></div>
                        <div class="module-mini-meta">
                            <i class="fa-solid fa-clock"></i> <?php echo e($announcement->created_at?->diffForHumans()); ?>

                            <?php if($announcement->is_pinned ?? false): ?>
                                <span style="margin: 0 8px;">·</span>
                                <span class="badge-published"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
                            <?php endif; ?>
                            <?php if(($announcement->publish_at ?? null) && $announcement->publish_at > now()): ?>
                                <span style="margin: 0 8px;">·</span>
                                <span class="badge-draft">Scheduled</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="<?php echo e(route('instructor.courses.announcements.show', [$course, $announcement])); ?>" class="btn-icon btn-view" title="View announcement">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <div class="tab-empty-state">
                <i class="fa-solid fa-bullhorn"></i>
                No announcements yet.
                <a href="<?php echo e(route('instructor.courses.announcements.create', $course)); ?>">Post one</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\_tab_announcements.blade.php ENDPATH**/ ?>