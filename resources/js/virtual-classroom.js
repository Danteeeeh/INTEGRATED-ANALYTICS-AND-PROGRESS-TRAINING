/**
 * Virtual Classroom (LiveKit) client.
 *
 * Bundled by Vite instead of importing from a CDN, so the room keeps working
 * when the browser enforces a strict CSP or the network blocks external
 * scripts. Configuration is read from data attributes on #virtualClassroom,
 * which keeps the Blade view free of inline JavaScript.
 */
import { DisconnectReason, Room, RoomEvent, Track } from 'livekit-client';

(function () {
    'use strict';

    const root = document.getElementById('virtualClassroom');

    // This entry is only loaded by the classroom view; a stray include on
    // another page must never throw.
    if (!root) return;

    /* ── Configuration ── */
    const tokenUrl = root.dataset.tokenUrl || '';
    const leaveUrl = root.dataset.leaveUrl || '';
    const backUrl = root.dataset.backUrl || '';
    const endUrl = root.dataset.endUrl || '';
    const muteAllUrl = root.dataset.muteAllUrl || '';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const currentName = root.dataset.displayName || '';
    const isInstructor = root.dataset.instructor === '1';
    const CHAT_TOPIC = 'class-chat';
    const HAND_TOPIC = 'class-hand';
    const REACTION_TOPIC = 'class-reaction';
    const MUTE_ALL_TOPIC = 'class-mute-all';
    const END_MEETING_TOPIC = 'class-end-meeting';
    const AVATAR_COLOURS = ['#2563eb', '#7c3aed', '#0891b2', '#059669', '#b45309', '#be123c', '#4f46e5', '#0e7490'];

    /* ── DOM refs ── */
    const $ = (id) => document.getElementById(id);
    const el = {
        lobby: $('lobby'), lobbyVideo: $('lobbyVideo'), lobbyAvatar: $('lobbyAvatar'), lobbyHint: $('lobbyHint'),
        lobbyMic: $('lobbyMicButton'), lobbyCam: $('lobbyCameraButton'), join: $('joinButton'),
        videoGrid: $('videoGrid'), controls: $('controls'), audioRoot: $('audioRoot'),
        mic: $('micButton'), camera: $('cameraButton'), screen: $('screenButton'),
        hand: $('handButton'), react: $('reactButton'), reactPop: $('reactPop'),
        chat: $('chatButton'), people: $('peopleButton'), spotlight: $('spotlightButton'), fullscreen: $('fullscreenButton'), settings: $('settingsButton'), leave: $('leaveButton'),
        chatTab: $('chatTabButton'), peopleTab: $('peopleTabButton'), chatPanel: $('chatPanel'), peoplePanel: $('peoplePanel'),
        chatLog: $('chatLog'), chatPlaceholder: $('chatPlaceholder'), chatInput: $('chatInput'), chatSend: $('sendChatButton'), chatForm: $('chatForm'),
        chatUnread: $('chatUnread'), peopleList: $('peopleList'), peopleCount: $('peopleCount'),
        status: $('connectionStatus'), count: $('participantCount'), timer: $('vcTimer'),
        qualityBars: $('qualityBars'), qualityLabel: $('qualityLabel'), qualityChip: $('qualityChip'),
        invite: $('inviteButton'), stage: $('vcStage'), roomBody: $('vcRoomBody'), side: $('vcSide'),
        settingsPop: $('settingsPop'), deviceCamera: $('deviceCamera'), deviceMic: $('deviceMic'), deviceSpeaker: $('deviceSpeaker'),
        instructorPop: $('instructorPop'), muteAll: $('muteAllButton'), endMeetingPop: $('endMeetingPopButton'),
        info: $('infoButton'), endMeeting: $('endMeetingButton'),
    };

    /* ── State ── */
    let room = null, connecting = false, leaving = false, failQuietly = false;
    let micEnabled = false, cameraEnabled = false, screenEnabled = false, handRaised = false;
    let connectedAt = null, timerInterval = null, unread = 0, activePanel = 'chat', spotlightKey = null;
    let lobbyStream = null, lobbyMicOn = false, lobbyCamOn = false;
    const tiles = new Map(), seenMessages = new Set(), raisedHands = new Set();
    const AVATAR_COLOUR = (identity) => AVATAR_COLOURS[Math.abs([...String(identity)].reduce((h, c) => (h * 31 + c.charCodeAt(0)) | 0, 7)) % AVATAR_COLOURS.length];
    const initials = (name) => String(name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase() || '?';
    const uid = () => crypto?.randomUUID ? crypto.randomUUID() : Date.now() + '-' + Math.random().toString(16).slice(2);

    function setStatus(message, error = false) {
        el.status.innerHTML = '';
        const icon = document.createElement('i');
        icon.className = error ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-info';
        el.status.append(icon, document.createTextNode(' ' + message));
        el.status.classList.toggle('error', error);
    }
    function toast(message) {
        const t = document.createElement('div'); t.className = 'vc-toast'; t.textContent = message;
        document.body.appendChild(t); setTimeout(() => t.remove(), 2600);
    }

    /* ── Quality + timer ── */
    function updateQuality(q) {
        const cls = q >= 2 ? 'good' : q === 1 ? 'fair' : 'poor';
        el.qualityBars.className = 'vc-quality ' + cls;
        el.qualityLabel.textContent = q >= 2 ? 'Good' : q === 1 ? 'Fair' : 'Poor';
        el.qualityChip.title = 'Connection quality: ' + el.qualityLabel.textContent;
    }
    function startTimer() {
        connectedAt = Date.now(); el.timer.hidden = false; clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            const s = Math.floor((Date.now() - connectedAt) / 1000);
            el.timer.textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
        }, 1000);
    }
    function stopTimer() {
        clearInterval(timerInterval); timerInterval = null; el.timer.hidden = true; el.timer.textContent = '00:00';
        el.qualityBars.className = 'vc-quality'; el.qualityLabel.textContent = '—';
    }

    /* ── Tiles ── */
    function tileKeyFor(track, p) { return track.source === Track.Source.ScreenShare ? p.identity + ':screen' : p.identity; }
    function tileNameFor(track, p) { return track.source === Track.Source.ScreenShare ? (p.name || p.identity) + ' — Screen' : p.name || p.identity; }
    function getTile(key, name) {
        let tile = tiles.get(key);
        if (!tile) {
            tile = document.createElement('div'); tile.className = 'vc-tile' + (key.endsWith(':screen') ? ' screen' : ''); tile.dataset.key = key;
            const avatar = document.createElement('div'); avatar.className = 'vc-tile-avatar';
            const disc = document.createElement('div'); disc.className = 'vc-avatar'; disc.style.background = AVATAR_COLOUR(key); disc.textContent = initials(name); avatar.appendChild(disc);
            const video = document.createElement('div'); video.className = 'vc-tile-video'; video.hidden = true;
            const badges = document.createElement('div'); badges.className = 'vc-tile-badges';
            const label = document.createElement('div'); label.className = 'vc-tile-name';
            const micOff = document.createElement('i'); micOff.className = 'fa-solid fa-microphone-slash'; micOff.hidden = true;
            label.append(micOff, document.createTextNode(' ' + (name || key)));
            tile.append(avatar, video, badges, label);
            tile.addEventListener('click', () => toggleSpotlight(key));
            el.videoGrid.appendChild(tile); tiles.set(key, tile);
        }
        return tile;
    }
    function attachTrack(track, p) {
        if (!track || !p) return;
        const key = tileKeyFor(track, p); const tile = getTile(key, tileNameFor(track, p));
        const element = track.attach(); element.autoplay = true; element.playsInline = true;
        element.style.width = '100%'; element.style.height = '100%';
        element.style.objectFit = track.source === Track.Source.ScreenShare ? 'contain' : 'cover'; element.style.background = '#020617';
        if (track.kind === Track.Kind.Video) { const h = tile.querySelector('.vc-tile-video'); h.appendChild(element); h.hidden = false; tile.querySelector('.vc-tile-avatar').hidden = true; }
        else el.audioRoot.appendChild(element);
    }
    function detachTrack(track, p) {
        if (!track || !p) return; track.detach().forEach(n => n.remove());
        const key = tileKeyFor(track, p); const tile = tiles.get(key);
        if (tile && track.kind === Track.Kind.Video) { const h = tile.querySelector('.vc-tile-video'); h.innerHTML = ''; h.hidden = true; tile.querySelector('.vc-tile-avatar').hidden = false; }
    }
    function removeTile(key) { const t = tiles.get(key); if (t) { t.remove(); tiles.delete(key); } if (spotlightKey === key) spotlightKey = null; }
    function toggleSpotlight(key) { spotlightKey = spotlightKey === key ? null : key; tiles.forEach((t, k) => t.classList.toggle('single', k === spotlightKey)); el.videoGrid.classList.toggle('single', spotlightKey !== null); }

    /* ── Participants ── */
    function renderParticipants() {
        if (!room) { el.peopleList.innerHTML = ''; el.peopleCount.textContent = '0'; return; }
        const rows = []; const push = (p, isLocal) => {
            const identity = p.identity, name = p.name || identity, hand = raisedHands.has(identity), micOff = p.isMicrophoneEnabled === false;
            rows.push(`<div class="vc-person" data-identity="${identity.replace(/"/g, '&quot;')}">
                <span class="vc-person-avatar" style="background:${AVATAR_COLOUR(identity)}">${initials(name)}</span>
                <span class="vc-person-body">
                    <span class="vc-person-name">${name.replace(/</g, '&lt;')}${isLocal ? ' (You)' : ''}</span>
                    <span class="vc-person-role">${isLocal ? 'You' : (p.isSpeaking ? 'Speaking' : 'Participant')}${hand ? ' · hand raised' : ''}</span>
                </span>
                <span class="vc-person-icons">${hand ? '<i class="fa-solid fa-hand hand" title="Hand raised"></i>' : ''}${micOff ? '<i class="fa-solid fa-microphone-slash" title="Microphone off"></i>' : ''}</span>
            </div>`);
        };
        push(room.localParticipant, true); room.remoteParticipants.forEach(p => push(p, false));
        el.peopleList.innerHTML = rows.join(''); el.peopleCount.textContent = String(room.numParticipants); el.count.textContent = String(room.numParticipants);
        renderTileBadges();
    }
    function renderTileBadges() {
        if (!room) return;
        const all = [room.localParticipant, ...room.remoteParticipants.values()];
        all.forEach(p => {
            const tile = tiles.get(p.identity); if (!tile) return;
            const micOff = p.isMicrophoneEnabled === false; const micIcon = tile.querySelector('.vc-tile-name i');
            if (micIcon) micIcon.hidden = !micOff;
            const badges = tile.querySelector('.vc-tile-badges'); badges.innerHTML = '';
            if (p.isLocal) { const you = document.createElement('span'); you.className = 'vc-tile-badge you'; you.textContent = 'You'; badges.appendChild(you); }
            if (raisedHands.has(p.identity)) { const h = document.createElement('span'); h.className = 'vc-tile-badge'; h.innerHTML = '<i class="fa-solid fa-hand"></i>'; badges.appendChild(h); }
            tile.classList.toggle('speaking', p.isSpeaking === true);
        });
    }

    /* ── Chat ── */
    function addChatMessage(name, text, ts, { mine = false, system = false, id = null } = {}) {
        const messageId = id || uid(); if (seenMessages.has(messageId)) return; seenMessages.add(messageId);
        if (el.chatPlaceholder) el.chatPlaceholder.remove();
        const row = document.createElement('p'); row.className = 'vc-chat-message' + (mine ? ' mine' : '') + (system ? ' system' : '');
        const strong = document.createElement('strong'); strong.textContent = (system ? 'System · ' : '') + (name || 'Participant') + (ts ? ' · ' + new Date(ts).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '');
        const span = document.createElement('span'); span.textContent = text; row.append(strong, span);
        el.chatLog.appendChild(row); el.chatLog.scrollTop = el.chatLog.scrollHeight;
        if (!mine && activePanel !== 'chat') { unread += 1; el.chatUnread.textContent = String(unread); el.chatUnread.classList.add('show'); el.chat.classList.add('has-dot'); }
    }
    function publish(topic, payload) {
        if (!room) return Promise.resolve(false);
        const bytes = new TextEncoder().encode(JSON.stringify(payload));
        return room.localParticipant.publishData(bytes, { reliable: true, topic }).then(() => true).catch(() => false);
    }

    /* ── Lobby preview ── */
    async function startLobbyPreview() {
        if (lobbyStream) return true; if (!navigator.mediaDevices?.getUserMedia) { el.lobbyHint.textContent = 'This browser does not expose camera/microphone controls.'; return false; }
        try {
            lobbyStream = await navigator.mediaDevices.getUserMedia({ video: lobbyCamOn, audio: lobbyMicOn });
            lobbyStream.getVideoTracks().forEach(t => t.enabled = lobbyCamOn); lobbyStream.getAudioTracks().forEach(t => t.enabled = lobbyMicOn);
            el.lobbyVideo.srcObject = lobbyStream; el.lobbyVideo.hidden = !lobbyCamOn; el.lobbyAvatar.hidden = lobbyCamOn;
            el.lobbyHint.textContent = lobbyCamOn || lobbyMicOn ? 'Preview on. Leave them off to join silently.' : 'All devices off — join muted.';
            return true;
        } catch (e) { lobbyStream = null; el.lobbyVideo.hidden = true; el.lobbyAvatar.hidden = false; el.lobbyHint.textContent = 'Camera/microphone unavailable or denied — join anyway with chat.'; return false; }
    }
    function stopLobbyPreview() { if (!lobbyStream) return; lobbyStream.getTracks().forEach(t => t.stop()); lobbyStream = null; el.lobbyVideo.srcObject = null; el.lobbyVideo.hidden = true; el.lobbyAvatar.hidden = false; }
    function setLobbyControlState() { el.lobbyMic.innerHTML = lobbyMicOn ? '<i class="fa-solid fa-microphone"></i>' : '<i class="fa-solid fa-microphone-slash"></i>'; el.lobbyMic.classList.toggle('muted', !lobbyMicOn); el.lobbyCam.innerHTML = lobbyCamOn ? '<i class="fa-solid fa-video"></i>' : '<i class="fa-solid fa-video-slash"></i>'; el.lobbyCam.classList.toggle('muted', !lobbyCamOn); }
    el.lobbyMic.addEventListener('click', async () => { lobbyMicOn = !lobbyMicOn; setLobbyControlState(); if (lobbyMicOn) await startLobbyPreview(); lobbyStream?.getAudioTracks().forEach(t => t.enabled = lobbyMicOn); });
    el.lobbyCam.addEventListener('click', async () => { lobbyCamOn = !lobbyCamOn; setLobbyControlState(); if (lobbyCamOn) await startLobbyPreview(); lobbyStream?.getVideoTracks().forEach(t => t.enabled = lobbyCamOn); });
    setLobbyControlState();

    /* ── Join / reconnect ── */
    async function join() {
        if (connecting || leaving) return; connecting = true; el.join.disabled = true;
        if (room) { failQuietly = true; try { await room.disconnect(); } catch (_) { } room = null; }
        stopLobbyPreview(); setStatus('Requesting access token…');
        let data;
        try {
            const r = await fetch(tokenUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: '{}', credentials: 'same-origin' });
            data = await r.json().catch(() => ({})); if (!r.ok) throw new Error(data.message || ('Token failed ' + r.status)); if (!data.url || !data.token) throw new Error('No valid token returned.');
        } catch (e) { connecting = false; el.join.disabled = false; setStatus(e.message, true); return; }
        setStatus('Connecting to classroom…');
        room = new Room({ adaptiveStream: true, dynacast: true });
        room.on(RoomEvent.Connected, () => {
            connecting = false; failQuietly = false; startTimer();
            setStatus('Connected as ' + (data.name || currentName) + '.');
            el.lobby.hidden = true; el.videoGrid.hidden = false; el.controls.hidden = false;
            [el.mic, el.camera, el.screen, el.hand, el.react].forEach(b => b.disabled = false);
            el.chatInput.disabled = false; el.chatSend.disabled = false; syncDeviceButtons(); renderParticipants();
            room.remoteParticipants.forEach(p => p.trackPublications.forEach(pub => { if (pub.track && pub.track.isSubscribed) attachTrack(pub.track, p); }));
            if (lobbyMicOn) void enableMicrophone(); if (lobbyCamOn) void enableCamera();
        });
        room.on(RoomEvent.Reconnecting, () => setStatus('Reconnecting…'));
        room.on(RoomEvent.Reconnected, () => setStatus('Reconnected.'));
        room.on(RoomEvent.ConnectionQualityChanged, (q, p) => { if (!p || p.isLocal) updateQuality(Number(q)); });
        room.on(RoomEvent.ParticipantConnected, (p) => { renderParticipants(); addChatMessage('System', p.name || p.identity + ' joined.', Date.now(), { system: true }); });
        room.on(RoomEvent.ParticipantDisconnected, (p) => { removeTile(p.identity); removeTile(p.identity + ':screen'); raisedHands.delete(p.identity); renderParticipants(); addChatMessage('System', p.name || p.identity + ' left.', Date.now(), { system: true }); });
        room.on(RoomEvent.TrackSubscribed, (t, pub, p) => attachTrack(t, p));
        room.on(RoomEvent.TrackUnsubscribed, (t, pub, p) => detachTrack(t, p));
        room.on(RoomEvent.LocalTrackPublished, (pub) => { if (pub.track) attachTrack(pub.track, room.localParticipant); });
        room.on(RoomEvent.LocalTrackUnpublished, (pub) => { if (pub?.track) detachTrack(pub.track, room.localParticipant); syncDeviceButtons(); });
        room.on(RoomEvent.ActiveSpeakersChanged, () => renderTileBadges());
        room.on(RoomEvent.DataReceived, (payload, p) => {
            let m; try { m = JSON.parse(new TextDecoder().decode(payload)); } catch (_) { return; } if (!m || typeof m !== 'object') return;
            const from = p?.name || p?.identity || 'Participant';
            if (m.kind === 'chat') addChatMessage(m.name || from, String(m.text || ''), m.ts, { id: m.id });
            else if (m.kind === 'hand') { if (m.raised) raisedHands.add(p?.identity || from); else raisedHands.delete(p?.identity || from); renderParticipants(); }
            else if (m.kind === 'reaction') floatReaction(String(m.emoji || '👍'), p?.identity);
            else if (m.kind === 'mute-all') { disableMicrophone(); setStatus('Instructor muted everyone.'); }
            else if (m.kind === 'end-meeting') { setStatus('Meeting ended by instructor.'); setTimeout(() => window.location.href = backUrl, 1500); }
        });
        room.on(RoomEvent.Disconnected, (reason) => {
            // The instructor ended the session from outside the room (dashboard
            // or auto-close): LiveKit deleted it, so there is nothing left to
            // reconnect to. Go back to the class page instead of flashing a
            // Reconnect button that can only fail.
            if (reason === DisconnectReason.ROOM_DELETED || reason === DisconnectReason.ROOM_CLOSED) {
                stopTimer();
                setStatus('The instructor ended this class. Returning to the class page…');
                window.setTimeout(() => { window.location.href = backUrl; }, 1500);
                return;
            }

            connecting = false; if (failQuietly) { failQuietly = false; return; }
            stopTimer(); micEnabled = cameraEnabled = screenEnabled = handRaised = false;
            [el.mic, el.camera, el.screen, el.hand, el.react].forEach(b => b.disabled = true);
            el.chatInput.disabled = true; el.chatSend.disabled = true; el.hand.classList.remove('active'); el.hand.innerHTML = '<i class="fa-regular fa-hand"></i>';
            tiles.forEach(t => t.remove()); tiles.clear(); raisedHands.clear(); renderParticipants();
            if (!leaving) { el.lobby.hidden = false; el.videoGrid.hidden = true; el.controls.hidden = true; el.join.disabled = false; el.join.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Reconnect'; setStatus('Disconnected. Click "Reconnect" to rejoin.', true); }
        });
        try { await room.connect(data.url, data.token, { autoSubscribe: true }); el.join.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> Join classroom'; el.join.disabled = false; }
        catch (e) {
            connecting = false; el.join.disabled = false; const raw = e?.message || 'Unable to connect.';
            if (/invalid token|token/i.test(raw)) setStatus('Token rejected. Verify LIVEKIT_API_KEY / LIVEKIT_API_SECRET / LIVEKIT_URL in environment variables (wss:// URL), then redeploy or run "php artisan config:clear". Reconnect requests a fresh token.', true);
            else setStatus(raw, true);
            failQuietly = true; try { await room.disconnect(); } catch (_) { } room = null;
        }
    }

    /* ── Device controls ── */
    async function enableMicrophone() { if (!room) return; try { await room.localParticipant.setMicrophoneEnabled(true); micEnabled = true; setStatus('Microphone on.'); } catch (e) { micEnabled = false; setStatus('Mic unavailable: ' + (e?.message || 'no mic found') + '. Chat still works.', true); } syncDeviceButtons(); }
    async function disableMicrophone() { if (!room) return; try { await room.localParticipant.setMicrophoneEnabled(false); micEnabled = false; setStatus('Microphone off.'); } catch (e) { setStatus('Mic error: ' + e.message, true); } syncDeviceButtons(); }
    async function enableCamera() { if (!room) return; try { await room.localParticipant.setCameraEnabled(true); cameraEnabled = true; setStatus('Camera on.'); } catch (e) { cameraEnabled = false; setStatus('Camera unavailable: ' + (e?.message || 'no camera found') + '. Audio/chat still work.', true); } syncDeviceButtons(); }
    async function disableCamera() { if (!room) return; try { await room.localParticipant.setCameraEnabled(false); cameraEnabled = false; setStatus('Camera off.'); } catch (e) { setStatus('Camera error: ' + e.message, true); } syncDeviceButtons(); }
    function syncDeviceButtons() { const p = room?.localParticipant; const mo = p ? p.isMicrophoneEnabled : micEnabled; const co = p ? p.isCameraEnabled : cameraEnabled; micEnabled = mo; cameraEnabled = co; el.mic.innerHTML = mo ? '<i class="fa-solid fa-microphone"></i>' : '<i class="fa-solid fa-microphone-slash"></i>'; el.mic.classList.toggle('muted', !mo); el.camera.innerHTML = co ? '<i class="fa-solid fa-video"></i>' : '<i class="fa-solid fa-video-slash"></i>'; el.camera.classList.toggle('muted', !co); el.screen.innerHTML = screenEnabled ? '<i class="fa-solid fa-display"></i> Stop' : '<i class="fa-solid fa-display"></i> Share'; }

    el.join.addEventListener('click', join);
    el.mic.addEventListener('click', () => { if (!room) return; const on = room.localParticipant.isMicrophoneEnabled; on ? disableMicrophone() : enableMicrophone(); });
    el.camera.addEventListener('click', () => { if (!room) return; const on = room.localParticipant.isCameraEnabled; on ? disableCamera() : enableCamera(); });
    el.screen.addEventListener('click', async () => { if (!room) return; try { await room.localParticipant.setScreenShareEnabled(!screenEnabled); screenEnabled = !screenEnabled; setStatus(screenEnabled ? 'Screen sharing on.' : 'Screen sharing stopped.'); } catch (e) { screenEnabled = false; setStatus('Screen share cancelled or unsupported.', true); } syncDeviceButtons(); });

    /* ── Hand + reactions ── */
    el.hand.addEventListener('click', async () => { if (!room) return; handRaised = !handRaised; const id = room.localParticipant.identity; if (handRaised) raisedHands.add(id); else raisedHands.delete(id); el.hand.classList.toggle('active', handRaised); el.hand.innerHTML = handRaised ? '<i class="fa-solid fa-hand"></i>' : '<i class="fa-regular fa-hand"></i>'; renderParticipants(); const s = await publish(HAND_TOPIC, { kind: 'hand', raised: handRaised }); if (!s) setStatus('Could not broadcast hand raise.', true); });
    el.react.addEventListener('click', () => { el.reactPop.hidden = !el.reactPop.hidden; });
    el.reactPop.querySelectorAll('button').forEach(b => { b.addEventListener('click', async () => { el.reactPop.hidden = true; const emoji = b.dataset.emoji; floatReaction(emoji, room?.localParticipant.identity); await publish(REACTION_TOPIC, { kind: 'reaction', emoji }); }); });
    function floatReaction(emoji, identity) { const host = identity ? tiles.get(identity) : null; const layer = document.createElement('div'); layer.className = 'vc-float'; const span = document.createElement('span'); span.textContent = emoji; layer.appendChild(span); (host || el.videoGrid).appendChild(layer); setTimeout(() => layer.remove(), 2500); }

    /* ── Side panel ── */
    function showPanel(name) { activePanel = name; el.chatPanel.hidden = name !== 'chat'; el.peoplePanel.hidden = name !== 'people'; el.chatTab.classList.toggle('active', name === 'chat'); el.peopleTab.classList.toggle('active', name === 'people'); el.chat.classList.toggle('active', name === 'chat' && !el.side.hidden); el.people.classList.toggle('active', name === 'people' && !el.side.hidden); if (name === 'chat') { unread = 0; el.chatUnread.classList.remove('show'); el.chat.classList.remove('has-dot'); } }
    el.chatTab.addEventListener('click', () => showPanel('chat'));
    el.peopleTab.addEventListener('click', () => showPanel('people'));
    el.chat.addEventListener('click', () => { el.side.hidden = !el.side.hidden; el.roomBody.classList.toggle('panel-hidden', el.side.hidden); if (!el.side.hidden) showPanel('chat'); });
    el.people.addEventListener('click', () => { el.side.hidden = !el.side.hidden; el.roomBody.classList.toggle('panel-hidden', el.side.hidden); if (!el.side.hidden) showPanel('people'); });
    el.spotlight.addEventListener('click', () => { if (spotlightKey) { toggleSpotlight(spotlightKey); return; } const first = tiles.keys().next().value; if (first) toggleSpotlight(first); });

    /* ── Settings (devices) ── */
    async function loadDevices() {
        if (!navigator.mediaDevices?.enumerateDevices) return;
        const devs = await navigator.mediaDevices.enumerateDevices();
        const fill = (sel, kind, fallbackLabel) => {
            sel.innerHTML = '';
            devs.filter(d => d.kind === kind).forEach(d => {
                const o = document.createElement('option');
                o.value = d.deviceId;
                o.textContent = d.label || fallbackLabel;
                sel.appendChild(o);
            });
            if (!sel.options.length) {
                const o = document.createElement('option');
                o.value = '';
                o.textContent = 'None found';
                sel.appendChild(o);
                sel.disabled = true;
            }
        };
        fill(el.deviceCamera, 'videoinput', 'Camera'); fill(el.deviceMic, 'audioinput', 'Microphone'); fill(el.deviceSpeaker, 'audiooutput', 'Speaker');
    }
    el.deviceCamera.addEventListener('change', async () => { if (!room || !el.deviceCamera.value) return; try { await room.switchActiveDevice('videoinput', el.deviceCamera.value); } catch (e) { setStatus('Camera switch failed: ' + e.message, true); } });
    el.deviceMic.addEventListener('change', async () => { if (!room || !el.deviceMic.value) return; try { await room.switchActiveDevice('audioinput', el.deviceMic.value); } catch (e) { setStatus('Mic switch failed: ' + e.message, true); } });
    el.deviceSpeaker.addEventListener('change', async () => { if (!room || !el.deviceSpeaker.value) return; try { await room.switchActiveDevice('audiooutput', el.deviceSpeaker.value); } catch (e) { setStatus('Speaker switch failed: ' + e.message, true); } });
    el.settings.addEventListener('click', () => { el.settingsPop.hidden = !el.settingsPop.hidden; if (!el.settingsPop.hidden) loadDevices(); el.reactPop.hidden = true; });

    /* ── Instructor admin ── */
    async function postJson(url) {
        try { const r = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }); return r.ok; } catch (_) { return false; }
    }
    /* Instructor-only controls are absent from a student's DOM — bind them
       defensively so a missing button can never abort the rest of this module. */
    if (el.muteAll) el.muteAll.addEventListener('click', async () => { if (!isInstructor) return; const ok = await postJson(muteAllUrl); if (ok) { await publish(MUTE_ALL_TOPIC, { kind: 'mute-all' }); toast('All participants muted.'); } else setStatus('Mute all failed.', true); if (el.instructorPop) el.instructorPop.hidden = true; });
    if (el.endMeetingPop) el.endMeetingPop.addEventListener('click', async () => { if (!isInstructor) return; if (!confirm('End this meeting for everyone?')) return; const ok = await postJson(endUrl); if (ok) { await publish(END_MEETING_TOPIC, { kind: 'end-meeting' }); toast('Meeting ended.'); window.location.href = backUrl; } else setStatus('End meeting failed.', true); if (el.instructorPop) el.instructorPop.hidden = true; });
    if (el.endMeeting) el.endMeeting.addEventListener('click', async () => { if (!isInstructor) return; if (!confirm('End this meeting for everyone?')) return; const ok = await postJson(endUrl); if (ok) { toast('Meeting ended.'); window.location.href = backUrl; } else setStatus('End meeting failed.', true); });

    /* ── Invite, fullscreen, panels ── */
    el.invite.addEventListener('click', async () => { try { await navigator.clipboard.writeText(window.location.href); toast('Link copied.'); } catch (_) { toast('Copy address bar link instead.'); } });
    el.fullscreen.addEventListener('click', () => {
        if (!document.fullscreenElement) el.stage.requestFullscreen().catch(_ => { });
        else document.exitFullscreen().catch(_ => { });
    });
    document.addEventListener('fullscreenchange', () => { el.fullscreen.classList.toggle('vc-fullscreen-enter', !!document.fullscreenElement); el.fullscreen.classList.toggle('vc-fullscreen-exit', !document.fullscreenElement); el.fullscreen.title = document.fullscreenElement ? 'Exit fullscreen' : 'Fullscreen'; });
    if (el.info) el.info.addEventListener('click', () => { toast('Class: ' + el.count.textContent + ' participants · ' + new Date().toLocaleTimeString()); });
    el.chatForm.addEventListener('submit', async (e) => {
        e.preventDefault(); const text = el.chatInput.value.trim(); if (!room || !text) return;
        const payload = { kind: 'chat', id: uid(), name: currentName, text, ts: Date.now() }; const s = await publish(CHAT_TOPIC, payload);
        if (s) { addChatMessage('You', text, payload.ts, { mine: true, id: payload.id }); el.chatInput.value = ''; } else setStatus('Send failed. Check connection.', true);
    });

    /* ── Leave ── */
    async function leaveRoom() {
        if (leaving) return; leaving = true; el.leave.disabled = true; stopLobbyPreview(); stopTimer();
        if (room) { try { await room.disconnect(); } catch (_) { } room = null; }
        try { await fetch(leaveUrl, { method: 'POST', headers: { 'Accept': 'text/html', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', redirect: 'manual' }); } catch (_) { }
        window.location.href = backUrl;
    }
    el.leave.addEventListener('click', leaveRoom);
    window.addEventListener('pagehide', () => { if (!leaving && room) { const b = new URLSearchParams({ _token: csrf }); navigator.sendBeacon(leaveUrl, b); } });

    /* ── Keyboard shortcuts ── */
    document.addEventListener('keydown', (e) => { if (e.target.matches('input,textarea,select')) return; if (!room) return; const k = e.key.toLowerCase(); if (k === 'm') el.mic.click(); else if (k === 'v') el.camera.click(); else if (k === 'h') el.hand.click(); else if (k === 'c') el.chat.click(); else if (k === 'p') el.people.click(); });

    showPanel('chat'); syncDeviceButtons();
})();
