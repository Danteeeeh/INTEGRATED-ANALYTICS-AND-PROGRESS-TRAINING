<?php $__env->startSection('title', 'Certificate Verification'); ?>

<?php $__env->startSection('content'); ?>
<div class="user-page" style="max-width:720px;margin:0 auto">
    <?php if($valid ?? false): ?>
        <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Certificate Verified','subtitle' => 'This certificate is authentic and was issued by '.e(config('app.name')).'.','icon' => 'fa-shield-check','kicker' => 'Public verification']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Certificate Verified','subtitle' => 'This certificate is authentic and was issued by '.e(config('app.name')).'.','icon' => 'fa-shield-check','kicker' => 'Public verification']); ?>
             <?php $__env->slot('meta', null, []); ?> 
                <span class="user-status active"><i class="fa-solid fa-circle-check"></i> Valid</span>
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
                <h3><i class="fa-solid fa-certificate"></i> Certificate Details</h3>
            </div>
            <div class="user-panel-body">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Certificate Number</label>
                        <div><strong><?php echo e($certificate['certificate_number'] ?? '—'); ?></strong></div>
                    </div>
                    <div class="form-field">
                        <label>Issued To</label>
                        <div><?php echo e($certificate['student_name'] ?? '—'); ?></div>
                    </div>
                    <div class="form-field">
                        <label>Course</label>
                        <div><?php echo e($certificate['course_title'] ?? '—'); ?></div>
                    </div>
                    <div class="form-field">
                        <label>Issue Date</label>
                        <div><?php echo e(optional($certificate['issue_date'] ?? null)?->format('F j, Y') ?? $certificate['issue_date'] ?? '—'); ?></div>
                    </div>
                    <?php if(($certificate['final_grade'] ?? null) !== null): ?>
                        <div class="form-field">
                            <label>Final Grade</label>
                            <div><?php echo e($certificate['final_grade']); ?>%</div>
                        </div>
                    <?php endif; ?>
                    <div class="form-field">
                        <label>Status</label>
                        <div><?php if (isset($component)) { $__componentOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb995150083e77f6e2d42b13b0b4a3cf4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-status-badge','data' => ['status' => ''.e($certificate['status'] ?? 'issued').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => ''.e($certificate['status'] ?? 'issued').'']); ?>
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
                </div>
            </div>
        </div>

        <div class="user-toolbar">
            <i class="fa-solid fa-circle-check" style="color:var(--user-success)"></i>
            <span>This certificate has been verified as authentic.</span>
        </div>
    <?php else: ?>
        <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Certificate Not Found','subtitle' => 'We could not verify a certificate with this code.','icon' => 'fa-shield-xmark','kicker' => 'Public verification']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Certificate Not Found','subtitle' => 'We could not verify a certificate with this code.','icon' => 'fa-shield-xmark','kicker' => 'Public verification']); ?>
             <?php $__env->slot('meta', null, []); ?> 
                <span class="user-status"><i class="fa-solid fa-xmark"></i> Invalid</span>
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
            <div class="user-panel-body">
                <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-shield-xmark','title' => 'Verification failed','description' => 'The verification code you provided does not match any issued certificate. Please check the code and try again.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-shield-xmark','title' => 'Verification failed','description' => 'The verification code you provided does not match any issued certificate. Please check the code and try again.']); ?>
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
            </div>
        </div>
    <?php endif; ?>

    <div class="user-actions" style="justify-content:center">
        <a href="<?php echo e(url('/')); ?>" class="btn btn-secondary"><i class="fa-solid fa-home"></i> Back to Home</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\certificate\verify.blade.php ENDPATH**/ ?>