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
    </div>
@endsection

@push('styles')
    @vite('resources/css/virtual-classroom.css')
@endpush
@section('content')
<div class="vc-room" id="virtualClassroom"
     data-token-url="{{ $tokenUrl }}"
     data-leave-url="{{ $leaveUrl }}"
     data-back-url="{{ $backUrl }}"
     data-end-url="{{ $endUrl }}"
     data-mute-all-url="{{ $muteAllUrl }}"
     data-display-name="{{ $displayName }}"
     data-instructor="{{ $isInstructor ? '1' : '0' }}">
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
                    <button type="button" class="vc-control wide primary" id="joinButton"><i class="fa-solid fa-right-to-bracket"></i> Join classroom</button>
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
    <div class="vc-status" id="connectionStatus" role="status"><i class="fa-solid fa-circle-info"></i> Not connected. Click "Join classroom" to enter.</div>
</div>

<div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px">
    <a class="btn-modal-cancel" href="{{ $backUrl }}" style="text-decoration:none;padding:10px 14px;border-radius:8px"><i class="fa-solid fa-arrow-left"></i> Back to class details</a>
    <span style="font-size:.8rem;color:#64748b;align-self:center">Follow your institution's online-class conduct and privacy policies.</span>
</div>
@endsection

@push('scripts')
    @vite('resources/js/virtual-classroom.js')
@endpush
