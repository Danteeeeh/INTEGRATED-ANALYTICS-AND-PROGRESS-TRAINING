@extends('layouts.aa')

@section('title', 'AA Dashboard')
@php
    $activeNav = 'dashboard';
    $pageTitle = 'Academic Affairs Overview';
    $pageIcon = '<i class="fa-solid fa-building-columns"></i>';

    // Read-only display query for the LMS ← AA synchronisation panel.
    // The AA Staff role keeps view-only access; nothing here writes anything.
    $aaSyncRecent = \App\Models\AaSyncLog::latest('started_at')->take(4)->get();
    $aaLastSync = $aaSyncRecent->first();
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-building-columns"></i>
            Academic Affairs Overview
        </h2>
        <span class="topbar-role-badge" style="margin-left:8px;padding:4px 10px;border-radius:999px;background:rgba(98,201,245,.12);color:#62c9f5;font-size:.72rem;font-weight:700;white-space:nowrap;">View-Only</span>
    </div>
@endsection

@section('content')
<div class="user-page dash-polish">
    {{-- ═══ WELCOME HEADER ═══ --}}
    <x-user-page-header
        title="Welcome, {{ auth()->user()->name ?? 'AA Officer' }}"
        subtitle="A read-only overview of academic activity mirrored from the AA system."
        icon="fa-building-columns"
        kicker="Academic Affairs"
    >
        <x-slot name="meta">
            <span class="live-dot"></span>
            <span>{{ now()->format('l, F j, Y') }}</span>
            <span>·</span>
            <span><i class="fa-solid fa-eye"></i> View-only access</span>
            <span>·</span>
            <span><i class="fa-solid fa-rotate"></i> {{ $aaLastSync ? 'Synced ' . $aaLastSync->started_at?->diffForHumans() : 'No sync yet' }}</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('aa.reports.index') }}" class="btn btn-primary"><i class="fa-solid fa-file-lines"></i> Academic reports</a>
            <a href="{{ route('aa.progress.index') }}" class="btn btn-secondary"><i class="fa-solid fa-chart-line"></i> Learning progress</a>
        </x-slot>
    </x-user-page-header>

    {{-- Read-only notice: AA pa rin ang source of truth ng official records/grades. --}}
    <div class="dash-note">
        <i class="fa-solid fa-circle-info"></i>
        <span>Read-only view for Academic Affairs. Official records and final grades remain in the <strong>AA system</strong> — the LMS copy only syncs from AA.</span>
    </div>

    {{-- ═══ SUMMARY CARDS ═══ --}}
    <div class="dash-section">
        <div>
            <h3><i class="fa-solid fa-chart-pie"></i> Academic summary</h3>
            <span class="dash-section-kicker">Active people, courses and enrollment records synced from AA</span>
        </div>
        <a href="{{ route('aa.students.index') }}" class="btn btn-secondary"><i class="fa-solid fa-users"></i> Students</a>
    </div>
    <div class="user-stat-grid">
        <x-user-stat-card
            label="Students"
            value="{{ $stats['students'] ?? 0 }}"
            icon="fa-user-graduate"
            trend="Active"
            footer="Synced from AA"
        />
        <x-user-stat-card
            label="Faculty"
            value="{{ $stats['instructors'] ?? 0 }}"
            icon="fa-chalkboard-user"
            trend="Active"
            footer="Instructor accounts"
        />
        <x-user-stat-card
            label="Courses"
            value="{{ $stats['courses'] ?? 0 }}"
            icon="fa-book"
            trend="{{ $stats['classes'] ?? 0 }} active classes"
            footer="Across all programs"
        />
        <x-user-stat-card
            label="Active Enrollments"
            value="{{ $stats['enrollments_active'] ?? 0 }}"
            icon="fa-user-plus"
            trend="{{ $stats['enrollments_pending'] ?? 0 }} pending"
            footer="{{ $stats['enrollments_completed'] ?? 0 }} completed"
        />
    </div>

    {{-- ═══ DATA SYNCHRONISATION STATUS + QUICK ACCESS ═══ --}}
    <div class="dash-split">
        <div class="dash-split-main">
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-rotate"></i> Academic data synchronization</h4>
                    <span class="dash-outcome {{ $aaLastSync && $aaLastSync->status === 'success' ? 'd-ok' : ($aaLastSync && $aaLastSync->status === 'failed' ? 'd-err' : 'd-warn') }}">
                        <i class="fa-solid {{ $aaLastSync && $aaLastSync->status === 'success' ? 'fa-circle-check' : ($aaLastSync && $aaLastSync->status === 'failed' ? 'fa-circle-exclamation' : 'fa-clock') }}"></i>
                        {{ $aaLastSync ? ucfirst($aaLastSync->status) : 'No sync yet' }}
                    </span>
                </div>
                <div class="dash-status-band">
                    <div class="dash-band-item">
                        <span class="dash-band-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                        <div>
                            <span class="dash-band-label">Last sync</span>
                            <span class="dash-band-value">{{ $aaLastSync && $aaLastSync->started_at ? $aaLastSync->started_at->diffForHumans() : 'Never' }}</span>
                        </div>
                    </div>
                    <div class="dash-band-item">
                        <span class="dash-band-icon"><i class="fa-solid fa-user-gear"></i></span>
                        <div>
                            <span class="dash-band-label">Run by</span>
                            <span class="dash-band-value">{{ $aaLastSync?->performedBy?->name ?? 'system' }}</span>
                        </div>
                    </div>
                    <div class="dash-band-item">
                        <span class="dash-band-icon"><i class="fa-solid fa-database"></i></span>
                        <div>
                            <span class="dash-band-label">Last changes</span>
                            <span class="dash-band-value">
                                {{ ($aaLastSync->records_created ?? 0) }} new ·
                                {{ ($aaLastSync->records_updated ?? 0) }} updated ·
                                {{ ($aaLastSync->records_skipped ?? 0) }} skipped
                            </span>
                        </div>
                    </div>
                </div>
                @if($aaSyncRecent->isNotEmpty())
                    <table class="crud-table">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>By</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:center;">New</th>
                                <th style="text-align:center;">Updated</th>
                                <th style="text-align:center;">Skipped</th>
                                <th style="text-align:center;">Failed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($aaSyncRecent as $log)
                                <tr>
                                    <td>{{ $log->started_at?->format('M j, g:i A') ?? '—' }}</td>
                                    <td>{{ $log->performedBy?->name ?? 'system' }}</td>
                                    <td style="text-align:center;">
                                        <span class="badge badge-{{ $log->status === 'success' ? 'active' : ($log->status === 'failed' ? 'dropped' : 'pending') }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    </td>
                                    <td style="text-align:center;">{{ $log->records_created }}</td>
                                    <td style="text-align:center;">{{ $log->records_updated }}</td>
                                    <td style="text-align:center;">{{ $log->records_skipped }}</td>
                                    <td style="text-align:center;">{{ $log->records_failed }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <ul class="dash-list">
                        <li class="dash-list-empty"><i class="fa-solid fa-rotate"></i> No synchronization runs recorded yet.</li>
                    </ul>
                @endif
            </section>
        </div>

        <div class="dash-split-side">
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-bolt"></i> Academic reports &amp; data</h4>
                </div>
                <ul class="dash-link-list">
                    <li><a href="{{ route('aa.reports.index') }}"><i class="fa-solid fa-file-lines"></i> Assessment reports <span class="dash-link-go">View <i class="fa-solid fa-arrow-right"></i></span></a></li>
                    <li><a href="{{ route('aa.progress.index') }}"><i class="fa-solid fa-chart-line"></i> Learning progress <span class="dash-link-go">View <i class="fa-solid fa-arrow-right"></i></span></a></li>
                    <li><a href="{{ route('aa.courses.index') }}"><i class="fa-solid fa-book"></i> Courses <span class="dash-link-go">View <i class="fa-solid fa-arrow-right"></i></span></a></li>
                    <li><a href="{{ route('aa.students.index') }}"><i class="fa-solid fa-user-graduate"></i> Students <span class="dash-link-go">View <i class="fa-solid fa-arrow-right"></i></span></a></li>
                    <li><a href="{{ route('aa.enrollments.index') }}"><i class="fa-solid fa-user-plus"></i> Enrolment records <span class="dash-link-go">View <i class="fa-solid fa-arrow-right"></i></span></a></li>
                </ul>
            </section>

            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-clock-rotate-left"></i> Recent system updates</h4>
                    <span class="panel-count">{{ $aaSyncRecent->count() }}</span>
                </div>
                <ul class="dash-list">
                    @forelse($aaSyncRecent as $log)
                        <li class="dash-list-item">
                            <span class="dash-list-icon {{ $log->status === 'success' ? 'i-green' : ($log->status === 'failed' ? 'i-rose' : 'i-amber') }}">
                                <i class="fa-solid {{ $log->status === 'success' ? 'fa-circle-check' : ($log->status === 'failed' ? 'fa-circle-exclamation' : 'fa-clock') }}"></i>
                            </span>
                            <div class="dash-list-body">
                                <p class="dash-list-title">Sync {{ ucfirst($log->status) }}</p>
                                <p class="dash-list-sub">{{ ($log->records_created ?? 0) }} new · {{ ($log->records_updated ?? 0) }} updated · {{ ($log->records_failed ?? 0) }} failed</p>
                            </div>
                            <div class="dash-list-meta">
                                <span class="dash-list-date">{{ $log->started_at?->diffForHumans() }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="dash-list-empty"><i class="fa-solid fa-clock"></i> No updates recorded yet</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>

    {{-- ═══ COURSE OVERVIEW + RECENT ENROLLMENTS ═══ --}}
    <div class="dash-section">
        <div>
            <h3><i class="fa-solid fa-book"></i> Course &amp; enrollment overview</h3>
            <span class="dash-section-kicker">Compact, read-only tables of the synced academic records</span>
        </div>
        <a href="{{ route('aa.courses.index') }}" class="btn btn-secondary"><i class="fa-solid fa-list"></i> All courses</a>
    </div>
    <div class="dash-split">
        <div class="dash-split-main">
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-book"></i> Courses by enrollment</h4>
                    <a href="{{ route('aa.courses.index') }}" class="view-all">View all courses</a>
                </div>
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Code</th>
                            <th style="text-align:center;">Classes</th>
                            <th style="text-align:center;">Active Enrollments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($courseOverview as $course)
                            <tr>
                                <td>{{ $course->title }}</td>
                                <td>{{ $course->code }}</td>
                                <td style="text-align:center;">{{ $course->classes_count }}</td>
                                <td style="text-align:center;">{{ $course->enrollments_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--dash-muted);">No courses yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>

        <div class="dash-split-side">
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-user-plus"></i> Recent enrollments</h4>
                    <a href="{{ route('aa.enrollments.index') }}" class="view-all">View all</a>
                </div>
                <ul class="dash-list">
                    @forelse($recentEnrollments as $enrollment)
                        <li class="dash-list-item">
                            <span class="dash-list-icon i-green"><i class="fa-solid fa-user-graduate"></i></span>
                            <div class="dash-list-body">
                                <p class="dash-list-title">{{ $enrollment->student?->name ?? 'N/A' }}</p>
                                <p class="dash-list-sub">{{ $enrollment->class?->code ?? '' }} — {{ $enrollment->class?->course?->title ?? '' }}</p>
                            </div>
                            <div class="dash-list-meta">
                                <span class="dash-meta-chip {{ $enrollment->status === 'active' ? 'm-green' : ($enrollment->status === 'pending' ? 'm-amber' : 'm-gray') }}">{{ ucfirst($enrollment->status) }}</span>
                                <span class="dash-list-date">{{ $enrollment->created_at?->diffForHumans() }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="dash-list-empty"><i class="fa-solid fa-user-plus"></i> No enrollments yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</div>
@endsection
