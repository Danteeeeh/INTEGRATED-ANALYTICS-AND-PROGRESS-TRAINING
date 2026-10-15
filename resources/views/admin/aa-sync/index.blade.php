@extends('layouts.admin')

@section('title', 'Sync from AA')
@php
    $activeNav = 'aa-sync';
    $pageTitle = 'Sync from AA';
    $pageIcon = '<i class="fa-solid fa-rotate"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-rotate"></i>
            Sync from AA
        </h2>
    </div>
@endsection

@section('content')
    {{-- Flash messages --}}
    @if (session('success'))
        <div style="margin-bottom:14px;padding:12px 16px;background:#dcfce7;border:1px solid #86efac;border-radius:8px;font-size:.85rem;color:#166534;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div style="margin-bottom:14px;padding:12px 16px;background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;font-size:.85rem;color:#991b1b;">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
        </div>
    @endif

    {{-- ═══ CONNECTION STATUS ═══ --}}
    <div class="crud-card" style="margin-bottom:18px;">
        <div class="crud-header">
            <h3>AA database connection</h3>
        </div>
        <div style="padding:16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span style="display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:999px;font-weight:700;font-size:.82rem;
                {{ $connected ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#991b1b;' }}">
                <i class="fa-solid {{ $connected ? 'fa-plug-circle-check' : 'fa-plug-circle-xmark' }}"></i>
                {{ $connected ? 'Connected' : 'Not connected' }}
            </span>
            <span style="font-size:.82rem;color:#64748b;">
                {{ env('AA_DB_HOST', '127.0.0.1') }}:{{ env('AA_DB_PORT', '3306') }} ·
                {{ env('AA_DB_DATABASE', 'aa_system') }} ·
                user: {{ env('AA_DB_USERNAME', 'root') }}
            </span>
        </div>
        @unless ($connected)
            <div style="padding:0 16px 16px;font-size:.8rem;color:#991b1b;">
                {{ $connectionError ?: 'I-set ang AA_DB_HOST / AA_DB_PORT / AA_DB_DATABASE / AA_DB_USERNAME / AA_DB_PASSWORD sa .env, tapos i-restart ang app (config cache).' }}
            </div>
        @endunless
    </div>

    {{-- ═══ SYNC FORM ═══ --}}
    <div class="crud-card" style="margin-bottom:18px;">
        <div class="crud-header">
            <h3>Run sync</h3>
        </div>
        <div style="padding:16px;">
            <p style="margin:0 0 14px;font-size:.85rem;color:#475569;">
                Ang enrollment at official records ay <strong>galing na sa AA</strong> — hindi na naka-lock dito.
                Piliin kung anong data ang ilalagay mula sa AA database. Ang <strong>final grades ay hindi nagsi-sync</strong> —
                hawak pa rin ito ng AA; ang LMS ay may sariling quiz/assignment scores lang.
            </p>
            <form method="POST" action="{{ route('admin.aa-sync.run') }}"
                  onsubmit="return confirm('Run the AA sync now? This will upsert records into the LMS (new + existing accounts ay ginagalaw).');">
                @csrf
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px;margin-bottom:16px;">
                    @foreach ($entities as $entity)
                        <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid #dce4f0;border-radius:8px;font-size:.85rem;cursor:pointer;">
                            <input type="checkbox" name="entities[]" value="{{ $entity }}" checked style="width:auto;margin:0;">
                            <span style="text-transform:capitalize;font-weight:600;">{{ $entity }}</span>
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="btn-add" style="background:linear-gradient(135deg,#0ea5e9,#2563eb);" @disabled(! $connected)>
                    <i class="fa-solid fa-rotate"></i> Sync from AA now
                </button>
                @unless ($connected)
                    <span style="margin-left:10px;font-size:.8rem;color:#991b1b;">Kailangan nakakonekta bago mag-sync.</span>
                @endunless
            </form>
        </div>
    </div>

    {{-- ═══ LAST SYNC RESULT ═══ --}}
    @if ($lastSync)
        <div class="crud-card" style="margin-bottom:18px;">
            <div class="crud-header">
                <h3>Last sync</h3>
                <span style="font-size:.8rem;color:#64748b;">
                    {{ $lastSync->started_at?->diffForHumans() }} · by {{ $lastSync->performedBy?->name ?? 'system' }}
                </span>
            </div>
            <div style="padding:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;">
                @foreach ([
                    ['Processed', $lastSync->records_processed, '#4d8ff0'],
                    ['Created', $lastSync->records_created, '#16a34a'],
                    ['Updated', $lastSync->records_updated, '#0ea5e9'],
                    ['Skipped', $lastSync->records_skipped, '#f59e0b'],
                    ['Failed', $lastSync->records_failed, '#dc2626'],
                ] as $tile)
                    <div style="padding:12px;border:1px solid #e2e8f0;border-radius:8px;">
                        <div style="font-size:1.3rem;font-weight:800;color:{{ $tile[2] }};">{{ $tile[1] }}</div>
                        <div style="font-size:.74rem;color:#64748b;">{{ $tile[0] }}</div>
                    </div>
                @endforeach
            </div>

            @if (! empty($lastSync->summary))
                <div style="padding:0 16px 16px;">
                    <table class="crud-table">
                        <thead>
                            <tr>
                                <th>Entity</th>
                                <th style="text-align:center;">Processed</th>
                                <th style="text-align:center;">Created</th>
                                <th style="text-align:center;">Updated</th>
                                <th style="text-align:center;">Skipped</th>
                                <th style="text-align:center;">Failed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lastSync->summary as $entity => $counts)
                                <tr>
                                    <td style="text-transform:capitalize;font-weight:600;">{{ $entity }}</td>
                                    <td style="text-align:center;">{{ $counts['processed'] ?? 0 }}</td>
                                    <td style="text-align:center;">{{ $counts['created'] ?? 0 }}</td>
                                    <td style="text-align:center;">{{ $counts['updated'] ?? 0 }}</td>
                                    <td style="text-align:center;">{{ $counts['skipped'] ?? 0 }}</td>
                                    <td style="text-align:center;">{{ $counts['failed'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @foreach ($lastSync->summary as $entity => $counts)
                        @if (! empty($counts['errors']) || ! empty($counts['skips']))
                            <details style="margin-top:10px;font-size:.8rem;">
                                <summary style="cursor:pointer;font-weight:600;color:#475569;">
                                    {{ ucfirst($entity) }} — skip/fail details
                                </summary>
                                <ul style="margin:8px 0 0;padding-left:20px;color:#92400e;">
                                    @foreach ($counts['errors'] ?? [] as $error)
                                        <li>Error: {{ $error }}</li>
                                    @endforeach
                                    @foreach ($counts['skips'] ?? [] as $key => $reason)
                                        <li>{{ $key }}: {{ $reason }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ═══ SYNC HISTORY ═══ --}}
    <div class="crud-card" style="margin-bottom:18px;">
        <div class="crud-header">
            <h3>Sync history</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>By</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:center;">Created</th>
                    <th style="text-align:center;">Updated</th>
                    <th style="text-align:center;">Skipped</th>
                    <th style="text-align:center;">Failed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->started_at?->format('M j, Y g:i A') ?? '—' }}</td>
                        <td>{{ $log->performedBy?->name ?? 'system' }}</td>
                        <td style="text-align:center;">
                            <span style="padding:3px 10px;border-radius:999px;font-size:.72rem;font-weight:700;
                                {{ $log->status === 'success' ? 'background:#dcfce7;color:#166534;' : ($log->status === 'failed' ? 'background:#fee2e2;color:#991b1b;' : 'background:#e0e7ff;color:#3730a3;') }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td style="text-align:center;">{{ $log->records_created }}</td>
                        <td style="text-align:center;">{{ $log->records_updated }}</td>
                        <td style="text-align:center;">{{ $log->records_skipped }}</td>
                        <td style="text-align:center;">{{ $log->records_failed }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;padding:20px;color:#64748b;">Wala pang sync.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ═══ MAPPING PREVIEW ═══ --}}
    <div class="crud-card">
        <div class="crud-header">
            <h3>Table mapping (config/aa_sync.php)</h3>
        </div>
        <div style="padding:14px 16px;font-size:.8rem;color:#64748b;">
            Kung magkaiba ang schema ng AA, i-update ang mapping sa <code>config/aa_sync.php</code> bago mag-sync.
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Entity</th>
                    <th>AA table</th>
                </tr>
            </thead>
            <tbody>
                @foreach (config('aa_sync.tables', []) as $entity => $map)
                    <tr>
                        <td style="text-transform:capitalize;font-weight:600;">{{ $entity }}</td>
                        <td><code>{{ $map['table'] ?? '—' }}</code></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
