@props(['id', 'title', 'size' => 'normal', 'closeButton' => true])

<div class="modal-overlay" id="{{ $id }}-overlay">
  <div class="modal {{ ($size === 'large' ? 'modal-lg' : ($size === 'small' ? 'modal-sm' : '')) }}">
    <div class="modal-header">
      <span>{{ $title }}</span>
      @if($closeButton)
        <button class="modal-close" data-close="{{ $id }}-overlay">&times;</button>
      @endif
    </div>
    <div class="modal-body">
      {{ $slot }}
    </div>
    @if(isset($footer))
      <div class="modal-footer {{ isset($footerSplit) && $footerSplit ? 'modal-footer-split' : '' }}">
        {{ $footer }}
      </div>
    @endif
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('{{ $id }}-overlay');
    const closeButtons = overlay.querySelectorAll('[data-close="{{ $id }}-overlay"]');
    
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
</script>