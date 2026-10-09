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
<style>
    [hidden]{display:none!important}
    .vc-room{color:#e5e7eb;background:#0b1220;border-radius:18px;overflow:hidden;min-height:600px;box-shadow:0 16px 44px rgba(2,6,23,.22)}
    .vc-room-head{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:16px 20px;background:#111827;border-bottom:1px solid #253047;flex-wrap:wrap}
    .vc-room-head h3{margin:0;font-size:1.05rem;color:#f9fafb}.vc-room-meta{font-size:.84rem;color:#9ca3af;margin-top:5px}
    .vc-live-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:#3f151b;color:#fecaca;font-size:.72rem;font-weight:800;letter-spacing:.08em}.vc-live-badge span{width:7px;height:7px;border-radius:50%;background:#ef4444;box-shadow:0 0 0 4px #ef444433}
    .vc-room-body{display:grid;grid-template-columns:minmax(0,1fr) 300px;min-height:490px}.vc-stage{padding:16px;min-width:0}.vc-video-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-content:start;min-height:330px}
    .vc-tile{position:relative;min-height:210px;background:#111827;border:1px solid #293449;border-radius:12px;overflow:hidden;display:flex;align-items:center;justify-content:center}.vc-tile video{width:100%;height:100%;min-height:210px;max-height:480px;object-fit:contain;background:#020617}.vc-tile-name{position:absolute;bottom:8px;left:8px;padding:5px 8px;background:#030712bb;border-radius:6px;font-size:.78rem;z-index:2}.vc-placeholder{color:#94a3b8;text-align:center;padding:20px}.vc-placeholder i{font-size:2.2rem;display:block;margin-bottom:12px;color:#64748b}
    .vc-controls{display:flex;justify-content:center;align-items:center;gap:10px;flex-wrap:wrap;padding:16px 0 4px}.vc-control{border:1px solid #374151;background:#1f2937;color:#f9fafb;border-radius:10px;padding:11px 14px;font-weight:700;cursor:pointer}.vc-control:hover{background:#374151}.vc-control:disabled{opacity:.5;cursor:not-allowed}.vc-control.primary{background:#2563eb;border-color:#2563eb}.vc-control.danger{background:#b91c1c;border-color:#b91c1c}.vc-control.muted{background:#7f1d1d;border-color:#991b1b}
    .vc-side{background:#111827;border-left:1px solid #253047;display:flex;flex-direction:column;min-width:0}.vc-side-tabs{padding:14px 16px;border-bottom:1px solid #253047;font-weight:800}.vc-chat-log{padding:12px;flex:1;min-height:200px;max-height:360px;overflow:auto}.vc-chat-message{margin:0 0 12px;overflow-wrap:anywhere;font-size:.88rem}.vc-chat-message strong{display:block;color:#93c5fd;margin-bottom:3px}.vc-chat-message span{color:#e5e7eb;white-space:pre-wrap}.vc-chat-form{display:flex;gap:8px;padding:12px;border-top:1px solid #253047}.vc-chat-form input{min-width:0;flex:1;background:#0b1220;color:white;border:1px solid #374151;border-radius:8px;padding:10px}.vc-chat-form button{background:#2563eb;color:white;border:0;border-radius:8px;padding:0 13px;font-weight:700}.vc-status{padding:10px 16px;font-size:.85rem;color:#cbd5e1;background:#0f172a;border-top:1px solid #253047}.vc-status.error{color:#fecaca}.vc-join-panel{padding:34px 20px;text-align:center}.vc-join-panel p{color:#9ca3af}.vc-audio-root{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
    @media(max-width:850px){.vc-room-body{grid-template-columns:1fr}.vc-side{border-left:0;border-top:1px solid #253047}.vc-chat-log{max-height:240px}.vc-room{min-height:0}.vc-video-grid{grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}.vc-tile,.vc-tile video{min-height:160px}}
</style>
@endpush

@section('content')
<div class="vc-room" id="virtualClassroom">
    <div class="vc-room-head">
        <div>
            <h3>{{ $virtualClass->title }}</h3>
            <div class="vc-room-meta">{{ $class->code }} · {{ $virtualClass->meeting_date->format('M j, Y') }} · {{ \Carbon\Carbon::parse($virtualClass->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($virtualClass->end_time)->format('g:i A') }}</div>
        </div>
        <div class="vc-room-meta"><i class="fa-solid fa-users"></i> <span id="participantCount">1</span> connected</div>
    </div>

    <div class="vc-room-body">
        <main class="vc-stage">
            <div class="vc-join-panel" id="joinPanel">
                <div style="font-size:3rem;color:#60a5fa;margin-bottom:12px"><i class="fa-solid fa-video"></i></div>
                <h3 style="color:#f9fafb;margin:0 0 8px">Ready to join?</h3>
                <p>Allow camera and microphone access when your browser asks. You can turn them off anytime.</p>
                <button type="button" class="vc-control primary" id="connectButton"><i class="fa-solid fa-right-to-bracket"></i> Join Live Classroom</button>
            </div>
            <div class="vc-video-grid" id="videoGrid" hidden></div>
            <div class="vc-controls" id="controls" hidden>
                <button type="button" class="vc-control" id="micButton" disabled><i class="fa-solid fa-microphone"></i> Mute</button>
                <button type="button" class="vc-control" id="cameraButton" disabled><i class="fa-solid fa-video"></i> Camera Off</button>
                <button type="button" class="vc-control" id="screenButton" disabled><i class="fa-solid fa-display"></i> Share Screen</button>
                <button type="button" class="vc-control danger" id="leaveButton"><i class="fa-solid fa-phone-slash"></i> Leave Class</button>
            </div>
            <div class="vc-audio-root" id="audioRoot"></div>
        </main>

        <aside class="vc-side">
            <div class="vc-side-tabs"><i class="fa-regular fa-comments"></i> Class Chat</div>
            <div class="vc-chat-log" id="chatLog" aria-live="polite">
                <p class="vc-placeholder" id="chatPlaceholder">Join the classroom to send and receive messages.</p>
            </div>
            <form class="vc-chat-form" id="chatForm">
                <input type="text" id="chatInput" maxlength="1000" placeholder="Write a message…" aria-label="Chat message" disabled>
                <button type="submit" id="sendChatButton" disabled>Send</button>
            </form>
        </aside>
    </div>
    <div class="vc-status" id="connectionStatus" role="status">Not connected. Click “Join Live Classroom” to connect.</div>
</div>

<div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px">
    <a class="btn-modal-cancel" href="{{ $backUrl }}" style="text-decoration:none;padding:10px 14px;border-radius:8px"><i class="fa-solid fa-arrow-left"></i> Back to class details</a>
    <span style="font-size:.8rem;color:#64748b;align-self:center">Please follow your institution’s online-class conduct and privacy policies.</span>
</div>

<script type="module">
import { Room, RoomEvent, Track } from 'https://cdn.jsdelivr.net/npm/livekit-client@2.15.6/+esm';

const tokenUrl = @json($tokenUrl);
const leaveUrl = @json($leaveUrl);
const backUrl = @json($backUrl);
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
const currentName = @json($displayName);
const connectButton = document.getElementById('connectButton');
const leaveButton = document.getElementById('leaveButton');
const micButton = document.getElementById('micButton');
const cameraButton = document.getElementById('cameraButton');
const screenButton = document.getElementById('screenButton');
const statusEl = document.getElementById('connectionStatus');
const videoGrid = document.getElementById('videoGrid');
const joinPanel = document.getElementById('joinPanel');
const controls = document.getElementById('controls');
const audioRoot = document.getElementById('audioRoot');
const participantCount = document.getElementById('participantCount');
const chatLog = document.getElementById('chatLog');
const chatPlaceholder = document.getElementById('chatPlaceholder');
const chatInput = document.getElementById('chatInput');
const sendChatButton = document.getElementById('sendChatButton');
const chatForm = document.getElementById('chatForm');
let room = null;
let micEnabled = false;
let cameraEnabled = false;
let screenEnabled = false;
let leaving = false;
const tiles = new Map();

function setStatus(message, error = false) {
    statusEl.textContent = message;
    statusEl.classList.toggle('error', error);
}

function getTile(identity, name) {
    let tile = tiles.get(identity);
    if (!tile) {
        tile = document.createElement('div');
        tile.className = 'vc-tile';
        tile.dataset.identity = identity;
        const label = document.createElement('div');
        label.className = 'vc-tile-name';
        label.textContent = name || identity;
        tile.appendChild(label);
        videoGrid.appendChild(tile);
        tiles.set(identity, tile);
    }
    return tile;
}

function tileKeyFor(track, participant) {
    // Screen shares get their own tile so the screen video does not stack on
    // top of the participant's camera video in the same tile.
    return track.source === Track.Source.ScreenShare
        ? participant.identity + ':screen'
        : participant.identity;
}

function tileNameFor(track, participant) {
    if (track.source === Track.Source.ScreenShare) {
        return (participant.name || participant.identity) + ' — Screen';
    }
    return participant.name || participant.identity;
}

function removeTile(key) {
    const tile = tiles.get(key);
    if (tile) {
        tile.remove();
        tiles.delete(key);
    }
}

function attachTrack(track, participant) {
    if (!track || !participant) return;
    const key = tileKeyFor(track, participant);
    const element = track.attach();
    element.autoplay = true;
    element.playsInline = true;
    if (track.kind === Track.Kind.Video) {
        element.style.width = '100%';
        element.style.height = '100%';
        element.style.minHeight = '210px';
        element.style.objectFit = 'contain';
        getTile(key, tileNameFor(track, participant)).appendChild(element);
    } else {
        // Audio (camera mic, screen-share audio) is played through hidden roots.
        audioRoot.appendChild(element);
    }
}

function addChatMessage(name, message) {
    if (chatPlaceholder) chatPlaceholder.remove();
    const row = document.createElement('p');
    row.className = 'vc-chat-message';
    const strong = document.createElement('strong');
    strong.textContent = name || 'Participant';
    const span = document.createElement('span');
    span.textContent = message;
    row.append(strong, span);
    chatLog.appendChild(row);
    chatLog.scrollTop = chatLog.scrollHeight;
}

function updateParticipants() {
    // livekit-client v2: numParticipants already includes the local
    // participant, so no extra +1 (it used to over-count by one).
    participantCount.textContent = room ? String(room.numParticipants) : '1';
}

function enableControls() {
    controls.hidden = false;
    videoGrid.hidden = false;
    chatInput.disabled = false;
    sendChatButton.disabled = false;
    micButton.disabled = false;
    cameraButton.disabled = false;
    screenButton.disabled = false;
}

async function connectToRoom() {
    connectButton.disabled = true;
    setStatus('Connecting securely to the virtual classroom…');
    try {
        const response = await fetch(tokenUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({}),
            credentials: 'same-origin'
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'Could not authorize this meeting.');
        if (!data.url || !data.token) throw new Error('The server did not return a valid meeting token.');

        room = new Room({ adaptiveStream: true, dynacast: true });
        room.on(RoomEvent.TrackSubscribed, (track, publication, participant) => attachTrack(track, participant));
        room.on(RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
            track.detach().forEach(element => element.remove());
            // Remote camera / screen share stopped: drop the now-empty tile.
            if (participant) removeTile(tileKeyFor(track, participant));
        });
        room.on(RoomEvent.LocalTrackUnpublished, (publication) => {
            // Camera off / screen share stopped locally: drop the stale tile
            // so the last frame does not linger on screen.
            if (room && publication) removeTile(tileKeyFor(publication, room.localParticipant));
        });
        room.on(RoomEvent.LocalTrackPublished, (publication) => {
            if (publication.track) attachTrack(publication.track, room.localParticipant);
        });
        room.on(RoomEvent.ParticipantConnected, updateParticipants);
        room.on(RoomEvent.ParticipantDisconnected, (participant) => {
            // Remove both the camera tile and the screen-share tile.
            removeTile(participant.identity);
            removeTile(participant.identity + ':screen');
            updateParticipants();
        });
        room.on(RoomEvent.DataReceived, (payload, participant) => {
            try {
                const message = new TextDecoder().decode(payload);
                const parsed = JSON.parse(message);
                if (parsed && typeof parsed.text === 'string') addChatMessage(parsed.name || participant?.name || participant?.identity || 'Participant', parsed.text);
            } catch (_) { /* Ignore non-chat data packets. */ }
        });
        room.on(RoomEvent.Disconnected, () => {
            if (!leaving) {
                setStatus('Disconnected from the virtual classroom. You can reconnect.', true);
                joinPanel.hidden = false;
                videoGrid.hidden = true;
                controls.hidden = true;
                connectButton.disabled = false;
                connectButton.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Reconnect';
            }
        });

        await room.connect(data.url, data.token, { autoSubscribe: true });
        joinPanel.hidden = true;
        enableControls();
        updateParticipants();
        room.localParticipant.trackPublications.forEach(publication => {
            if (publication.track) attachTrack(publication.track, room.localParticipant);
        });

        try {
            await room.localParticipant.setCameraEnabled(true);
            cameraEnabled = true;
        } catch (error) {
            cameraEnabled = false;
            addChatMessage('System', 'Camera permission was not granted. You can still listen and use chat.');
            setStatus('Camera unavailable: ' + (error?.message || 'permission denied or no camera found') + '. Audio and chat still work.', true);
        }
        try {
            await room.localParticipant.setMicrophoneEnabled(true);
            micEnabled = true;
        } catch (error) {
            micEnabled = false;
            addChatMessage('System', 'Microphone permission was not granted. Check your browser settings.');
            setStatus('Microphone unavailable: ' + (error?.message || 'permission denied or no microphone found') + '. You can still use chat.', true);
        }
        updateControlLabels();
        setStatus('Connected as ' + (data.name || currentName) + '.');
    } catch (error) {
        setStatus(error.message || 'Unable to connect. Check your network and LiveKit configuration.', true);
        connectButton.disabled = false;
    }
}

function updateControlLabels() {
    micButton.innerHTML = micEnabled ? '<i class="fa-solid fa-microphone"></i> Mute' : '<i class="fa-solid fa-microphone-slash"></i> Unmute';
    micButton.classList.toggle('muted', !micEnabled);
    cameraButton.innerHTML = cameraEnabled ? '<i class="fa-solid fa-video"></i> Camera Off' : '<i class="fa-solid fa-video-slash"></i> Camera On';
    cameraButton.classList.toggle('muted', !cameraEnabled);
    screenButton.innerHTML = screenEnabled ? '<i class="fa-solid fa-display"></i> Stop Sharing' : '<i class="fa-solid fa-display"></i> Share Screen';
}

connectButton.addEventListener('click', connectToRoom);
micButton.addEventListener('click', async () => {
    if (!room) return;
    try { await room.localParticipant.setMicrophoneEnabled(!micEnabled); micEnabled = !micEnabled; updateControlLabels(); }
    catch (error) { setStatus('Could not change microphone state: ' + error.message, true); }
});
cameraButton.addEventListener('click', async () => {
    if (!room) return;
    try { await room.localParticipant.setCameraEnabled(!cameraEnabled); cameraEnabled = !cameraEnabled; updateControlLabels(); }
    catch (error) { setStatus('Could not change camera state: ' + error.message, true); }
});
screenButton.addEventListener('click', async () => {
    if (!room) return;
    try { await room.localParticipant.setScreenShareEnabled(!screenEnabled); screenEnabled = !screenEnabled; updateControlLabels(); }
    catch (error) { setStatus('Screen sharing was cancelled or is not supported by this browser.', true); }
});
chatForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const text = chatInput.value.trim();
    if (!room || !text) return;
    try {
        const payload = new TextEncoder().encode(JSON.stringify({ name: currentName, text }));
        await room.localParticipant.publishData(payload, { reliable: true, topic: 'class-chat' });
        addChatMessage('You', text);
        chatInput.value = '';
    } catch (error) { setStatus('Could not send chat message.', true); }
});

async function leaveRoom() {
    if (leaving) return;
    leaving = true;
    leaveButton.disabled = true;
    if (room) {
        try { await room.disconnect(); } catch (_) {}
    }
    try {
        await fetch(leaveUrl, {
            method: 'POST',
            headers: { 'Accept': 'text/html', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            redirect: 'manual'
        });
    } catch (_) {}
    window.location.href = backUrl;
}
leaveButton.addEventListener('click', leaveRoom);
window.addEventListener('pagehide', () => {
    if (!leaving && room) {
        const body = new URLSearchParams({ _token: csrf });
        navigator.sendBeacon(leaveUrl, body);
    }
});
</script>
@endsection
