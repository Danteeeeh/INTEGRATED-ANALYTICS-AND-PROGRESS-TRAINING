<?php $__env->startSection('title', 'Audit Log Entry'); ?>
<?php $activeNav = 'audit_logs'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Audit Log Entry','subtitle' => 'Details of a single audited action.','icon' => 'fa-shield-halved']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Audit Log Entry','subtitle' => 'Details of a single audited action.','icon' => 'fa-shield-halved']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <span class="user-status"><?php echo e($auditLog->action); ?></span>
            <span><?php echo e($auditLog->created_at?->format('M j, Y g:i:s A')); ?></span>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.audit_logs.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Metadata</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Action</label>
                    <div><strong><?php echo e($auditLog->action); ?></strong></div>
                </div>
                <div class="form-field">
                    <label>Resource</label>
                    <div><?php echo e($auditLog->resource_type); ?> #<?php echo e($auditLog->resource_id); ?></div>
                </div>
                <div class="form-field">
                    <label>User</label>
                    <div><?php echo e($auditLog->user?->name ?? 'System'); ?></div>
                    <div class="user-email"><?php echo e($auditLog->user?->email); ?></div>
                </div>
                <div class="form-field">
                    <label>IP Address</label>
                    <div><?php echo e($auditLog->ip_address ?? '—'); ?></div>
                </div>
                <div class="form-field full">
                    <label>User Agent</label>
                    <div style="word-break:break-all"><?php echo e($auditLog->user_agent ?? '—'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if($auditLog->old_values || $auditLog->new_values): ?>
        <div class="user-panel">
            <div class="user-panel-head">
                <h3><i class="fa-solid fa-code-compare"></i> Before / After</h3>
            </div>
            <div class="user-panel-body">
                <?php
                    $keys = collect(array_merge(array_keys($auditLog->old_values ?? []), array_keys($auditLog->new_values ?? [])))
                        ->unique()->sort()->values();
                ?>
                <?php if($keys->count() > 0): ?>
                    <div class="user-table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Before</th>
                                    <th>After</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $keys; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><strong><?php echo e($key); ?></strong></td>
                                        <td>
                                            <?php if(isset($auditLog->old_values[$key])): ?>
                                                <pre style="margin:0;white-space:pre-wrap;word-break:break-word"><?php echo e(is_array($auditLog->old_values[$key]) ? json_encode($auditLog->old_values[$key], JSON_PRETTY_PRINT) : $auditLog->old_values[$key]); ?></pre>
                                            <?php else: ?>
                                                <span class="user-email">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(isset($auditLog->new_values[$key])): ?>
                                                <pre style="margin:0;white-space:pre-wrap;word-break:break-word"><?php echo e(is_array($auditLog->new_values[$key]) ? json_encode($auditLog->new_values[$key], JSON_PRETTY_PRINT) : $auditLog->new_values[$key]); ?></pre>
                                            <?php else: ?>
                                                <span class="user-email">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="user-actions">
        <a href="<?php echo e(route('admin.audit_logs.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Audit Logs</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\audit_logs\show.blade.php ENDPATH**/ ?>