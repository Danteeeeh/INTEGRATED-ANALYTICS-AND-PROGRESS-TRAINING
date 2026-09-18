<?php $__env->startSection('title', $discussion->title); ?>
<?php $activeNav = 'discussions'; ?>

<?php $__env->startSection('content'); ?>
<div class="learning-shell">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => ''.e($discussion->title).'','subtitle' => ''.e($course->title).' — discussion','icon' => 'fa-comments']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($discussion->title).'','subtitle' => ''.e($course->title).' — discussion','icon' => 'fa-comments']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <?php if($discussion->is_pinned): ?><span class="user-status active">Pinned</span><?php endif; ?>
            <?php if($discussion->is_locked): ?><span class="user-status">Locked</span><?php endif; ?>
            <span class="user-status"><?php echo e($discussion->posts->count()); ?> posts</span>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <form method="POST" action="<?php echo e(route('student.courses.discussions.subscribe', [$course, $discussion])); ?>" class="inline-form">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-secondary">
                    <i class="fa-solid <?php echo e($isSubscribed ? 'fa-bell-slash' : 'fa-bell'); ?>"></i> <?php echo e($isSubscribed ? 'Unsubscribe' : 'Subscribe'); ?>

                </button>
            </form>
            <a href="<?php echo e(route('student.courses.discussions.index', $course)); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
        <div class="user-panel-head"><h3><i class="fa-solid fa-comment-dots"></i> Discussion</h3></div>
        <div class="user-panel-body">
            <?php if($discussion->body): ?>
                <div style="white-space:pre-line"><?php echo e($discussion->body); ?></div>
            <?php else: ?>
                <p class="user-email">No description provided.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-list"></i> Posts</h3>
            <span class="user-status"><?php echo e($discussion->posts->count()); ?> posts</span>
        </div>
        <div class="user-panel-body">
            <?php if($discussion->posts->count() > 0): ?>
                <?php $__currentLoopData = $discussion->posts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="user-toolbar" style="flex-direction:column;align-items:stretch;margin-bottom:12px">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
                            <strong><?php echo e($post->author?->name ?? $post->creator?->name ?? '—'); ?></strong>
                            <span class="user-email"><?php echo e($post->created_at?->format('M j, Y g:i A')); ?></span>
                        </div>
                        <div style="white-space:pre-line"><?php echo e($post->body ?? $post->content ?? '—'); ?></div>

                        <?php $__currentLoopData = ($post->children ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div style="margin-left:18px;margin-top:8px;padding-left:10px;border-left:2px solid var(--dash-line)">
                                <strong><?php echo e($child->author?->name ?? '—'); ?></strong>
                                <div class="user-email"><?php echo e($child->created_at?->format('M j, Y g:i A')); ?></div>
                                <div style="white-space:pre-line"><?php echo e($child->body ?? $child->content ?? '—'); ?></div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-comments','title' => 'No posts yet','description' => 'Be the first to post in this discussion.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-comments','title' => 'No posts yet','description' => 'Be the first to post in this discussion.']); ?>
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

            <?php if(!$discussion->is_locked): ?>
                <form action="<?php echo e(route('student.courses.discussions.posts.store', [$course, $discussion])); ?>" method="POST" style="margin-top:16px">
                    <?php echo csrf_field(); ?>
                    <div class="form-field full">
                        <label for="postBody">Add a reply</label>
                        <textarea id="postBody" name="body" rows="4" required placeholder="Write your reply..."><?php echo e(old('body')); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('body')); ?></span>
                    </div>
                    <div class="form-actions user-actions">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Post Reply</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="user-toolbar">
                    <span class="user-status">This discussion is locked</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\discussions\show.blade.php ENDPATH**/ ?>