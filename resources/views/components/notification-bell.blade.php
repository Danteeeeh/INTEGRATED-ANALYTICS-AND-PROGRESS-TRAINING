@php
    $notifs = $sharedUnreadNotifications ?? collect();
    $count = $sharedUnreadCount ?? 0;
    $iconMap = [
        'grade' => 'fa-graduation-cap', 'quiz' => 'fa-question-circle',
        'assignment' => 'fa-file-lines', 'announcement' => 'fa-bullhorn',
        'virtual_class' => 'fa-video', 'attendance' => 'fa-calendar-check',
        'enrollment' => 'fa-user-plus', 'performance' => 'fa-chart-line',
        'achievement' => 'fa-trophy', 'info' => 'fa-circle-info',
    ];
@endphp
@once
<style>
.notif-bell-wrap{position:relative;z-index:1250;flex:0 0 auto}.notif-bell{position:relative;display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;min-width:36px;padding:0;color:#cfe7ff;background:rgba(77,143,240,.14);border:1px solid rgba(98,201,245,.28);border-radius:10px;cursor:pointer}.notif-bell:hover{color:#fff;background:rgba(77,143,240,.28);border-color:rgba(98,201,245,.55)}.notif-bell i{font-size:.9rem;line-height:1}.notif-badge{position:absolute;top:-5px;right:-5px;display:grid;place-items:center;min-width:17px;height:17px;padding:0 4px;color:#fff;background:#e4475f;border:2px solid #0b111e;border-radius:999px;font-size:.58rem;font-weight:800;line-height:1}.notif-dropdown{position:absolute;top:calc(100% + 10px);right:0;z-index:1300;display:none;width:min(360px,calc(100vw - 24px));max-height:420px;overflow:hidden;color:#dce8ff;background:#151c2c;border:1px solid rgba(153,174,214,.22);border-radius:12px;box-shadow:0 18px 40px rgba(0,0,0,.38)}.notif-bell-wrap.open .notif-dropdown{display:block}.notif-dd-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;color:#fff;border-bottom:1px solid rgba(153,174,214,.18);font-size:.78rem;font-weight:800}.notif-mark-all{padding:4px 0;color:#62c9f5;background:transparent;border:0;font-size:.68rem;cursor:pointer}.notif-dd-list{max-height:360px;overflow-y:auto}.notif-dd-item{display:flex;gap:10px;padding:11px 13px;color:#dce8ff;border-bottom:1px solid rgba(153,174,214,.1);text-decoration:none}.notif-dd-item:hover,.notif-dd-item.unread{background:rgba(77,143,240,.12)}.notif-dd-icon{display:grid;place-items:center;flex:0 0 28px;width:28px;height:28px;color:#62c9f5;background:rgba(98,201,245,.12);border-radius:8px;font-size:.75rem}.notif-dd-body{display:flex;min-width:0;flex-direction:column;gap:2px}.notif-dd-title{color:#fff;font-size:.73rem;font-weight:750}.notif-dd-text,.notif-dd-time{color:#9aa9c7;font-size:.68rem;line-height:1.35}.notif-dd-time{color:#7184a5}.notif-dd-empty{display:flex;align-items:center;justify-content:center;gap:8px;padding:28px 16px;color:#9aa9c7;font-size:.75rem}body.light-mode .notif-bell{color:#2449c6;background:rgba(36,73,198,.09);border-color:rgba(36,73,198,.2)}body.light-mode .notif-badge{border-color:#fff}body.light-mode .notif-dropdown{color:#263753;background:#fff;border-color:rgba(31,52,88,.16);box-shadow:0 18px 40px rgba(31,52,88,.18)}body.light-mode .notif-dd-head,body.light-mode .notif-dd-title{color:#12203a}body.light-mode .notif-dd-item{color:#263753;border-color:rgba(31,52,88,.1)}body.light-mode .notif-dd-text,body.light-mode .notif-dd-time,body.light-mode .notif-dd-empty{color:#5f708c}
</style>
@endonce
<div class="notif-bell-wrap" id="notifBellWrap">
    <button type="button" class="notif-bell" id="notifBellBtn" aria-label="Notifications" aria-haspopup="true" aria-expanded="false" title="Notifications">
        <i class="fa-solid fa-bell"></i>
        @if($count > 0)
            <span class="notif-badge" id="notifBadge">{{ $count > 99 ? '99+' : $count }}</span>
        @endif
    </button>

    <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-dd-head">
            <span>Notifications</span>
            @if($count > 0)
                <button type="button" class="notif-mark-all" id="notifMarkAllBtn" onclick="markAllNotificationsRead(event)">
                    <i class="fa-solid fa-check-double"></i> Mark all read
                </button>
            @endif
        </div>
        <div class="notif-dd-list">
            @forelse($notifs as $n)
                <a class="notif-dd-item {{ $n->read_at ? '' : 'unread' }}" href="{{ $n->link_url ? url($n->link_url) : '#' }}" data-id="{{ $n->id }}" onclick="openNotification(event, {{ $n->id }})">
                    <span class="notif-dd-icon"><i class="fa-solid {{ $iconMap[$n->type] ?? 'fa-circle-info' }}"></i></span>
                    <span class="notif-dd-body">
                        <span class="notif-dd-title">{{ $n->title }}</span>
                        <span class="notif-dd-text">{{ Str::limit($n->body ?? '', 70) }}</span>
                        <span class="notif-dd-time">{{ $n->created_at?->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <div class="notif-dd-empty">
                    <i class="fa-solid fa-bell-slash"></i>
                    <span>You're all caught up</span>
                </div>
            @endforelse
        </div>
    </div>
</div>
