<?php
    $status = strtolower((string) ($status ?? 'unknown'));
    $label = $label ?? ucfirst(str_replace('_', ' ', $status));
?>
<span class="user-status <?php echo e($status); ?>"><i class="fa-solid <?php echo e(in_array($status, ['active','published','completed']) ? 'fa-circle-check' : ($status === 'pending' ? 'fa-clock' : 'fa-circle')); ?>" aria-hidden="true"></i><?php echo e($label); ?></span>
<?php /**PATH C:\xampp\htdocs\lms\resources\views\components\user-status-badge.blade.php ENDPATH**/ ?>