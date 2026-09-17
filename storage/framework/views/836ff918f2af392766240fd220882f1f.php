<?php $__env->startSection('title', $module->title); ?>
<?php $activeNav = 'courses'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page module-detail-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => $module->title,'subtitle' => 'Manage this module’s lessons, content, and publishing state.','icon' => 'fa-layer-group']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module->title),'subtitle' => 'Manage this module’s lessons, content, and publishing state.','icon' => 'fa-layer-group']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('instructor.courses.modules.edit', [$course, $module])); ?>" class="btn btn-primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit Module
            </a>
            <a href="<?php echo e(route('instructor.courses.modules.index', $course)); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Modules
            </a>
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

    <div class="module-detail-layout">
        <aside class="module-detail-summary" aria-label="Module summary">
            <div class="module-summary-top">
                <span class="module-summary-icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span>
                <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => $module->is_published ? 'published' : 'draft']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module->is_published ? 'published' : 'draft')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $attributes = $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $component = $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
            </div>
            <span class="module-summary-kicker">Module #<?php echo e($module->order ?? '—'); ?></span>
            <h2><?php echo e($module->title); ?></h2>
            <p><?php echo e(Str::limit((string) $module->description, 180)); ?></p>

            <dl class="module-summary-list">
                <div><dt><i class="fa-solid fa-book" aria-hidden="true"></i> Course</dt><dd><?php echo e($course->code); ?> — <?php echo e($course->name ?? $course->title); ?></dd></div>
                <div><dt><i class="fa-solid fa-list-ol" aria-hidden="true"></i> Position</dt><dd>#<?php echo e($module->order ?? '—'); ?></dd></div>
                <div><dt><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Lessons</dt><dd><?php echo e($module->lessons->count()); ?></dd></div>
            </dl>

            <form method="POST" action="<?php echo e(route('instructor.courses.modules.' . ($module->is_published ? 'unpublish' : 'publish'), [$course, $module])); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn <?php echo e($module->is_published ? 'btn-secondary' : 'btn-success'); ?>" style="width:100%;justify-content:center">
                    <i class="fa-solid <?php echo e($module->is_published ? 'fa-eye-slash' : 'fa-paper-plane'); ?>" aria-hidden="true"></i>
                    <?php echo e($module->is_published ? 'Unpublish Module' : 'Publish Module'); ?>

                </button>
            </form>
        </aside>

        <div class="module-detail-main">
            <section class="user-panel module-overview-panel" aria-labelledby="module-overview-title">
                <div class="user-panel-head">
                    <div>
                        <span class="user-kicker"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Overview</span>
                        <h3 id="module-overview-title">Module information</h3>
                    </div>
                </div>
                <div class="user-panel-body module-overview-body">
                    <?php if($module->description): ?>
                        <div class="module-overview-block"><span class="module-overview-label"><i class="fa-solid fa-align-left" aria-hidden="true"></i> Description</span><p><?php echo e($module->description); ?></p></div>
                    <?php endif; ?>
                    <?php if($module->objectives): ?>
                        <div class="module-overview-block"><span class="module-overview-label"><i class="fa-solid fa-bullseye" aria-hidden="true"></i> Learning objectives</span><p style="white-space:pre-wrap"><?php echo e($module->objectives); ?></p></div>
                    <?php endif; ?>
                    <?php if(!$module->description && !$module->objectives): ?>
                        <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-align-left','title' => 'No module description yet','description' => 'Add a description or learning objectives to guide your students.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-align-left','title' => 'No module description yet','description' => 'Add a description or learning objectives to guide your students.']); ?>
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
            </section>

            <section class="user-panel module-lessons-panel" aria-labelledby="module-lessons-title">
                <div class="user-panel-head">
                    <div>
                        <span class="user-kicker"><i class="fa-solid fa-list" aria-hidden="true"></i> Content</span>
                        <h3 id="module-lessons-title">Lessons in this module (<?php echo e($module->lessons->count()); ?>)</h3>
                    </div>
                    <div class="user-actions">
                        <a href="<?php echo e(route('instructor.courses.modules.lessons.index', [$course, $module])); ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-list" aria-hidden="true"></i> Manage</a>
                        <a href="<?php echo e(route('instructor.courses.modules.lessons.create', [$course, $module])); ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Lesson</a>
                    </div>
                </div>

                <div class="user-panel-body">
                    <?php if(($module->lessons->count() ?? 0) > 0): ?>
                        <div class="module-lesson-list">
                            <?php $__currentLoopData = $module->lessons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lesson): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <article class="module-lesson-card">
                                    <span class="module-lesson-icon lesson-type-<?php echo e($lesson->type ?? 'text'); ?>">
                                        <?php switch($lesson->type ?? 'text'):
                                            case ('video'): ?><i class="fa-solid fa-play" aria-hidden="true"></i><?php break; ?>
                                            <?php case ('quiz'): ?><i class="fa-solid fa-circle-question" aria-hidden="true"></i><?php break; ?>
                                            <?php case ('file'): ?><i class="fa-solid fa-file" aria-hidden="true"></i><?php break; ?>
                                            <?php default: ?><i class="fa-solid fa-book-open" aria-hidden="true"></i><?php break; ?>
                                        <?php endswitch; ?>
                                    </span>
                                    <div class="module-lesson-body">
                                        <strong><?php echo e($lesson->title); ?></strong>
                                        <span class="module-lesson-meta">
                                            <span><i class="fa-solid fa-list-ol" aria-hidden="true"></i> #<?php echo e($lesson->order ?? $loop->iteration); ?></span>
                                            <?php if($lesson->duration): ?><span><i class="fa-solid fa-clock" aria-hidden="true"></i> <?php echo e($lesson->duration); ?> min</span><?php endif; ?>
                                            <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => $lesson->is_published ? 'published' : 'draft']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($lesson->is_published ? 'published' : 'draft')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $attributes = $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $component = $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="user-actions">
                                        <a href="<?php echo e(route('instructor.courses.modules.lessons.show', [$course, $module, $lesson])); ?>" class="btn btn-icon" title="View lesson"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                        <a href="<?php echo e(route('instructor.courses.modules.lessons.edit', [$course, $module, $lesson])); ?>" class="btn btn-icon" title="Edit lesson"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></a>
                                        <form method="POST" action="<?php echo e(route('instructor.courses.modules.lessons.destroy', [$course, $module, $lesson])); ?>" onsubmit="return confirm('Delete this lesson?');">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-icon btn-danger" title="Delete lesson"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-book-open','title' => 'No lessons in this module yet','description' => 'Add the first lesson to start building this module’s content.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-book-open','title' => 'No lessons in this module yet','description' => 'Add the first lesson to start building this module’s content.']); ?>
                             <?php $__env->slot('action', null, []); ?> 
                                <a href="<?php echo e(route('instructor.courses.modules.lessons.create', [$course, $module])); ?>" class="btn btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Lesson</a>
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
                </div>
            </section>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
