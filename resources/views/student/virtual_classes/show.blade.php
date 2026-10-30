@extends('layouts.student')

@section('title', 'Virtual Class')
@php
    $activeNav = 'virtual_classes';
    $pageTitle = 'Virtual Class';
    $pageIcon = '<i class="fa-solid fa-video"></i>';

    // Same rule as the listing row (VirtualClass::studentCanJoin), so this page
    // cannot offer a Join button that only bounces back. A built-in LiveKit
    // classroom stays shut until the instructor starts it, so a session whose
    // start time has arrived while it is still only "scheduled" gets a waiting
    // state instead of a button that cannot work.
    $canJoin = $virtualClass->studentCanJoin();
    $startsAt = $virtualClass->startsAt();
    $startsLater = $startsAt !== null && now()->lt($startsAt);
    $awaitingInstructor = ! $startsLater
        && ! $virtualClass->hasEnded()
        && in_array($virtualClass->status, [
            \App\Models\VirtualClass::STATUS_SCHEDULED,
            \App\Models\VirtualClass::STATUS_ONGOING,
        ], true);
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            {{ $virtualClass->title }}
        </h2>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('student.classes.virtual_classes.index', $class) }}"
               class="btn-modal-cancel" style="display: inline-flex; align-items: center; padding: 8px 16px; border-radius: 6px; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> Back to Classes
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- A broken video setup is visible before the Join button below. --}}
    @if($livekitProblem)
        @include('partials.livekit-config-warning', ['problem' => $livekitProblem])
    @endif

    <!-- Meeting Status Card -->
    <div class="form-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h3 style="margin: 0 0 8px 0;"><i class="fa-solid fa-video"></i> {{ $virtualClass->title }}</h3>
                {{-- The poll below rewrites this badge in place, so it has to say
                     which state it is showing rather than re-deriving it. --}}
                <div id="classStatusBadge" data-status="{{ $virtualClass->status }}" style="opacity: 0.9; font-size: 0.9rem;">
                    @if($virtualClass->status === 'scheduled')
                        <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">
                            <i class="fa-solid fa-calendar"></i> Scheduled
                        </span>
                    @elseif($virtualClass->status === 'ongoing')
                        <span style="background: rgba(34, 197, 94, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">
                            <i class="fa-solid fa-circle fa-spin" style="font-size: 0.6rem;"></i> Live Now
                        </span>
                    @elseif($virtualClass->status === 'completed')
                        <span style="background: rgba(148, 163, 184, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">
                            <i class="fa-solid fa-check-circle"></i> Completed
                        </span>
                    @else
                        <span style="background: rgba(239, 68, 68, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">
                            <i class="fa-solid fa-times-circle"></i> Cancelled
                        </span>
                    @endif
                </div>

                {{-- The schedule sits on the same card as the badge: "Scheduled"
                     or "Live Now" alone says nothing about when the session was
                     meant to run. Static — the poll only changes the badge. --}}
                <p id="classScheduleLine" style="margin: 8px 0 0 0; opacity: 0.9; font-size: 0.85rem;">
                    <i class="fa-solid fa-calendar-day"></i>
                    {{ $virtualClass->meeting_date->format('l, M d, Y') }}
                    &middot;
                    {{ \Carbon\Carbon::parse($virtualClass->start_time)->format('g:i A') }}
                    &ndash;
                    {{ \Carbon\Carbon::parse($virtualClass->end_time)->format('g:i A') }}
                </p>
            </div>

            @if($canJoin)
                <form action="{{ route('student.classes.virtual_classes.join', [$class, $virtualClass]) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn-add" style="background: #22c55e; color: white; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 600; border: none;">
                        <i class="fa-solid fa-sign-in-alt"></i> Join Meeting
                    </button>
                </form>
            @elseif($startsLater || $awaitingInstructor)
                {{-- The page can be open while the class is still closed: before
                     its start time, or after it while the instructor has not
                     opened the room. The button polls the class state and
                     turns into a working Join form once the room opens, so the
                     student does not have to reload to notice the start. --}}
                <div id="joinWatch"
                     data-status-url="{{ route('student.classes.virtual_classes.status', [$class, $virtualClass]) }}"
                     data-token="{{ csrf_token() }}"
                     data-interval="{{ $startsLater ? 60000 : 20000 }}">
                    @if($startsLater)
                        <button disabled class="btn-modal-cancel" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; border-radius: 8px; cursor: not-allowed; font-size: 1rem; border: none;">
                            <i class="fa-solid fa-clock"></i> Not Started Yet
                        </button>
                    @else
                        <button disabled class="btn-modal-cancel" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; border-radius: 8px; cursor: not-allowed; font-size: 1rem; border: none;">
                            <i class="fa-solid fa-hourglass-half"></i> Waiting for Instructor
                        </button>
                    @endif
                </div>
            @else
                <button disabled class="btn-modal-cancel" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; border-radius: 8px; cursor: not-allowed; font-size: 1rem; border: none;">
                    <i class="fa-solid fa-check-circle"></i> Ended
                </button>
            @endif
        </div>
    </div>

    <!-- Meeting Details -->
    <div class="form-card">
        <h3><i class="fa-solid fa-info-circle"></i> Meeting Details</h3>

        <div class="modal-row">
            <span>Date:</span>
            <span>
                {{ $virtualClass->meeting_date->format('l, F d, Y') }}
                {{-- Shown for a live class, and switched on by the poll when the
                     instructor opens the room while the page is open. --}}
                <span id="dateLiveChip" style="display: {{ $virtualClass->status === 'ongoing' ? 'inline-flex' : 'none' }}; align-items: center; gap: 5px; margin-left: 8px; background: #dcfce7; color: #16a34a; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                    <i class="fa-solid fa-circle fa-spin" style="font-size: 0.5rem;"></i> Live now
                </span>
            </span>
        </div>

        <div class="modal-row">
            <span>Time:</span>
            <span>
                {{ \Carbon\Carbon::parse($virtualClass->start_time)->format('g:i A') }}
                &ndash;
                {{ \Carbon\Carbon::parse($virtualClass->end_time)->format('g:i A') }}
            </span>
        </div>

        <div class="modal-row">
            <span>Duration:</span>
            <span>{{ $virtualClass->durationLabel() }}</span>
        </div>

        <div class="modal-row">
            <span>Instructor:</span>
            <span>{{ $virtualClass->instructor->full_name }}</span>
        </div>

        <div class="modal-row">
            <span>Platform:</span>
            <span>
                <i class="fa-solid fa-video" style="margin-right: 4px;"></i> {{ $virtualClass->providerLabel() }}
            </span>
        </div>

        @if($virtualClass->description)
            <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-align-left"></i> Description</div>
            <div class="modal-row"><span></span><span>{{ $virtualClass->description }}</span></div>
        @endif

        @if($virtualClass->meeting_password)
            <div class="modal-row" style="margin-top: 8px;">
                <span>Password:</span>
                <span style="font-family: monospace; background: #f1f5f9; padding: 4px 8px; border-radius: 4px;">
                    {{ $virtualClass->meeting_password }}
                </span>
            </div>
        @endif
    </div>

    <!-- Meeting Link -->
    @if($virtualClass->meeting_url)
        <div class="form-card">
            <h3><i class="fa-solid fa-link"></i> Meeting Link</h3>
            <div style="display: flex; gap: 8px; margin-top: 16px;">
                <input type="text" value="{{ $virtualClass->meeting_url }}" readonly
                       style="flex: 1; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc;">
                <button type="button" onclick="copyMeetingLink()" class="btn-add" style="padding: 12px 20px; border-radius: 8px; cursor: pointer;">
                    <i class="fa-solid fa-copy"></i> Copy
                </button>
                <a href="{{ $virtualClass->meeting_url }}" target="_blank"
                   class="btn-add" style="padding: 12px 20px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center;">
                    <i class="fa-solid fa-external-link-alt"></i> Open
                </a>
            </div>
        </div>
    @endif

    <!-- Your Attendance -->
    <div class="form-card">
        <h3><i class="fa-solid fa-user-check"></i> Your Attendance</h3>

        @if($myAttendance)
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-top: 16px;">
                <div class="modal-row">
                    <span>Status:</span>
                    <span>
                        @if($myAttendance->attendance_status === 'present')
                            <span style="background: #dcfce7; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                <i class="fa-solid fa-check-circle"></i> Present
                            </span>
                        @elseif($myAttendance->attendance_status === 'late')
                            <span style="background: #fef3c7; color: #d97706; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                <i class="fa-solid fa-clock"></i> Late
                            </span>
                        @else
                            <span style="background: #fee2e2; color: #dc2626; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                <i class="fa-solid fa-times-circle"></i> Absent
                            </span>
                        @endif
                    </span>
                </div>

                @if($myAttendance->joined_at)
                    <div class="modal-row">
                        <span>Joined At:</span>
                        <span>{{ $myAttendance->joined_at->format('M d, Y g:i A') }}</span>
                    </div>
                @endif

                @if($myAttendance->left_at)
                    <div class="modal-row">
                        <span>Left At:</span>
                        <span>{{ $myAttendance->left_at->format('M d, Y g:i A') }}</span>
                    </div>
                @endif

                @if($myAttendance->duration_minutes)
                    <div class="modal-row">
                        <span>Duration:</span>
                        <span>{{ $myAttendance->duration_minutes }} minutes</span>
                    </div>
                @endif
            </div>
        @else
            <div style="padding: 20px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 8px; margin-top: 16px;">
                <i class="fa-solid fa-user-clock" style="font-size: 2rem; margin-bottom: 8px;"></i>
                <div>Attendance not yet recorded</div>
                <div style="font-size: 0.85rem; margin-top: 4px;">Join the meeting to record your attendance</div>
            </div>
        @endif
    </div>

    <!-- Meeting Guidelines -->
    <div class="form-card">
        <h3><i class="fa-solid fa-lightbulb"></i> Meeting Guidelines</h3>
        <ul style="margin-top: 16px; padding-left: 20px; color: #475569;">
            <li style="margin-bottom: 8px;">Join the meeting 5-10 minutes before the scheduled start time.</li>
            <li style="margin-bottom: 8px;">Test your microphone and camera before joining.</li>
            <li style="margin-bottom: 8px;">Ensure you have a stable internet connection.</li>
            <li style="margin-bottom: 8px;">Keep your microphone muted when not speaking.</li>
            <li style="margin-bottom: 8px;">Use a quiet environment with minimal background noise.</li>
            <li style="margin-bottom: 8px;">Have any required materials ready before the meeting starts.</li>
            <li style="margin-bottom: 8px;">Take notes during the session for better retention.</li>
        </ul>
    </div>

    <!-- Recurring Information -->
    @if($virtualClass->recurrence && is_array($virtualClass->recurrence))
        <div class="form-card">
            <h3><i class="fa-solid fa-redo"></i> Recurring Meeting</h3>
            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-top: 16px;">
                <div class="modal-row">
                    <span>Frequency:</span>
                    <span>{{ ucfirst($virtualClass->recurrence['frequency'] ?? 'weekly') }}</span>
                </div>

                @if(isset($virtualClass->recurrence['end_date']))
                    <div class="modal-row">
                        <span>Until:</span>
                        <span>{{ \Carbon\Carbon::parse($virtualClass->recurrence['end_date'])->format('M d, Y') }}</span>
                    </div>
                @endif

                @if(isset($virtualClass->recurrence['days']))
                    <div class="modal-row">
                        <span>Days:</span>
                        <span>{{ implode(', ', array_map('ucfirst', $virtualClass->recurrence['days'])) }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Add to Calendar -->
    <div class="form-card">
        <h3><i class="fa-solid fa-calendar-plus"></i> Add to Calendar</h3>
        <div style="display: flex; gap: 8px; margin-top: 16px;">
            <button type="button" onclick="addToGoogleCalendar()" class="btn-add" style="flex: 1; padding: 12px 20px; border-radius: 8px; cursor: pointer;">
                <i class="fa-solid fa-google"></i> Google Calendar
            </button>
            <button type="button" onclick="downloadICS()" class="btn-modal-cancel" style="flex: 1; padding: 12px 20px; border-radius: 8px; cursor: pointer;">
                <i class="fa-solid fa-download"></i> Download ICS
            </button>
        </div>
    </div>

    <script>
        function copyMeetingLink() {
            const link = '{{ $virtualClass->meeting_url }}';
            navigator.clipboard.writeText(link).then(() => {
                alert('Meeting link copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }

        function addToGoogleCalendar() {
            const title = encodeURIComponent('{{ $virtualClass->title }}');
            const start = encodeURIComponent('{{ $virtualClass->meeting_date->format('Ymd') }}T{{ str_replace(':', '', $virtualClass->start_time) }}00');
            const end = encodeURIComponent('{{ $virtualClass->meeting_date->format('Ymd') }}T{{ str_replace(':', '', $virtualClass->end_time) }}00');
            const description = encodeURIComponent('{{ $virtualClass->description ?? "Virtual class meeting" }}');
            const location = encodeURIComponent('{{ $virtualClass->meeting_url ?? "" }}');

            const url = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${start}/${end}&details=${description}&location=${location}`;
            window.open(url, '_blank');
        }

        function downloadICS() {
            const title = '{{ $virtualClass->title }}';
            const start = '{{ $virtualClass->meeting_date->format('Ymd') }}T{{ str_replace(':', '', $virtualClass->start_time) }}00';
            const end = '{{ $virtualClass->meeting_date->format('Ymd') }}T{{ str_replace(':', '', $virtualClass->end_time) }}00';
            const description = '{{ $virtualClass->description ?? "Virtual class meeting" }}';
            const location = '{{ $virtualClass->meeting_url ?? "" }}';

            const icsContent = `BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
DTSTART:${start}
DTEND:${end}
SUMMARY:${title}
DESCRIPTION:${description}
LOCATION:${location}
END:VEVENT
END:VCALENDAR`;

            const blob = new Blob([icsContent], { type: 'text/calendar' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${title.replace(/\s+/g, '_')}.ics`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Countdown timer for meeting start
        @if($virtualClass->status === 'scheduled' && now()->lt($virtualClass->meeting_date->setTimeFromTimeString($virtualClass->start_time)))
            const meetingStart = new Date('{{ $virtualClass->meeting_date->setTimeFromTimeString($virtualClass->start_time)->toIso8601String() }}');

            function updateCountdown() {
                const now = new Date();
                const diff = meetingStart - now;

                if (diff > 0) {
                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                    let countdownText = '';
                    if (days > 0) countdownText += days + 'd ';
                    countdownText += hours + 'h ' + minutes + 'm ' + seconds + 's';

                    const countdownElement = document.getElementById('countdown');
                    if (countdownElement) {
                        countdownElement.textContent = countdownText;
                    }
                }
            }

            setInterval(updateCountdown, 1000);
            updateCountdown();
        @endif

        // Watch a closed class until the instructor opens it. The buttons above
        // are disabled on purpose — the join route would bounce them — so once
        // the room is live the wait button is swapped for the real Join form in
        // place, without a reload and without losing the page state.
        (function () {
            const watch = document.getElementById('joinWatch');
            if (!watch) return;

            let timer = null;
            const stop = () => {
                if (timer) clearInterval(timer);
                timer = null;
            };

            // The button is not the only thing that says "scheduled": the badge
            // and the date row do too. Flip them with the button, otherwise the
            // card would offer Join while still announcing a class that has not
            // started.
            const markLive = () => {
                const badge = document.getElementById('classStatusBadge');
                if (badge && badge.dataset.status !== 'ongoing') {
                    badge.dataset.status = 'ongoing';
                    badge.innerHTML = '<span style="background:rgba(34,197,94,0.3);padding:4px 12px;border-radius:20px;font-size:0.85rem;">'
                        + '<i class="fa-solid fa-circle fa-spin" style="font-size:0.6rem;"></i> Live Now</span>';
                }

                const chip = document.getElementById('dateLiveChip');
                if (chip) {
                    chip.style.display = 'inline-flex';
                }
            };

            const joinForm = (url) => {
                const form = document.createElement('form');
                form.action = url;
                form.method = 'POST';
                form.style.display = 'inline';

                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = watch.dataset.token;
                form.appendChild(token);

                const button = document.createElement('button');
                button.type = 'submit';
                button.className = 'btn-add';
                button.style.cssText = 'background:#22c55e;color:white;padding:12px 24px;border-radius:8px;cursor:pointer;font-size:1rem;font-weight:600;border:none;';
                button.innerHTML = '<i class="fa-solid fa-sign-in-alt"></i> Join Meeting';
                form.appendChild(button);

                return form;
            };

            const check = async () => {
                try {
                    const response = await fetch(watch.dataset.statusUrl, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });

                    // A signed-out session redirects to the login page, which
                    // is not JSON: leave the button alone and stop pestering.
                    if (!response.ok || !(response.headers.get('content-type') || '').includes('application/json')) {
                        stop();
                        return;
                    }

                    const data = await response.json();

                    // The class is live, whatever the button below ends up
                    // doing: the badge and date row say so from now on.
                    if (data.status === 'ongoing') {
                        markLive();
                    }

                    if (data.can_join) {
                        stop();
                        watch.replaceWith(joinForm(data.join_url));
                        return;
                    }

                    // Past its end time, or closed by the instructor: there is
                    // nothing left to wait for.
                    if (data.ended || data.status === 'completed' || data.status === 'cancelled') {
                        stop();
                    }
                } catch (error) {
                    // Offline or a dropped request — keep the last state and
                    // try again on the next tick.
                }
            };

            timer = setInterval(check, parseInt(watch.dataset.interval, 10) || 20000);
        })();
    </script>
@endsection