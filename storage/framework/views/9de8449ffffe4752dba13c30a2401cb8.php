<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['notifications' => []]));

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

foreach (array_filter((['notifications' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="notif-overlay" id="notifOverlay"></div>
<div class="notif-panel" id="notifPanel">
  <div class="notif-header">
    <span>Notifications</span>
    <div class="notif-header-actions">
      <button class="notif-mark-all" id="notifMarkAll">Mark all as read</button>
      <button class="notif-close" id="notifClose">&times;</button>
    </div>
  </div>
  <div class="notif-list" id="notifList">
    <?php if(count($notifications) > 0): ?>
      <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="notif-item <?php echo e(($notification['unread'] ?? false) ? 'unread' : ''); ?>" data-notif="<?php echo e($notification['id'] ?? loop->index); ?>">
          <?php if(($notification['unread'] ?? false)): ?>
            <span class="notif-dot"></span>
          <?php endif; ?>
          <div class="notif-text">
            <div class="notif-title"><?php echo e($notification['title']); ?></div>
            <div class="notif-desc"><?php echo e($notification['description']); ?></div>
          </div>
          <span class="notif-time"><?php echo e($notification['time'] ?? 'Just now'); ?></span>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php else: ?>
      <div class="notif-empty">No notifications</div>
    <?php endif; ?>
  </div>
</div><?php /**PATH C:\xampp\htdocs\lms\resources\views/components/sms-notifications.blade.php ENDPATH**/ ?>