.module-detail-page{--module-accent:#c4b5fd;--module-accent-strong:#7c3aed;--module-accent-soft:rgba(139,92,246,.14);gap:16px}.module-detail-page .user-hero{display:flex;align-items:flex-end;justify-content:space-between;box-sizing:border-box}.module-detail-page *{box-sizing:border-box}.module-detail-layout{display:grid;grid-template-columns:minmax(230px,.38fr) minmax(0,1fr);gap:16px;align-items:start}.module-detail-summary{position:sticky;top:18px;display:grid;gap:15px;padding:20px;border:1px solid rgba(196,181,253,.26);border-radius:16px;background:linear-gradient(160deg,rgba(124,58,237,.2),rgba(15,31,75,.85));box-shadow:0 16px 36px rgba(3,8,20,.18)}.module-summary-top{display:flex;align-items:center;justify-content:space-between;gap:10px}.module-summary-icon{display:grid;place-items:center;width:44px;height:44px;border:1px solid rgba(196,181,253,.32);border-radius:13px;color:var(--module-accent);background:rgba(139,92,246,.16)}.module-summary-kicker{color:var(--module-accent);font-size:.62rem;font-weight:850;letter-spacing:.12em;text-transform:uppercase}.module-detail-summary h2{margin:3px 0 0;color:#fff;font-size:1.18rem;line-height:1.2}.module-detail-summary>p{margin:8px 0 0;color:rgba(238,244,255,.72);font-size:.72rem;line-height:1.5}.module-summary-list{display:grid;gap:12px;margin:0;padding:16px 0;border-top:1px solid rgba(219,234,254,.14);border-bottom:1px solid rgba(219,234,254,.14)}.module-summary-list div{display:grid;gap:4px}.module-summary-list dt{color:rgba(238,244,255,.55);font-size:.62rem;text-transform:uppercase;letter-spacing:.06em}.module-summary-list dt i{width:16px;color:var(--module-accent)}.module-summary-list dd{margin:0;color:#fff;font-size:.74rem;line-height:1.35}.module-overview-body{display:grid;gap:18px}.module-overview-block{display:grid;gap:7px}.module-overview-label{color:var(--module-accent-strong);font-size:.64rem;font-weight:850;letter-spacing:.09em;text-transform:uppercase}.module-overview-label i{margin-right:6px}.module-overview-block p{margin:0;color:var(--dash-muted);font-size:.78rem;line-height:1.65}.module-lesson-list{display:grid;gap:9px}.module-lesson-card{display:flex;align-items:center;gap:13px;min-width:0;padding:13px 14px;border:1px solid var(--dash-line);border-radius:12px;background:var(--dash-surface-raised);transition:border-color .16s,box-shadow .16s}.module-lesson-card:hover{border-color:rgba(196,181,253,.45);box-shadow:0 8px 20px rgba(3,8,20,.12)}.module-lesson-icon{display:grid;place-items:center;width:38px;height:38px;flex:none;border-radius:11px}.lesson-type-video{color:#93c5fd;background:rgba(59,130,246,.16)}.lesson-type-quiz{color:#c4b5fd;background:rgba(139,92,246,.16)}.lesson-type-file{color:#6ee7b7;background:rgba(16,185,129,.14)}.lesson-type-text{color:#fcd34d;background:rgba(251,191,36,.14)}.module-lesson-body{display:grid;gap:5px;min-width:0;flex:1}.module-lesson-body>strong{overflow:hidden;color:var(--dash-text);font-size:.8rem;text-overflow:ellipsis;white-space:nowrap}.module-lesson-meta{display:flex;align-items:center;flex-wrap:wrap;gap:12px;color:var(--dash-muted);font-size:.66rem}.module-lesson-meta>span{display:inline-flex;align-items:center;gap:5px}.module-lesson-meta .user-status{font-size:.6rem;padding:3px 8px}body.light-mode .module-detail-summary{background:linear-gradient(160deg,rgba(124,58,237,.94),rgba(20,16,45,.98))}.light-mode .module-detail-summary h2{color:#fff}@media(max-width:860px){.module-detail-page .user-hero{align-items:flex-start;flex-direction:column;padding:20px}.module-detail-layout{grid-template-columns:1fr}.module-detail-summary{position:static}}@media(max-width:620px){.module-lesson-card{align-items:flex-start;flex-wrap:wrap}.module-lesson-body{flex-basis:calc(100% - 52px)}.module-lesson-card .user-actions{margin-left:auto}.module-detail-summary{padding:17px}}@media(prefers-reduced-motion:reduce){.module-lesson-card{transition:none}}
</style>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\courses\modules\show.blade.php ENDPATH**/ ?>