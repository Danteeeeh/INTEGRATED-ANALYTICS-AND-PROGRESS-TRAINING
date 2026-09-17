<?php
    $label = $label ?? 'Metric';
    $value = $value ?? 0;
    $icon = $icon ?? 'fa-chart-simple';
    $valueId = $valueId ?? null;
?>
<section class="user-stat-card" aria-label="<?php echo e($label); ?>" <?php echo e($attributes); ?>>
    <div class="user-stat-top">
        <span class="user-stat-icon"><i class="fa-solid <?php echo e($icon); ?>" aria-hidden="true"></i></span>
        <?php if(isset($trend)): ?><span class="user-stat-trend"><?php echo e($trend); ?></span><?php endif; ?>
    </div>
    <span class="user-stat-label"><?php echo e($label); ?></span>
    <strong class="user-stat-value" <?php if($valueId): ?> id="<?php echo e($valueId); ?>" <?php endif; ?>><?php echo e($value); ?></strong>
    <?php if(isset($footer)): ?><span class="user-stat-foot"><?php echo e($footer); ?></span><?php endif; ?>
</section>
<?php /**PATH C:\xampp\htdocs\lms\resources\views/components/user-stat-card.blade.php ENDPATH**/ ?>