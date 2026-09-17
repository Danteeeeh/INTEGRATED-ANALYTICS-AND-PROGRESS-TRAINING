<?php
    $title = $title ?? 'Page';
    $subtitle = $subtitle ?? null;
    $icon = $icon ?? 'fa-layer-group';
    $accent = $accent ?? 'var(--user-accent)';
?>
<section class="user-hero" style="--page-accent: <?php echo e($accent); ?>" aria-labelledby="user-page-title">
    <div class="user-hero-copy">
        <div class="user-kicker"><i class="fa-solid <?php echo e($icon); ?>" aria-hidden="true"></i> <?php echo e($kicker ?? ucfirst(auth()->user()?->role?->slug ?? 'User')); ?></div>
        <h1 id="user-page-title"><?php echo e($title); ?></h1>
        <?php if($subtitle): ?><p><?php echo e($subtitle); ?></p><?php endif; ?>
        <?php if(isset($meta)): ?><div class="user-hero-meta"><?php echo e($meta); ?></div><?php endif; ?>
    </div>
    <?php if(isset($actions)): ?><div class="user-hero-actions"><?php echo e($actions); ?></div><?php endif; ?>
</section>
<?php /**PATH C:\xampp\htdocs\lms\resources\views\components\user-page-header.blade.php ENDPATH**/ ?>