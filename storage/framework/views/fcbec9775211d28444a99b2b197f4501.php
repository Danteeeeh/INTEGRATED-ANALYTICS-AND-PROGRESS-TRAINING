<?php $__env->startSection('title', $certificate->certificate_number ?? 'Certificate'); ?>
<?php $activeNav = 'certificates'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => ''.e($certificate->certificate_number ?? 'Certificate').'','subtitle' => 'Certificate details and verification information.','icon' => 'fa-certificate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($certificate->certificate_number ?? 'Certificate').'','subtitle' => 'Certificate details and verification information.','icon' => 'fa-certificate']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($certificate->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($certificate->status).'']); ?>
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
            <?php if($certificate->status === 'revoked'): ?>
                <span class="user-status">Revoked <?php echo e($certificate->revoked_at?->format('M j, Y')); ?></span>
            <?php endif; ?>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.certificates.download', $certificate)); ?>" class="btn btn-primary"><i class="fa-solid fa-download"></i> Download</a>
            <a href="<?php echo e(route('admin.certificates.edit', $certificate)); ?>" class="btn btn-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="<?php echo e(route('admin.certificates.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
            <h3><i class="fa-solid fa-info-circle"></i> Certificate Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Certificate Number</label>
                    <div><strong><?php echo e($certificate->certificate_number); ?></strong></div>
                </div>
                <div class="form-field">
                    <label>Verification Code</label>
                    <div><code><?php echo e($certificate->verification_code); ?></code></div>
                </div>
                <div class="form-field">
                    <label>Student</label>
                    <div><?php echo e($certificate->student?->name ?? $certificate->student_name_display ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Course</label>
                    <div><?php echo e($certificate->course_name_display ?? $certificate->course?->title ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Class</label>
                    <div><?php echo e($certificate->class?->code ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Issued At</label>
                    <div><?php echo e($certificate->issued_at?->format('M j, Y g:i A') ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Completion Date</label>
                    <div><?php echo e($certificate->completion_date?->format('M j, Y') ?? '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Final Grade</label>
                    <div><?php echo e($certificate->final_grade !== null ? $certificate->final_grade.'%' : '—'); ?></div>
                </div>
                <div class="form-field">
                    <label>Template</label>
                    <div><?php echo e($certificate->template_name ?? 'Standard'); ?></div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($certificate->status).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($certificate->status).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $attributes = $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4)): ?>
<?php $component = $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4; ?>
<?php unset($__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4); ?>
<?php endif; ?></div>
                </div>
                <?php if($certificate->revoke_reason): ?>
                    <div class="form-field full">
                        <label>Revoke Reason</label>
                        <div><?php echo e($certificate->revoke_reason); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-shield-check"></i> Public Verification</h3>
        </div>
        <div class="user-panel-body">
            <p class="user-email">Anyone can verify this certificate by visiting the public verification page with the verification code:</p>
            <div class="user-toolbar">
                <input class="form-control" readonly value="<?php echo e(url('/certificate/verify/' . $certificate->verification_code)); ?>" aria-label="Verification URL">
                <a class="btn btn-secondary" href="<?php echo e(url('/certificate/verify/' . $certificate->verification_code)); ?>" target="_blank" rel="noopener">
                    <i class="fa-solid fa-up-right-from-square"></i> Open
                </a>
            </div>
        </div>
    </div>

    <div class="user-actions">
        <?php if($certificate->status === 'issued'): ?>
            <form action="<?php echo e(route('admin.certificates.destroy', $certificate)); ?>" method="POST" onsubmit="return confirm('Revoke this certificate?')">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Revoke</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\certificates\show.blade.php ENDPATH**/ ?>