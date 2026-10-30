@extends($layout)

@section('title', $virtualClass->title.' — Virtual Classroom')

@php
    $activeNav = 'virtual_classes';
    $pageTitle = 'Virtual Classroom';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title"><i class="fa-solid fa-video"></i> {{ $virtualClass->title }}</h2>
        <span class="vc-live-badge"><span></span> LIVE CLASS</span>
        <span class="vc-head-timer" id="vcTimer" hidden>00:00</span>
    </div>
@endsection

@push('styles')
<style>
    [hidden]{display:none!important}
    /* ── Meeting room shell ── */
    .vc-room{color:#e5e7eb;background:#0b1220;border-radius:18px;overflow:hidden;box-shadow:0 16px 44px rgba(2,6,23,.22);display:flex;flex-direction:column;min-height:640px}
    .vc-room-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 20px;background:#111827;border-bottom:1px solid #253047;flex-wrap:wrap}
    .vc-room-head h3{margin:0;font-size:1.02rem;color:#f9fafb}
    .vc-room-meta{font-size:.8rem;color:#9ca3af;margin-top:4px}
    .vc-live-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:#3f151b;color:#fecaca;font-size:.7rem;font-weight:800;letter-spacing:.08em}
    .vc-live-badge span{width:7px;height:7px;border-radius:50%;background:#ef4444;box-shadow:0 0 0 4px #ef444433;animation:vcPulse 1.8s infinite}
    @keyframes vcPulse{0%,100%{opacity:1}50%{opacity:.35}}
    .vc-head-timer{font-variant-numeric:tabular-nums;font-weight:800;font-size:.82rem;color:#93c5fd;background:#0b1220;border:1px solid #293449;border-radius:999px;padding:5px 11px}
    .vc-head-chip{display:inline-flex;align-items:center;gap:7px;font-size:.78rem;color:#cbd5e1;background:#0b1220;border:1px solid #293449;border-radius:999px;padding:5px 11px;font-weight:650;white-space:nowrap}
    .vc-head-chip i{color:#93c5fd}
    .vc-quality{display:inline-flex;align-items:flex-end;gap:2px;height:12px}
    .vc-quality b{width:3px;background:#374151;border-radius:1px;transition:background .2s}
    .vc-quality b:nth-child(1){height:4px}.vc-quality b:nth-child(2){height:7px}.vc-quality b:nth-child(3){height:10px}
    .vc-quality.good b{background:#34d399}.vc-quality.fair b{background:#fbbf24}.vc-quality.poor b{background:#ef4444}
    .vc-icon-btn{border:1px solid #293449;background:#0b1220;color:#cbd5e1;border-radius:9px;padding:6px 10px;font-size:.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px}
    .vc-icon-btn:hover{background:#1f2937;color:#f9fafb}
    .vc-icon-btn.instructor{background:#7c3aed;border-color:#7c3aed;color:#fff}
    .vc-icon-btn.instructor:hover{background:#6b21a8}
    .vc-icon-btn.danger{background:#b91c1c;border-color:#b91c1c;color:#fff}
    .vc-icon-btn.danger:hover{background:#991b1b}

    /* ── Body: stage + side panel ── */
    .vc-room-body{display:grid;grid-template-columns:minmax(0,1fr) 320px;flex:1;min-height:0}
    .vc-room-body.panel-hidden{grid-template-columns:minmax(0,1fr)}
    .vc-stage{padding:16px;min-width:0;display:flex;flex-direction:column;gap:12px}
    .vc-video-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:12px;align-content:start;flex:1;min-height:320px}
    .vc-video-grid.single{grid-template-columns:1fr}

    /* ── Tiles ── */
    .vc-tile{position:relative;min-height:200px;background:#111827;border:1px solid #293449;border-radius:14px;overflow:hidden;display:flex;align-items:center;justify-content:center;transition:border-color .18s,box-shadow .18s}
    .vc-tile.speaking{border-color:#34d399;box-shadow:0 0 0 2px rgba(52,211,153,.35)}
    .vc-tile.screen{border-color:#3b82f6}
    .vc-tile video{width:100%;height:100%;min-height:200px;max-height:52vh;object-fit:contain;background:#020617}
    .vc-tile.screen video{max-height:60vh}
    .vc-tile-name{position:absolute;bottom:8px;left:8px;display:inline-flex;align-items:center;gap:6px;padding:5px 9px;background:#030712cc;border-radius:7px;font-size:.76rem;font-weight:650;z-index:2;max-width:calc(100% - 16px)}
    .vc-tile-name i{font-size:.7rem;color:#fca5a5}
    .vc-tile-badges{position:absolute;top:8px;right:8px;display:flex;gap:6px;z-index:2}
    .vc-tile-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:999px;font-size:.66rem;font-weight:800;background:#030712cc;color:#fcd34d}
    .vc-tile-badge.host{color:#93c5fd}
    .vc-tile-badge.you{color:#6ee7b7}
    .vc-avatar{display:grid;place-items:center;width:84px;height:84px;border-radius:50%;color:#fff;font-size:1.7rem;font-weight:800;letter-spacing:.02em}
    .vc-placeholder{color:#94a3b8;text-align:center;padding:20px}
    .vc-placeholder i{font-size:2.2rem;display:block;margin-bottom:12px;color:#64748b}

    /* ── Emoji reactions overlay ── */
    .vc-float{position:absolute;bottom:56px;right:14px;display:flex;flex-direction:column;align-items:center;gap:6px;z-index:5;pointer-events:none}
    .vc-float span{font-size:1.6rem;animation:vcFloatUp 2.4s ease forwards}
    @keyframes vcFloatUp{0%{opacity:0;transform:translateY(12px) scale(.6)}15%{opacity:1;transform:translateY(0) scale(1.1)}100%{opacity:0;transform:translateY(-70px) scale(1)}}

    /* ── Controls bar ── */
    .vc-controls{display:flex;justify-content:center;align-items:center;gap:9px;flex-wrap:wrap;padding:6px 0 2px;position:relative}
    .vc-control{border:1px solid #374151;background:#1f2937;color:#f9fafb;border-radius:999px;width:52px;height:52px;display:grid;place-items:center;font-size:1.02rem;cursor:pointer;position:relative;transition:background .16s,transform .16s}
    .vc-control:hover{background:#374151}
    .vc-control:disabled{opacity:.45;cursor:not-allowed}
    .vc-control.wide{width:auto;padding:0 18px;border-radius:999px;font-weight:700;font-size:.82rem;gap:8px;display:inline-flex;align-items:center}
    .vc-control.danger{background:#b91c1c;border-color:#b91c1c}
    .vc-control.danger:hover{background:#991b1b}
    .vc-control.muted{background:#7f1d1d;border-color:#991b1b}
    .vc-control.active{background:#2563eb;border-color:#2563eb;color:#fff}
    .vc-control .vc-dot{position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:#ef4444;display:none}
    .vc-control.has-dot .vc-dot{display:block}
    .vc-controls-sep{width:1px;height:30px;background:#293449;margin:0 4px}

    /* ── Popovers ── */
    .vc-pop{position:absolute;bottom:64px;left:50%;transform:translateX(-50%);background:#111827;border:1px solid #293449;border-radius:14px;padding:12px;display:flex;gap:8px;box-shadow:0 14px 34px rgba(2,6,23,.5);z-index:20}
    .vc-pop button{background:#0b1220;border:1px solid #293449;border-radius:9px;font-size:1.15rem;width:40px;height:40px;cursor:pointer}
    .vc-pop button:hover{background:#1f2937}
    .vc-pop.vc-settings{flex-direction:column;gap:12px;width:280px;padding:16px}
    .vc-pop.vc-settings label{display:block;font-size:.7rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:#9ca3af;margin-bottom:6px}
    .vc-pop.vc-settings select{width:100%;background:#0b1220;color:#f9fafb;border:1px solid #374151;border-radius:8px;padding:10px;font-size:.85rem}
    .vc-pop.vc-settings select:focus{outline:none;border-color:#3b82f6}
    .vc-pop-item{padding:10px 14px;cursor:pointer;border-radius:10px;background:transparent;text-align:left;font-size:.85rem;color:#f9fafb;display:flex;align-items:center;gap:10px}
    .vc-pop-item:hover{background:#0b1220;border-color:#293449}

    /* ── Side panel ── */
    .vc-side{background:#111827;border-left:1px solid #253047;display:flex;flex-direction:column;min-width:0}
    .vc-side-tabs{display:flex;align-items:center;gap:6px;padding:12px 14px;border-bottom:1px solid #253047}
    .vc-tab{flex:1;border:1px solid transparent;background:transparent;color:#9ca3af;font-weight:800;font-size:.78rem;padding:9px 10px;border-radius:10px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px}
    .vc-tab:hover{color:#e5e7eb}
    .vc-tab.active{background:#0b1220;border-color:#293449;color:#f9fafb}
    .vc-tab-count{background:#1f2937;border-radius:999px;padding:1px 8px;font-size:.68rem;color:#cbd5e1}
    .vc-unread{background:#ef4444;color:#fff;border-radius:999px;padding:1px 7px;font-size:.66rem;display:none}
    .vc-unread.show{display:inline-block}
    .vc-chat-log{padding:12px;flex:1;min-height:180px;max-height:46vh;overflow:auto;display:flex;flex-direction:column;gap:10px}
    .vc-chat-message{margin:0;overflow-wrap:anywhere;font-size:.86rem}
    .vc-chat-message strong{display:block;color:#93c5fd;margin-bottom:3px;font-size:.76rem}
    .vc-chat-message.mine strong{color:#6ee7b7}
    .vc-chat-message.system strong{color:#fcd34d}
    .vc-chat-message span{color:#e5e7eb;white-space:pre-wrap}
    .vc-chat-form{display:flex;gap:8px;padding:12px;border-top:1px solid #253047}
    .vc-chat-form input{min-width:0;flex:1;background:#0b1220;color:#fff;border:1px solid #374151;border-radius:9px;padding:10px}
    .vc-chat-form input:focus{outline:none;border-color:#3b82f6}
    .vc-chat-form button{background:#2563eb;color:#fff;border:0;border-radius:9px;padding:0 14px;font-weight:700;cursor:pointer}
    .vc-people-list{padding:8px;flex:1;overflow:auto;display:flex;flex-direction:column;gap:4px}
    .vc-person{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:11px}
    .vc-person:hover{background:#0b1220}
    .vc-person-avatar{flex:none;display:grid;place-items:center;width:34px;height:34px;border-radius:50%;color:#fff;font-size:.78rem;font-weight:800}
    .vc-person-body{min-width:0;flex:1}
    .vc-person-name{font-size:.84rem;font-weight:650;color:#f9fafb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .vc-person-role{font-size:.68rem;color:#9ca3af}
    .vc-person-icons{display:flex;align-items:center;gap:8px;color:#fca5a5;font-size:.8rem}
    .vc-person-icons .hand{color:#fcd34d}

    /* ── Lobby ── */
    .vc-lobby{flex:1;display:grid;place-items:center;padding:26px 18px}
    .vc-lobby-card{width:min(560px,100%);background:#111827;border:1px solid #293449;border-radius:18px;padding:22px;text-align:center}
    .vc-lobby-preview{position:relative;aspect-ratio:16/10;background:#020617;border:1px solid #293449;border-radius:14px;overflow:hidden;margin-bottom:14px}
    .vc-lobby-preview video{width:100%;height:100%;object-fit:cover;transform:scaleX(-1)}
    .vc-lobby-preview .vc-avatar{position:absolute;inset:0;margin:auto}
    .vc-lobby-actions{display:flex;justify-content:center;gap:10px;margin:14px 0}
    .vc-lobby h3{color:#f9fafb;margin:0 0 6px}
    .vc-lobby p{color:#9ca3af;font-size:.84rem;margin:0 0 4px}

    /* ── Status + misc ── */
    .vc-status{padding:10px 16px;font-size:.84rem;color:#cbd5e1;background:#0f172a;border-top:1px solid #253047;min-height:38px;display:flex;align-items:center;gap:9px}
    .vc-status.error{color:#fecaca}
    .vc-config-banner{display:flex;gap:12px;align-items:flex-start;padding:14px 20px;background:#3f151b;border-bottom:1px solid #7f1d1d;color:#fecaca}
    .vc-config-banner i{font-size:1.05rem;color:#fca5a5;margin-top:2px}
    .vc-config-banner strong{display:block;font-size:.9rem;color:#fee2e2;margin-bottom:4px}
    .vc-config-banner p{margin:0;font-size:.82rem;line-height:1.55;overflow-wrap:anywhere}
    .vc-status i{color:#93c5fd}
    .vc-toast{position:fixed;left:50%;bottom:26px;transform:translateX(-50%);background:#111827;border:1px solid #293449;color:#f9fafb;border-radius:12px;padding:10px 16px;font-size:.84rem;z-index:999;box-shadow:0 14px 34px rgba(2,6,23,.5)}
    .vc-audio-root{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
    .vc-stage:fullscreen{background:#0b1220;padding:10px}
    .vc-fullscreen-enter{background:#2563eb;color:#fff}
    .vc-fullscreen-exit{background:#6b7280;color:#fff}

    @media(max-width:900px){
        .vc-room-body{grid-template-columns:1fr}
        .vc-room-body.panel-hidden{grid-template-columns:1fr}
        .vc-side{border-left:0;border-top:1px solid #253047}
        .vc-chat-log{max-height:240px}
        .vc-room{min-height:0}
        .vc-video-grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr));min-height:200px}
        .vc-tile,.vc-tile video{min-height:150px}
        .vc-control{width:46px;height:46px;font-size:.95rem}
        .vc-control.wide{padding:0 14px}
    }
</style>
@endpush
@section('content')
<div class="vc-room" id="virtualClassroom">
    {{-- ═══ HEADER ═══ --}}
    <header class="vc-room-head">
        <div>
            <h3>{{ $virtualClass->title }}</h3>
            <div class="vc-room-meta">{{ $class->code }} · {{ $virtualClass->meeting_date->format('M j, Y') }} · {{ \Carbon\Carbon::parse($virtualClass->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($virtualClass->end_time)->format('g:i A') }}</div>
        </div>
        <div class="vc-head-right">
            <span class="vc-head-chip"><i class="fa-solid fa-users"></i> <span id="participantCount">0</span> connected</span>
            <span class="vc-head-chip" id="qualityChip" title="Connection quality">
                <span class="vc-quality" id="qualityBars"><b></b><b></b><b></b></span>
                <span id="qualityLabel">—</span>
            </span>
            <span class="vc-head-timer" id="vcTimer" hidden>00:00</span>
            <button type="button" class="vc-icon-btn" id="inviteButton" title="Copy classroom link"><i class="fa-solid fa-link"></i> Invite</button>
            @if($isInstructor)
                <button type="button" class="vc-icon-btn instructor" id="infoButton" title="Meeting info"><i class="fa-solid fa-circle-info"></i> Info</button>
                <button type="button" class="vc-icon-btn danger" id="endMeetingButton" title="End meeting for everyone"><i class="fa-solid fa-stop"></i> End Meeting</button>
            @endif
        </div>
    </header>

    {{-- ═══ CONFIGURATION WARNING ═══ --}}
    {{-- Rendered by the server, so it is visible even when the LiveKit client
         script never loads, and always before the Join button is pressed. The
         same partial warns on the class-details pages that lead here. --}}
    @if($livekitProblem)
        @include('partials.livekit-config-warning', [
            'problem' => $livekitProblem,
            'warningClass' => 'vc-config-banner',
            'warningId' => 'livekitConfigBanner',
        ])
    @endif

    <div class="vc-room-body" id="vcRoomBody">
        <main class="vc-stage" id="vcStage">
            {{-- ═══ LOBBY ═══ --}}
            <div class="vc-lobby" id="lobby">
                <div class="vc-lobby-card">
                    <div class="vc-lobby-preview" id="lobbyPreview">
                        <video id="lobbyVideo" autoplay playsinline muted hidden></video>
                        <div class="vc-avatar" id="lobbyAvatar">?</div>
                    </div>
                    <h3>Ready to join?</h3>
                    <p>Camera and microphone are <strong>optional</strong>. You can join with both off, listen, watch, and use the chat — turn them on anytime.</p>
                    <div class="vc-lobby-actions">
                        <button type="button" class="vc-control" id="lobbyMicButton" title="Microphone" aria-label="Toggle microphone"><i class="fa-solid fa-microphone-slash"></i></button>
                        <button type="button" class="vc-control" id="lobbyCameraButton" title="Camera" aria-label="Toggle camera"><i class="fa-solid fa-video-slash"></i></button>
                    </div>
                    <button type="button" class="vc-control wide primary" id="joinButton" @disabled($livekitProblem) title="{{ $livekitProblem ? 'Joining is disabled until the video service is configured.' : 'Join classroom' }}"><i class="fa-solid fa-right-to-bracket"></i> Join classroom</button>
                    <p class="vc-room-meta" id="lobbyHint" role="status"></p>
                </div>
            </div>

            {{-- ═══ PARTICIPANT STAGE ═══ --}}
            <div class="vc-video-grid" id="videoGrid" hidden></div>

            {{-- ═══ CONTROL BAR ═══ --}}
            <div class="vc-controls" id="controls" hidden>
                <button type="button" class="vc-control" id="micButton" title="Microphone (M)" aria-label="Toggle microphone" disabled><i class="fa-solid fa-microphone-slash"></i></button>
                <button type="button" class="vc-control" id="cameraButton" title="Camera (V)" aria-label="Toggle camera" disabled><i class="fa-solid fa-video-slash"></i></button>
                <button type="button" class="vc-control" id="screenButton" title="Share screen" aria-label="Share screen" disabled><i class="fa-solid fa-display"></i></button>
                <button type="button" class="vc-control" id="handButton" title="Raise hand (H)" aria-label="Raise hand" disabled><i class="fa-solid fa-hand"></i></button>
                <button type="button" class="vc-control" id="reactButton" title="Reaction" aria-label="Send reaction" disabled><i class="fa-regular fa-face-smile"></i></button>
                <span class="vc-controls-sep"></span>
                <button type="button" class="vc-control" id="chatButton" title="Chat (C)" aria-label="Toggle chat"><i class="fa-regular fa-comments"></i><span class="vc-dot"></span></button>
                <button type="button" class="vc-control" id="peopleButton" title="Participants (P)" aria-label="Toggle participants"><i class="fa-solid fa-users"></i></button>
                <button type="button" class="vc-control" id="spotlightButton" title="Spotlight" aria-label="Spotlight participant" disabled><i class="fa-solid fa-expand"></i></button>
                <button type="button" class="vc-control" id="fullscreenButton" title="Fullscreen (F)" aria-label="Toggle fullscreen"><i class="fa-solid fa-expand"></i></button>
                <button type="button" class="vc-control" id="settingsButton" title="Settings" aria-label="Settings"><i class="fa-solid fa-gear"></i></button>
                <span class="vc-controls-sep"></span>
                <button type="button" class="vc-control danger" id="leaveButton" title="Leave classroom" aria-label="Leave"><i class="fa-solid fa-phone-slash"></i> Leave</button>

                {{-- Reaction picker --}}
                <div class="vc-pop" id="reactPop" hidden>
                    <button type="button" data-emoji="👍" title="Thumbs up">👍</button>
                    <button type="button" data-emoji="🎉" title="Celebrate">🎉</button>
                    <button type="button" data-emoji="❤️" title="Heart">❤️</button>
                    <button type="button" data-emoji="😂" title="Haha">😂</button>
                    <button type="button" data-emoji="👏" title="Clap">👏</button>
                    <button type="button" data-emoji="🤔" title="Thinking">🤔</button>
                </div>

                {{-- Settings popover ──}}
                <div class="vc-pop vc-settings" id="settingsPop" hidden>
                    <label>Camera</label>
                    <select id="deviceCamera"></select>
                    <label>Microphone</label>
                    <select id="deviceMic"></select>
                    <label>Speaker</label>
                    <select id="deviceSpeaker"></select>
                </div>

                {{-- Instructor admin popover ──}}
                <div class="vc-pop" id="instructorPop" hidden>
                    @if($isInstructor)
                        <button type="button" class="vc-pop-item instructor-pop" id="muteAllButton" title="Mute all participants"><i class="fa-solid fa-microphone-slash"></i> Mute All</button>
                        <button type="button" class="vc-pop-item instructor-pop danger" id="endMeetingPopButton" title="End meeting for everyone"><i class="fa-solid fa-stop"></i> End Meeting for All</button>
                    @endif
                </div>
            </div>

            <div class="vc-audio-root" id="audioRoot"></div>
        </main>

        {{-- ═══ SIDE PANEL: CHAT + PARTICIPANTS ═══ --}}
        <aside class="vc-side" id="vcSide">
            <div class="vc-side-tabs">
                <button type="button" class="vc-tab active" id="chatTabButton" aria-controls="chatPanel"><i class="fa-regular fa-comments"></i> Chat <span class="vc-unread" id="chatUnread">0</span></button>
                <button type="button" class="vc-tab" id="peopleTabButton" aria-controls="peoplePanel"><i class="fa-solid fa-users"></i> People <span class="vc-tab-count" id="peopleCount">0</span></button>
            </div>

            <div id="chatPanel" style="display:flex;flex-direction:column;flex:1;min-height:0">
                <div class="vc-chat-log" id="chatLog" aria-live="polite">
                    <p class="vc-placeholder" id="chatPlaceholder">Join the classroom to send and receive messages — no camera or microphone needed.</p>
                </div>
                <form class="vc-chat-form" id="chatForm">
                    <input type="text" id="chatInput" maxlength="1000" placeholder="Write a message…" aria-label="Chat message" disabled>
                    <button type="submit" id="sendChatButton" disabled>Send</button>
                </form>
            </div>

            <div id="peoplePanel" hidden>
                <div class="vc-people-list" id="peopleList"></div>
            </div>
        </aside>
    </div>

    {{-- ═══ STATUS BAR ═══ --}}
    <div class="vc-status" id="connectionStatus" role="status"><i class="fa-solid fa-circle-info"></i> {{ $livekitProblem ? 'Not connected. The video service has to be configured before this room can be joined.' : 'Not connected. Click "Join classroom" to enter.' }}</div>
</div>

<div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px">
    <a class="btn-modal-cancel" href="{{ $backUrl }}" style="text-decoration:none;padding:10px 14px;border-radius:8px"><i class="fa-solid fa-arrow-left"></i> Back to class details</a>
    <span style="font-size:.8rem;color:#64748b;align-self:center">Follow your institution's online-class conduct and privacy policies.</span>
</div>

<script type="module">
// Only exports that exist in livekit-client 2.x: importing a name the module
// does not provide aborts the whole script, and the room never connects.
import { Room, RoomEvent, Track } from 'https://cdn.jsdelivr.net/npm/livekit-client@2.15.6/+esm';

/* ── Configuration ── */
const tokenUrl = @json($tokenUrl);
const leaveUrl = @json($leaveUrl);
const backUrl = @json($backUrl);
const endUrl = @json($endUrl);
const muteAllUrl = @json($muteAllUrl);
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
const currentName = @json($displayName);
const isInstructor = @json($isInstructor);
const CHAT_TOPIC = 'class-chat';
const HAND_TOPIC = 'class-hand';
const REACTION_TOPIC = 'class-reaction';
const MUTE_ALL_TOPIC = 'class-mute-all';
const END_MEETING_TOPIC = 'class-end-meeting';
// v2 replaced the DataPacket_Kind enum with a boolean `reliable` option.
const dataOptions = { reliable: true };
const AVATAR_COLOURS = ['#2563eb','#7c3aed','#0891b2','#059669','#b45309','#be123c','#4f46e5','#0e7490'];

/* ── DOM refs ── */
const $ = (id) => document.getElementById(id);
const el = {
    lobby:$('lobby'), lobbyVideo:$('lobbyVideo'), lobbyAvatar:$('lobbyAvatar'), lobbyHint:$('lobbyHint'),
    lobbyMic:$('lobbyMicButton'), lobbyCam:$('lobbyCameraButton'), join:$('joinButton'),
    videoGrid:$('videoGrid'), controls:$('controls'), audioRoot:$('audioRoot'),
    mic:$('micButton'), camera:$('cameraButton'), screen:$('screenButton'),
    hand:$('handButton'), react:$('reactButton'), reactPop:$('reactPop'),
    chat:$('chatButton'), people:$('peopleButton'), spotlight:$('spotlightButton'), fullscreen:$('fullscreenButton'), settings:$('settingsButton'), leave:$('leaveButton'),
    chatTab:$('chatTabButton'), peopleTab:$('peopleTabButton'), chatPanel:$('chatPanel'), peoplePanel:$('peoplePanel'),
    chatLog:$('chatLog'), chatPlaceholder:$('chatPlaceholder'), chatInput:$('chatInput'), chatSend:$('sendChatButton'), chatForm:$('chatForm'),
    chatUnread:$('chatUnread'), peopleList:$('peopleList'), peopleCount:$('peopleCount'),
    status:$('connectionStatus'), count:$('participantCount'), timer:$('vcTimer'),
    qualityBars:$('qualityBars'), qualityLabel:$('qualityLabel'), qualityChip:$('qualityChip'),
    invite:$('inviteButton'), stage:$('vcStage'), roomBody:$('vcRoomBody'), side:$('vcSide'),
    settingsPop:$('settingsPop'), deviceCamera:$('deviceCamera'), deviceMic:$('deviceMic'), deviceSpeaker:$('deviceSpeaker'),
    instructorPop:$('instructorPop'), muteAll:$('muteAllButton'), endMeetingPop:$('endMeetingPopButton'),
    info:$('infoButton'), endMeeting:$('endMeetingButton'),
};

/* ── State ── */
let room=null, connecting=false, leaving=false, failQuietly=false;
let micEnabled=false, cameraEnabled=false, screenEnabled=false, handRaised=false;
let connectedAt=null, timerInterval=null, unread=0, activePanel='chat', spotlightKey=null;
let lobbyStream=null, lobbyMicOn=false, lobbyCamOn=false;
const tiles=new Map(), seenMessages=new Set(), raisedHands=new Set();
const AVATAR_COLOUR=(identity)=>AVATAR_COLOURS[Math.abs([...String(identity)].reduce((h,c)=>(h*31+c.charCodeAt(0))|0,7))%AVATAR_COLOURS.length];
const initials=(name)=>String(name||'?').trim().split(/\s+/).slice(0,2).map(w=>w[0]||'').join('').toUpperCase()||'?';
const uid=()=>crypto?.randomUUID?crypto.randomUUID():Date.now()+'-'+Math.random().toString(16).slice(2);

function setStatus(message,error=false){
    el.status.innerHTML='';
    const icon=document.createElement('i');
    icon.className=error?'fa-solid fa-triangle-exclamation':'fa-solid fa-circle-info';
    el.status.append(icon,document.createTextNode(' '+message));
    el.status.classList.toggle('error',error);
}
function toast(message){
    const t=document.createElement('div'); t.className='vc-toast'; t.textContent=message;
    document.body.appendChild(t); setTimeout(()=>t.remove(),2600);
}

/* ── Quality + timer ── */
function updateQuality(q){
    const cls=q>=2?'good':q===1?'fair':'poor';
    el.qualityBars.className='vc-quality '+cls;
    el.qualityLabel.textContent=q>=2?'Good':q===1?'Fair':'Poor';
    el.qualityChip.title='Connection quality: '+el.qualityLabel.textContent;
}
function startTimer(){
    connectedAt=Date.now(); el.timer.hidden=false; clearInterval(timerInterval);
    timerInterval=setInterval(()=>{
        const s=Math.floor((Date.now()-connectedAt)/1000);
        el.timer.textContent=String(Math.floor(s/60)).padStart(2,'0')+':'+String(s%60).padStart(2,'0');
    },1000);
}
function stopTimer(){
    clearInterval(timerInterval); timerInterval=null; el.timer.hidden=true; el.timer.textContent='00:00';
    el.qualityBars.className='vc-quality'; el.qualityLabel.textContent='—';
}

/* ── Tiles ── */
function tileKeyFor(track,p){ return track.source===Track.Source.ScreenShare ? p.identity+':screen' : p.identity; }
function tileNameFor(track,p){ return track.source===Track.Source.ScreenShare ? (p.name||p.identity)+' — Screen' : p.name||p.identity; }
function getTile(key,name){
    let tile=tiles.get(key);
    if(!tile){
        tile=document.createElement('div'); tile.className='vc-tile'+(key.endsWith(':screen')?' screen':''); tile.dataset.key=key;
        const avatar=document.createElement('div'); avatar.className='vc-tile-avatar';
        const disc=document.createElement('div'); disc.className='vc-avatar'; disc.style.background=AVATAR_COLOUR(key); disc.textContent=initials(name); avatar.appendChild(disc);
        const video=document.createElement('div'); video.className='vc-tile-video'; video.hidden=true;
        const badges=document.createElement('div'); badges.className='vc-tile-badges';
        const label=document.createElement('div'); label.className='vc-tile-name';
        const micOff=document.createElement('i'); micOff.className='fa-solid fa-microphone-slash'; micOff.hidden=true;
        label.append(micOff,document.createTextNode(' '+(name||key)));
        tile.append(avatar,video,badges,label);
        tile.addEventListener('click',()=>toggleSpotlight(key));
        el.videoGrid.appendChild(tile); tiles.set(key,tile);
    }
    return tile;
}
function attachTrack(track,p){
    if(!track||!p) return;
    const key=tileKeyFor(track,p); const tile=getTile(key,tileNameFor(track,p));
    const element=track.attach(); element.autoplay=true; element.playsInline=true;
    element.style.width='100%'; element.style.height='100%';
    element.style.objectFit=track.source===Track.Source.ScreenShare?'contain':'cover'; element.style.background='#020617';
    if(track.kind===Track.Kind.Video){ const h=tile.querySelector('.vc-tile-video'); h.appendChild(element); h.hidden=false; tile.querySelector('.vc-tile-avatar').hidden=true; }
    else el.audioRoot.appendChild(element);
}
function detachTrack(track,p){
    if(!track||!p) return; track.detach().forEach(n=>n.remove());
    const key=tileKeyFor(track,p); const tile=tiles.get(key);
    if(tile&&track.kind===Track.Kind.Video){ const h=tile.querySelector('.vc-tile-video'); h.innerHTML=''; h.hidden=true; tile.querySelector('.vc-tile-avatar').hidden=false; }
}
function removeTile(key){ const t=tiles.get(key); if(t){ t.remove(); tiles.delete(key); } if(spotlightKey===key) spotlightKey=null; }
function toggleSpotlight(key){ spotlightKey=spotlightKey===key?null:key; tiles.forEach((t,k)=>t.classList.toggle('single',k===spotlightKey)); el.videoGrid.classList.toggle('single',spotlightKey!==null); }

/* ── Participants ── */
function renderParticipants(){
    if(!room){ el.peopleList.innerHTML=''; el.peopleCount.textContent='0'; return; }
    const rows=[]; const push=(p,isLocal)=>{
        const identity=p.identity, name=p.name||identity, hand=raisedHands.has(identity), micOff=p.isMicrophoneEnabled===false;
        rows.push(`<div class="vc-person" data-identity="${identity.replace(/"/g,'&quot;')}">
            <span class="vc-person-avatar" style="background:${AVATAR_COLOUR(identity)}">${initials(name)}</span>
            <span class="vc-person-body">
                <span class="vc-person-name">${name.replace(/</g,'&lt;')}${isLocal?' (You)':''}</span>
                <span class="vc-person-role">${isLocal?'You':(p.isSpeaking?'Speaking':'Participant')}${hand?' · hand raised':''}</span>
            </span>
            <span class="vc-person-icons">${hand?'<i class="fa-solid fa-hand hand" title="Hand raised"></i>':''}${micOff?'<i class="fa-solid fa-microphone-slash" title="Microphone off"></i>':''}</span>
        </div>`);
    };
    push(room.localParticipant,true); room.remoteParticipants.forEach(p=>push(p,false));
    el.peopleList.innerHTML=rows.join(''); el.peopleCount.textContent=String(room.numParticipants); el.count.textContent=String(room.numParticipants);
    renderTileBadges();
}
function renderTileBadges(){
    if(!room) return;
    const all=[room.localParticipant,...room.remoteParticipants.values()];
    all.forEach(p=>{
        const tile=tiles.get(p.identity); if(!tile) return;
        const micOff=p.isMicrophoneEnabled===false; const micIcon=tile.querySelector('.vc-tile-name i');
        if(micIcon) micIcon.hidden=!micOff;
        const badges=tile.querySelector('.vc-tile-badges'); badges.innerHTML='';
        if(p.isLocal){ const you=document.createElement('span'); you.className='vc-tile-badge you'; you.textContent='You'; badges.appendChild(you); }
        if(raisedHands.has(p.identity)){ const h=document.createElement('span'); h.className='vc-tile-badge'; h.innerHTML='<i class="fa-solid fa-hand"></i>'; badges.appendChild(h); }
        tile.classList.toggle('speaking',p.isSpeaking===true);
    });
}

/* ── Chat ── */
function addChatMessage(name,text,ts,{mine=false,system=false,id=null}={}){
    const messageId=id||uid(); if(seenMessages.has(messageId)) return; seenMessages.add(messageId);
    if(el.chatPlaceholder) el.chatPlaceholder.remove();
    const row=document.createElement('p'); row.className='vc-chat-message'+(mine?' mine':'')+(system?' system':'');
    const strong=document.createElement('strong'); strong.textContent=(system?'System · ':'')+(name||'Participant')+(ts?' · '+new Date(ts).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}):'');
    const span=document.createElement('span'); span.textContent=text; row.append(strong,span);
    el.chatLog.appendChild(row); el.chatLog.scrollTop=el.chatLog.scrollHeight;
    if(!mine&&activePanel!=='chat'){ unread+=1; el.chatUnread.textContent=String(unread); el.chatUnread.classList.add('show'); el.chat.classList.add('has-dot'); }
}
function publish(topic,payload){
    if(!room) return Promise.resolve(false);
    const bytes=new TextEncoder().encode(JSON.stringify(payload));
    return room.localParticipant.publishData(bytes,{...dataOptions,topic}).then(()=>true).catch(()=>false);
}

/* ── Lobby preview ── */
async function startLobbyPreview(){
    if(lobbyStream) return true; if(!navigator.mediaDevices?.getUserMedia){ el.lobbyHint.textContent='This browser does not expose camera/microphone controls.'; return false; }
    try{
        lobbyStream=await navigator.mediaDevices.getUserMedia({video:lobbyCamOn,audio:lobbyMicOn});
        lobbyStream.getVideoTracks().forEach(t=>t.enabled=lobbyCamOn); lobbyStream.getAudioTracks().forEach(t=>t.enabled=lobbyMicOn);
        el.lobbyVideo.srcObject=lobbyStream; el.lobbyVideo.hidden=!lobbyCamOn; el.lobbyAvatar.hidden=lobbyCamOn;
        el.lobbyHint.textContent=lobbyCamOn||lobbyMicOn?'Preview on. Leave them off to join silently.':'All devices off — join muted.';
        return true;
    }catch(e){ lobbyStream=null; el.lobbyVideo.hidden=true; el.lobbyAvatar.hidden=false; el.lobbyHint.textContent='Camera/microphone unavailable or denied — join anyway with chat.'; return false; }
}
function stopLobbyPreview(){ if(!lobbyStream) return; lobbyStream.getTracks().forEach(t=>t.stop()); lobbyStream=null; el.lobbyVideo.srcObject=null; el.lobbyVideo.hidden=true; el.lobbyAvatar.hidden=false; }
function setLobbyControlState(){ el.lobbyMic.innerHTML=lobbyMicOn?'<i class="fa-solid fa-microphone"></i>':'<i class="fa-solid fa-microphone-slash"></i>'; el.lobbyMic.classList.toggle('muted',!lobbyMicOn); el.lobbyCam.innerHTML=lobbyCamOn?'<i class="fa-solid fa-video"></i>':'<i class="fa-solid fa-video-slash"></i>'; el.lobbyCam.classList.toggle('muted',!lobbyCamOn); }
el.lobbyMic.addEventListener('click',async()=>{ lobbyMicOn=!lobbyMicOn; setLobbyControlState(); if(lobbyMicOn) await startLobbyPreview(); lobbyStream?.getAudioTracks().forEach(t=>t.enabled=lobbyMicOn); });
el.lobbyCam.addEventListener('click',async()=>{ lobbyCamOn=!lobbyCamOn; setLobbyControlState(); if(lobbyCamOn) await startLobbyPreview(); lobbyStream?.getVideoTracks().forEach(t=>t.enabled=lobbyCamOn); });
setLobbyControlState();

/* ── Join / reconnect ── */
async function join(){
    if(connecting||leaving) return; connecting=true; el.join.disabled=true;
    if(room){ failQuietly=true; try{ await room.disconnect(); }catch(_){} room=null; }
    stopLobbyPreview(); setStatus('Requesting access token…');
    let data;
    try{
        const r=await fetch(tokenUrl,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},body:'{}',credentials:'same-origin'});
        data=await r.json().catch(()=>({})); if(!r.ok) throw new Error(data.message||('Token failed '+r.status)); if(!data.url||!data.token) throw new Error('No valid token returned.');
    }catch(e){ connecting=false; el.join.disabled=false; setStatus(e.message,true); return; }
    setStatus('Connecting to classroom…');
    room=new Room({adaptiveStream:true,dynacast:true});
    room.on(RoomEvent.Connected,()=>{
        connecting=false; failQuietly=false; startTimer();
        setStatus('Connected as '+(data.name||currentName)+'.');
        el.lobby.hidden=true; el.videoGrid.hidden=false; el.controls.hidden=false;
        [el.mic,el.camera,el.screen,el.hand,el.react].forEach(b=>b.disabled=false);
        el.chatInput.disabled=false; el.chatSend.disabled=false; syncDeviceButtons(); renderParticipants();
        room.remoteParticipants.forEach(p=>p.trackPublications.forEach(pub=>{ if(pub.track&&pub.track.isSubscribed) attachTrack(pub.track,p); }));
        if(lobbyMicOn) void enableMicrophone(); if(lobbyCamOn) void enableCamera();
    });
    room.on(RoomEvent.Reconnecting,()=>setStatus('Reconnecting…'));
    room.on(RoomEvent.Reconnected,()=>setStatus('Reconnected.'));
    room.on(RoomEvent.ConnectionQualityChanged,(q,p)=>{ if(!p||p.isLocal) updateQuality(Number(q)); });
    room.on(RoomEvent.ParticipantConnected,(p)=>{ renderParticipants(); addChatMessage('System',p.name||p.identity+' joined.',Date.now(),{system:true}); });
    room.on(RoomEvent.ParticipantDisconnected,(p)=>{ removeTile(p.identity); removeTile(p.identity+':screen'); raisedHands.delete(p.identity); renderParticipants(); addChatMessage('System',p.name||p.identity+' left.',Date.now(),{system:true}); });
    room.on(RoomEvent.TrackSubscribed,(t,pub,p)=>attachTrack(t,p));
    room.on(RoomEvent.TrackUnsubscribed,(t,pub,p)=>detachTrack(t,p));
    room.on(RoomEvent.LocalTrackPublished,(pub)=>{ if(pub.track) attachTrack(pub.track,room.localParticipant); });
    room.on(RoomEvent.LocalTrackUnpublished,(pub)=>{ if(pub?.track) detachTrack(pub.track,room.localParticipant); syncDeviceButtons(); });
    room.on(RoomEvent.ActiveSpeakersChanged,()=>renderTileBadges());
    room.on(RoomEvent.DataReceived,(payload,p)=>{
        let m; try{ m=JSON.parse(new TextDecoder().decode(payload)); }catch(_){ return; } if(!m||typeof m!=='object') return;
        const from=p?.name||p?.identity||'Participant';
        if(m.kind==='chat') addChatMessage(m.name||from,String(m.text||''),m.ts,{id:m.id});
        else if(m.kind==='hand'){ if(m.raised) raisedHands.add(p?.identity||from); else raisedHands.delete(p?.identity||from); renderParticipants(); }
        else if(m.kind==='reaction') floatReaction(String(m.emoji||'👍'),p?.identity);
        else if(m.kind==='mute-all'){ disableMicrophone(); setStatus('Instructor muted everyone.'); }
        else if(m.kind==='end-meeting'){ setStatus('Meeting ended by instructor.'); setTimeout(()=>window.location.href=backUrl,1500); }
    });
    room.on(RoomEvent.Disconnected,()=>{
        connecting=false; if(failQuietly){ failQuietly=false; return; }
        stopTimer(); micEnabled=cameraEnabled=screenEnabled=handRaised=false;
        [el.mic,el.camera,el.screen,el.hand,el.react].forEach(b=>b.disabled=true);
        el.chatInput.disabled=true; el.chatSend.disabled=true; el.hand.classList.remove('active'); el.hand.innerHTML='<i class="fa-regular fa-hand"></i>';
        tiles.forEach(t=>t.remove()); tiles.clear(); raisedHands.clear(); renderParticipants();
        if(!leaving){ el.lobby.hidden=false; el.videoGrid.hidden=true; el.controls.hidden=true; el.join.disabled=false; el.join.innerHTML='<i class="fa-solid fa-rotate-right"></i> Reconnect'; setStatus('Disconnected. Click "Reconnect" to rejoin.',true); }
    });
    try{ await room.connect(data.url,data.token,{autoSubscribe:true}); el.join.innerHTML='<i class="fa-solid fa-right-to-bracket"></i> Join classroom'; el.join.disabled=false; }
    catch(e){
        connecting=false; el.join.disabled=false; const raw=e?.message||'Unable to connect.';
        if(/invalid token|token/i.test(raw)) setStatus('Token rejected. Verify LIVEKIT_API_KEY / LIVEKIT_API_SECRET / LIVEKIT_URL in environment variables (wss:// URL), then redeploy or run "php artisan config:clear". Reconnect requests a fresh token.',true);
        else setStatus(raw,true);
        failQuietly=true; try{ await room.disconnect(); }catch(_){} room=null;
    }
}

/* ── Device controls ── */
async function enableMicrophone(){ if(!room) return; try{ await room.localParticipant.setMicrophoneEnabled(true); micEnabled=true; setStatus('Microphone on.'); }catch(e){ micEnabled=false; setStatus('Mic unavailable: '+(e?.message||'no mic found')+'. Chat still works.',true); } syncDeviceButtons(); }
async function disableMicrophone(){ if(!room) return; try{ await room.localParticipant.setMicrophoneEnabled(false); micEnabled=false; setStatus('Microphone off.'); }catch(e){ setStatus('Mic error: '+e.message,true); } syncDeviceButtons(); }
async function enableCamera(){ if(!room) return; try{ await room.localParticipant.setCameraEnabled(true); cameraEnabled=true; setStatus('Camera on.'); }catch(e){ cameraEnabled=false; setStatus('Camera unavailable: '+(e?.message||'no camera found')+'. Audio/chat still work.',true); } syncDeviceButtons(); }
async function disableCamera(){ if(!room) return; try{ await room.localParticipant.setCameraEnabled(false); cameraEnabled=false; setStatus('Camera off.'); }catch(e){ setStatus('Camera error: '+e.message,true); } syncDeviceButtons(); }
function syncDeviceButtons(){ const p=room?.localParticipant; const mo=p?p.isMicrophoneEnabled:micEnabled; const co=p?p.isCameraEnabled:cameraEnabled; micEnabled=mo; cameraEnabled=co; el.mic.innerHTML=mo?'<i class="fa-solid fa-microphone"></i>':'<i class="fa-solid fa-microphone-slash"></i>'; el.mic.classList.toggle('muted',!mo); el.camera.innerHTML=co?'<i class="fa-solid fa-video"></i>':'<i class="fa-solid fa-video-slash"></i>'; el.camera.classList.toggle('muted',!co); el.screen.innerHTML=screenEnabled?'<i class="fa-solid fa-display"></i> Stop':'<i class="fa-solid fa-display"></i> Share'; }

el.join.addEventListener('click',join);
el.mic.addEventListener('click',()=>{ if(!room) return; const on=room.localParticipant.isMicrophoneEnabled; on?disableMicrophone():enableMicrophone(); });
el.camera.addEventListener('click',()=>{ if(!room) return; const on=room.localParticipant.isCameraEnabled; on?disableCamera():enableCamera(); });
el.screen.addEventListener('click',async()=>{ if(!room) return; try{ await room.localParticipant.setScreenShareEnabled(!screenEnabled); screenEnabled=!screenEnabled; setStatus(screenEnabled?'Screen sharing on.':'Screen sharing stopped.'); }catch(e){ screenEnabled=false; setStatus('Screen share cancelled or unsupported.',true); } syncDeviceButtons(); });

/* ── Hand + reactions ── */
el.hand.addEventListener('click',async()=>{ if(!room) return; handRaised=!handRaised; const id=room.localParticipant.identity; if(handRaised) raisedHands.add(id); else raisedHands.delete(id); el.hand.classList.toggle('active',handRaised); el.hand.innerHTML=handRaised?'<i class="fa-solid fa-hand"></i>':'<i class="fa-regular fa-hand"></i>'; renderParticipants(); const s=await publish(HAND_TOPIC,{kind:'hand',raised:handRaised}); if(!s) setStatus('Could not broadcast hand raise.',true); });
el.react.addEventListener('click',()=>{ el.reactPop.hidden=!el.reactPop.hidden; });
el.reactPop.querySelectorAll('button').forEach(b=>{ b.addEventListener('click',async()=>{ el.reactPop.hidden=true; const emoji=b.dataset.emoji; floatReaction(emoji,room?.localParticipant.identity); await publish(REACTION_TOPIC,{kind:'reaction',emoji}); }); });
function floatReaction(emoji,identity){ const host=identity?tiles.get(identity):null; const layer=document.createElement('div'); layer.className='vc-float'; const span=document.createElement('span'); span.textContent=emoji; layer.appendChild(span); (host||el.videoGrid).appendChild(layer); setTimeout(()=>layer.remove(),2500); }

/* ── Side panel ── */
function showPanel(name){ activePanel=name; el.chatPanel.hidden=name!=='chat'; el.peoplePanel.hidden=name!=='people'; el.chatTab.classList.toggle('active',name==='chat'); el.peopleTab.classList.toggle('active',name==='people'); el.chat.classList.toggle('active',name==='chat'&&!el.side.hidden); el.people.classList.toggle('active',name==='people'&&!el.side.hidden); if(name==='chat'){ unread=0; el.chatUnread.classList.remove('show'); el.chat.classList.remove('has-dot'); } }
el.chatTab.addEventListener('click',()=>showPanel('chat'));
el.peopleTab.addEventListener('click',()=>showPanel('people'));
el.chat.addEventListener('click',()=>{ el.side.hidden=!el.side.hidden; el.roomBody.classList.toggle('panel-hidden',el.side.hidden); if(!el.side.hidden) showPanel('chat'); });
el.people.addEventListener('click',()=>{ el.side.hidden=!el.side.hidden; el.roomBody.classList.toggle('panel-hidden',el.side.hidden); if(!el.side.hidden) showPanel('people'); });
el.spotlight.addEventListener('click',()=>{ if(spotlightKey){ toggleSpotlight(spotlightKey); return; } const first=tiles.keys().next().value; if(first) toggleSpotlight(first); });

/* ── Settings (devices) ── */
async function loadDevices(){
    if(!navigator.mediaDevices?.enumerateDevices) return;
    const devs=await navigator.mediaDevices.enumerateDevices();
    const fill=(sel,kind)=>{ sel.innerHTML=''; devs.filter(d=>d.kind===kind).forEach(d=>{ const o=document.createElement('option'); o.value=d.deviceId; o.textContent=d.label||kind==='videoinput'?'Camera':'Microphone'; sel.appendChild(o); }); if(!sel.options.length){ const o=document.createElement('option'); o.value=''; o.textContent='None found'; sel.appendChild(o); sel.disabled=true; } };
    fill(el.deviceCamera,'videoinput'); fill(el.deviceMic,'audioinput'); fill(el.deviceSpeaker,'audiooutput');
}
el.deviceCamera.addEventListener('change',async()=>{ if(!room||!el.deviceCamera.value) return; try{ await room.switchActiveDevice('videoinput',el.deviceCamera.value); }catch(e){ setStatus('Camera switch failed: '+e.message,true); } });
el.deviceMic.addEventListener('change',async()=>{ if(!room||!el.deviceMic.value) return; try{ await room.switchActiveDevice('audioinput',el.deviceMic.value); }catch(e){ setStatus('Mic switch failed: '+e.message,true); } });
el.deviceSpeaker.addEventListener('change',async()=>{ if(!room||!el.deviceSpeaker.value) return; try{ await room.switchActiveDevice('audiooutput',el.deviceSpeaker.value); }catch(e){ setStatus('Speaker switch failed: '+e.message,true); } });
el.settings.addEventListener('click',()=>{ el.settingsPop.hidden=!el.settingsPop.hidden; if(!el.settingsPop.hidden) loadDevices(); el.reactPop.hidden=true; });

/* ── Instructor admin ── */
async function postJson(url){
    try{ const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'}); return r.ok; }catch(_){ return false; }
}
// These four controls exist only for instructors. Wiring them unconditionally
// threw a null handler error on the student page, which stopped the rest of
// the module (chat, leave, panels) from ever running.
el.muteAll?.addEventListener('click',async()=>{ if(!isInstructor) return; const ok=await postJson(muteAllUrl); if(ok){ await publish(MUTE_ALL_TOPIC,{kind:'mute-all'}); toast('All participants muted.'); } else setStatus('Mute all failed.',true); el.instructorPop.hidden=true; });
el.endMeetingPop?.addEventListener('click',async()=>{ if(!isInstructor) return; if(!confirm('End this meeting for everyone?')) return; const ok=await postJson(endUrl); if(ok){ await publish(END_MEETING_TOPIC,{kind:'end-meeting'}); toast('Meeting ended.'); window.location.href=backUrl; } else setStatus('End meeting failed.',true); el.instructorPop.hidden=true; });
el.endMeeting?.addEventListener('click',async()=>{ if(!isInstructor) return; if(!confirm('End this meeting for everyone?')) return; const ok=await postJson(endUrl); if(ok){ toast('Meeting ended.'); window.location.href=backUrl; } else setStatus('End meeting failed.',true); });

/* ── Invite, fullscreen, panels ── */
el.invite.addEventListener('click',async()=>{ try{ await navigator.clipboard.writeText(window.location.href); toast('Link copied.'); }catch(_){ toast('Copy address bar link instead.'); } });
el.fullscreen.addEventListener('click',()=>{
    if(!document.fullscreenElement) el.stage.requestFullscreen().catch(_=>{});
    else document.exitFullscreen().catch(_=>{});
});
document.addEventListener('fullscreenchange',()=>{ el.fullscreen.classList.toggle('vc-fullscreen-enter',!!document.fullscreenElement); el.fullscreen.classList.toggle('vc-fullscreen-exit',!document.fullscreenElement); el.fullscreen.title=document.fullscreenElement?'Exit fullscreen':'Fullscreen'; });
el.info?.addEventListener('click',()=>{ toast('Class: '+el.count.textContent+' participants · '+new Date().toLocaleTimeString()); });
el.chatForm.addEventListener('submit',async(e)=>{
    e.preventDefault(); const text=el.chatInput.value.trim(); if(!room||!text) return;
    const payload={kind:'chat',id:uid(),name:currentName,text,ts:Date.now()}; const s=await publish(CHAT_TOPIC,payload);
    if(s){ addChatMessage('You',text,payload.ts,{mine:true,id:payload.id}); el.chatInput.value=''; } else setStatus('Send failed. Check connection.',true);
});

/* ── Leave ── */
async function leaveRoom(){
    if(leaving) return; leaving=true; el.leave.disabled=true; stopLobbyPreview(); stopTimer();
    if(room){ try{ await room.disconnect(); }catch(_){} room=null; }
    try{ await fetch(leaveUrl,{method:'POST',headers:{'Accept':'text/html','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',redirect:'manual'}); }catch(_){}
    window.location.href=backUrl;
}
el.leave.addEventListener('click',leaveRoom);
window.addEventListener('pagehide',()=>{ if(!leaving&&room){ const b=new URLSearchParams({_token:csrf}); navigator.sendBeacon(leaveUrl,b); } });

/* ── Keyboard shortcuts ── */
document.addEventListener('keydown',(e)=>{ if(e.target.matches('input,textarea,select')) return; if(!room) return; const k=e.key.toLowerCase(); if(k==='m') el.mic.click(); else if(k==='v') el.camera.click(); else if(k==='h') el.hand.click(); else if(k==='c') el.chat.click(); else if(k==='p') el.people.click(); });

showPanel('chat'); syncDeviceButtons();
</script>
@endsection
