<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['id', 'title', 'size' => 'normal', 'closeButton' => true]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['id', 'title', 'size' => 'normal', 'closeButton' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="modal-overlay" id="<?php echo e($id); ?>-overlay">
  <div class="modal <?php echo e(($size === 'large' ? 'modal-lg' : ($size === 'small' ? 'modal-sm' : ''))); ?>">
    <div class="modal-header">
      <span><?php echo e($title); ?></span>
      <?php if($closeButton): ?>
        <button class="modal-close" data-close="<?php echo e($id); ?>-overlay">&times;</button>
      <?php endif; ?>
    </div>
    <div class="modal-body">
      <?php echo e($slot); ?>

    </div>
    <?php if(isset($footer)): ?>
      <div class="modal-footer <?php echo e(isset($footerSplit) && $footerSplit ? 'modal-footer-split' : ''); ?>">
        <?php echo e($footer); ?>

      </div>
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('<?php echo e($id); ?>-overlay');
    const closeButtons = overlay.querySelectorAll('[data-close="<?php echo e($id); ?>-overlay"]');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            overlay.classList.remove('active');
        });
    });
    
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.classList.remove('active');
        }
    });
});
</script><?php /**PATH C:\xampp\htdocs\lms\resources\views\components\sms-modal.blade.php ENDPATH**/ ?>