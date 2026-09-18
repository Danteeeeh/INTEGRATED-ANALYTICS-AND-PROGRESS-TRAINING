@props(['notifications' => []])

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
    @if(count($notifications) > 0)
      @foreach($notifications as $notification)
        <div class="notif-item {{ ($notification['unread'] ?? false) ? 'unread' : '' }}" data-notif="{{ $notification['id'] ?? loop->index }}">
          @if(($notification['unread'] ?? false))
            <span class="notif-dot"></span>
          @endif
          <div class="notif-text">
            <div class="notif-title">{{ $notification['title'] }}</div>
            <div class="notif-desc">{{ $notification['description'] }}</div>
          </div>
          <span class="notif-time">{{ $notification['time'] ?? 'Just now' }}</span>
        </div>
      @endforeach
    @else
      <div class="notif-empty">No notifications</div>
    @endif
  </div>
</div>