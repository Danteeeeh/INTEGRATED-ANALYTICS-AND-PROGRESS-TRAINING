<?php
    $details ??= [];
    $buttonLink ??= null;
?>

<div class="info-card">
  <div class="card-label">
    <?php if($icon): ?>
      <i class="<?php echo e($icon); ?>"></i>
    <?php endif; ?>
    <?php echo e($label); ?>

  </div>
  <?php if($name): ?>
    <div class="card-name"><?php echo e($name); ?></div>
  <?php endif; ?>
  <?php if($amount): ?>
    <div class="card-amount"><?php echo e($amount); ?></div>
  <?php endif; ?>
  <?php if($status): ?>
    <div class="card-status"><?php echo e($status); ?></div>
  <?php endif; ?>
  <?php if(isset($details) && is_iterable($details)): ?>
    <?php $__currentLoopData = $details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="card-detail"><?php echo e($detail); ?></div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  <?php endif; ?>
  <?php if($buttonText && $buttonLink): ?>
    <a href="<?php echo e($buttonLink); ?>" class="card-btn">
      <i class="fa-solid fa-arrow-right"></i>
      <?php echo e($buttonText); ?>

    </a>
  <?php endif; ?>
</div><?php /**PATH C:\xampp\htdocs\lms\resources\views\components\sms-info-card.blade.php ENDPATH**/ ?>