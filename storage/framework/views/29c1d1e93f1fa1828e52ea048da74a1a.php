<?php $__env->startSection('title', 'Discussions'); ?>
<?php
    $activeNav = 'courses';
    $pageTitle = 'Discussions';
    $pageIcon = '<i class="fa-solid fa-comments"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-comments"></i>
            Discussions
        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="crud-card">
        <div class="crud-header">
            <h3>All Discussions</h3>
            <a href="<?php echo e(route('admin.discussions.create')); ?>" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                New Discussion
            </a>
        </div>

        <div style="padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #fafafa;">
            <form method="GET" action="<?php echo e(route('admin.discussions.index')); ?>" data-filter-form style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; align-items: end;">
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Course</label>
                    <select name="course_id">
                        <option value="">All Courses</option>
                        <?php $__currentLoopData = $courses ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($course->id); ?>" <?php echo e(request('course_id') == $course->id ? 'selected' : ''); ?>>
                                <?php echo e($course->code); ?> - <?php echo e($course->name ?? $course->title); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Class</label>
                    <select name="class_id">
                        <option value="">All Classes</option>
                        <?php $__currentLoopData = $classes ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($class->id); ?>" <?php echo e(request('class_id') == $class->id ? 'selected' : ''); ?>>
                                <?php echo e($class->code); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Type</label>
                    <select name="type">
                        <option value="">All Types</option>
                        <option value="general" <?php echo e(request('type') == 'general' ? 'selected' : ''); ?>>General</option>
                        <option value="academic" <?php echo e(request('type') == 'academic' ? 'selected' : ''); ?>>Academic</option>
                        <option value="qna" <?php echo e(request('type') == 'qna' ? 'selected' : ''); ?>>Q&amp;A</option>
                        <option value="graded" <?php echo e(request('type') == 'graded' ? 'selected' : ''); ?>>Graded</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Search</label>
                    <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Title, content...">
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <a href="<?php echo e(route('admin.discussions.index')); ?>" style="padding: 8px 16px; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; display: inline-flex; align-items: center;">
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
                    <th>Type</th>
                    <th>Posts</th>
                    <th>Pinned</th>
                    <th>Locked</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $discussions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $discussion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #111827;"><?php echo e($discussion->title); ?></div>
                            <?php if($discussion->user): ?>
                                <div style="font-size: 0.75rem; color: #6b7280;">by <?php echo e($discussion->user->name); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($discussion->course->code ?? '-'); ?> - <?php echo e(($discussion->course->name ?? $discussion->course->title) ?? '-'); ?></td>
                        <td><?php echo e($discussion->class->code ?? '-'); ?></td>
                        <td>
                            <?php
                                $typeBadge = match($discussion->type) {
                                    'general' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'General'],
                                    'academic' => ['bg' => '#ede9fe', 'color' => '#6d28d9', 'label' => 'Academic'],
                                    'qna' => ['bg' => '#fef3c7', 'color' => '#b45309', 'label' => 'Q&amp;A'],
                                    'graded' => ['bg' => '#dcfce7', 'color' => '#15803d', 'label' => 'Graded'],
                                    default => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => ucfirst($discussion->type)],
                                };
                            ?>
                            <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; background: <?php echo e($typeBadge['bg']); ?>; color: <?php echo e($typeBadge['color']); ?>;">
                                <?php echo $typeBadge['label']; ?>

                            </span>
                        </td>
                        <td style="text-align: center; font-weight: 600;"><?php echo e(($discussion->posts_count ?? $discussion->posts->count()) ?? 0); ?></td>
                        <td style="text-align: center;">
                            <?php if($discussion->is_pinned): ?>
                                <span class="badge-active" title="Pinned"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
                            <?php else: ?>
                                <span style="color: #9ca3af; font-size: 0.85rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if($discussion->is_locked): ?>
                                <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; background: #fee2e2; color: #dc2626;" title="Locked">
                                    <i class="fa-solid fa-lock"></i> Locked
                                </span>
                            <?php else: ?>
                                <span style="color: #9ca3af; font-size: 0.85rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <form method="POST" action="<?php echo e(route('admin.discussions.pin', $discussion)); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn-icon" title="<?php echo e($discussion->is_pinned ? 'Unpin' : 'Pin'); ?>" style="background: <?php echo e(($discussion->is_pinned ? '#fef3c7' : '#f3f4f6')); ?>; color: <?php echo e(($discussion->is_pinned ? '#b45309' : '#6b7280')); ?>;">
                                    <i class="fa-solid fa-thumbtack"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?php echo e(route('admin.discussions.lock', $discussion)); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn-icon" title="<?php echo e($discussion->is_locked ? 'Unlock' : 'Lock'); ?>" style="background: <?php echo e(($discussion->is_locked ? '#fee2e2' : '#f3f4f6')); ?>; color: <?php echo e(($discussion->is_locked ? '#dc2626' : '#6b7280')); ?>;">
                                    <i class="fa-solid fa-lock"></i>
                                </button>
                            </form>
                            <a href="<?php echo e(route('admin.discussions.show', $discussion)); ?>" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('admin.discussions.edit', $discussion)); ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="<?php echo e(route('admin.discussions.destroy', $discussion)); ?>" class="inline" onsubmit="return confirm('Are you sure you want to delete this discussion?');">
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
                            No discussions found. <a href="<?php echo e(route('admin.discussions.create')); ?>" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if(method_exists($discussions, 'hasPages') && $discussions->hasPages()): ?>
            <div style="padding: 16px 24px; border-top: 1px solid #e5e7eb;">
                <?php echo e($discussions->appends(request()->query())->links()); ?>

            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\discussions\index.blade.php ENDPATH**/ ?>