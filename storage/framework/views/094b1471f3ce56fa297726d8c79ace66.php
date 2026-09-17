<?php
    $icon = $icon ?? 'fa-inbox';
    $title = $title ?? 'Nothing here yet';
    $description = $description ?? null;
?>
<div class="user-empty">
    <i class="fa-solid <?php echo e($icon); ?>" aria-hidden="true"></i>
    <h3><?php echo e($title); ?></h3>
    <?php if($description): ?><p><?php echo e($description); ?></p><?php endif; ?>
    <?php if(isset($action)): ?><?php echo e($action); ?><?php endif; ?>
</div>
<?php /**PATH C:\xampp\htdocs\lms\resources\views/components/user-empty-state.blade.php ENDPATH**/ ?>