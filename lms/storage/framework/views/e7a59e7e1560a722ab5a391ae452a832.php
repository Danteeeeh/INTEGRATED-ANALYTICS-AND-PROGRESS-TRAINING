<?php
    $buttonLink ??= null;
    $height ??= '300px';
?>

<div class="chart-card">
  <div class="chart-header">
    <div>
      <h3><?php echo e($title); ?></h3>
      <?php if($subtitle): ?>
        <div class="chart-sub"><?php echo e($subtitle); ?></div>
      <?php endif; ?>
    </div>
    <?php if($buttonText && $buttonLink): ?>
      <a href="<?php echo e($buttonLink); ?>" class="card-btn">
        <i class="fa-solid fa-arrow-right"></i>
        <?php echo e($buttonText); ?>

      </a>
    <?php endif; ?>
  </div>
  <div class="chart-wrap" style="height: <?php echo e($height); ?>">
    <canvas id="<?php echo e($chartId); ?>"></canvas>
  </div>
</div><?php /**PATH C:\xampp\htdocs\lms\resources\views\components\sms-chart-card.blade.php ENDPATH**/ ?>