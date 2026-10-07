@php
    /**
     * Printable academic record for the registrar's office.
     *
     * Deliberately standalone — no admin chrome, no sidebar — because this is
     * the sheet that gets printed, signed and filed.
     *
     * @var array<string, mixed> $record
     */
    $student = $record['student'];
    $printedAt = now()->format('F j, Y');
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Academic Record — {{ $student->full_name }}</title>
    <style>
        *,*::before,*::after{box-sizing:border-box}
        body{margin:0;padding:28px;background:#eef2f7;font-family:"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:#0f172a;font-size:13px;line-height:1.45}
        .sheet{max-width:960px;margin:0 auto;background:#fff;padding:36px 40px;box-shadow:0 2px 12px rgba(15,23,42,.08)}
        .toolbar{max-width:960px;margin:0 auto 14px;display:flex;gap:8px;justify-content:space-between;align-items:center;flex-wrap:wrap}
        .toolbar a,.toolbar button{display:inline-flex;align-items:center;gap:6px;padding:9px 15px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#334155;font-size:.82rem;font-weight:600;text-decoration:none;cursor:pointer}
        .toolbar .primary{background:#4f46e5;border-color:#4f46e5;color:#fff}
        .hint{font-size:.76rem;color:#64748b}

        .head{text-align:center;border-bottom:2px solid #0f172a;padding-bottom:14px;margin-bottom:20px}
        .head h1{margin:0 0 4px;font-size:1.3rem;font-weight:800;letter-spacing:.02em;text-transform:uppercase}
        .head p{margin:0;font-size:.8rem;color:#475569}

        .meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px 26px;margin-bottom:22px}
        .meta div{display:flex;gap:8px;font-size:.83rem;border-bottom:1px dotted #cbd5e1;padding-bottom:5px}
        .meta dt,.meta .k{font-weight:700;color:#334155;min-width:104px}
        .meta .v{color:#0f172a}

        table{width:100%;border-collapse:collapse;font-size:.8rem}
        thead th{background:#f1f5f9;padding:9px 8px;text-align:left;font-size:.68rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#334155;border:1px solid #cbd5e1}
        tbody td{padding:8px;border:1px solid #cbd5e1}
        tbody tr:nth-child(even){background:#f8fafc}
        .num{text-align:right;font-variant-numeric:tabular-nums}
        .mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.75rem}
        .tag{display:inline-block;padding:2px 8px;border-radius:999px;font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em}
        .tag.is-verified{background:#dcfce7;color:#15803d}
        .tag.is-draft{background:#fef3c7;color:#b45309}
        .tag.is-missing{background:#fee2e2;color:#b91c1c}

        .summary{display:flex;justify-content:flex-end;margin-top:18px}
        .summary table{width:auto;min-width:340px}
        .summary td{padding:7px 10px}
        .summary td.k{font-weight:700;background:#f8fafc}

        .notice{margin-top:18px;padding:11px 14px;border:1px solid #fde68a;background:#fffbeb;border-radius:8px;font-size:.79rem;color:#92400e}
        .notice.ok{border-color:#bbf7d0;background:#f0fdf4;color:#166534}

        .signatures{display:flex;justify-content:space-between;gap:40px;margin-top:52px}
        .signatures div{flex:1;text-align:center;font-size:.78rem;color:#475569}
        .signatures .line{margin-bottom:6px;border-bottom:1px solid #0f172a;height:34px}
        .signatures .who{font-weight:700;color:#0f172a}

        @media print{
            body{background:#fff;padding:0}
            .sheet{box-shadow:none;padding:0;max-width:none}
            .toolbar{display:none}
            .signatures{page-break-inside:avoid}
            thead{display:table-header-group}
            tr{page-break-inside:avoid}
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div style="display:flex;gap:8px;">
            <a href="{{ route('admin.academic-records.index', array_filter(['period_id' => $periodId])) }}">
                &larr; Back to list
            </a>
            <a href="{{ route('admin.academic-records.export', ['student' => $student, 'period_id' => $periodId]) }}" class="primary">
                Download CSV
            </a>
        </div>
        <span class="hint">Generated {{ $printedAt }}</span>
    </div>

    <div class="sheet">
        <header class="head">
            <h1>Academic Record</h1>
            <p>Official transcript for the registrar's office &middot; generated {{ $printedAt }}</p>
        </header>

        <dl class="meta">
            <div><span class="k">Student No</span><span class="v mono">{{ $record['identifier'] ?? '—' }}</span></div>
            <div><span class="k">Name</span><span class="v">{{ $student->full_name }}</span></div>
            <div><span class="k">Program</span><span class="v">{{ $record['program'] ?? '—' }}</span></div>
            <div><span class="k">Department</span><span class="v">{{ $record['department'] ?? '—' }}</span></div>
            <div><span class="k">Section</span><span class="v">{{ $record['section'] ?? '—' }}</span></div>
            <div>
                <span class="k">Period</span>
                <span class="v">
                    {{ $periodId ? ($periods->firstWhere('id', $periodId)?->name ?? '—') : 'All periods' }}
                </span>
            </div>
        </dl>

        <table>
            <thead>
                <tr>
                    <th style="width:34px">#</th>
                    <th>Subject Code</th>
                    <th>Shortname</th>
                    <th>Subject Name</th>
                    <th>Section</th>
                    <th>Instructor</th>
                    <th class="num">Units</th>
                    <th class="num">Final Grade</th>
                    <th>Letter</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($record['lines'] as $index => $line)
                    <tr>
                        <td class="num">{{ $index + 1 }}</td>
                        <td class="mono">{{ $line['code'] ?? '—' }}</td>
                        <td class="mono">{{ $line['shortname'] ?? '—' }}</td>
                        <td>{{ $line['name'] ?? '—' }}</td>
                        <td>{{ $line['section'] ?? '—' }}</td>
                        <td>{{ $line['instructor'] ?? '—' }}</td>
                        <td class="num">{{ $line['units'] > 0 ? rtrim(rtrim(number_format($line['units'], 2), '0'), '.') : '—' }}</td>
                        <td class="num">{{ $line['is_graded'] ? number_format((float) $line['final_grade'], 2) : '—' }}</td>
                        <td>{{ $line['is_graded'] ? $line['letter_grade'] : '—' }}</td>
                        <td>
                            @if (! $line['is_graded'])
                                <span class="tag is-missing">Pending</span>
                            @elseif ($line['is_verified'])
                                <span class="tag is-verified">Verified</span>
                            @else
                                <span class="tag is-draft">Unverified</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center;color:#64748b;padding:28px;">
                            No graded subjects on record for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="summary">
            <table>
                <tr>
                    <td class="k">Subjects graded</td>
                    <td class="num">{{ $record['graded_count'] }} of {{ $record['total_count'] }}</td>
                </tr>
                <tr>
                    <td class="k">Units earned</td>
                    <td class="num">{{ rtrim(rtrim(number_format((float) $record['units_earned'], 2), '0'), '.') ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="k">General Weighted Average</td>
                    <td class="num"><strong>{{ $record['gwa'] !== null ? number_format((float) $record['gwa'], 2) : '—' }}</strong></td>
                </tr>
                <tr>
                    <td class="k">Remark</td>
                    <td class="num">{{ $record['overall_remark'] }}</td>
                </tr>
            </table>
        </div>

        @if ($record['unverified_count'] > 0)
            <p class="notice">
                <strong>{{ $record['unverified_count'] }}</strong> of {{ $record['total_count'] }} subjects on this
                record have not been verified yet. Only verified subjects are treated as final.
            </p>
        @elseif ($record['total_count'] > 0)
            <p class="notice ok">Every subject on this record has been verified.</p>
        @endif

        <div class="signatures">
            <div>
                <div class="line"></div>
                <div class="who">Prepared by</div>
                LMS Academic Records
            </div>
            <div>
                <div class="line"></div>
                <div class="who">Noted by</div>
                College Registrar
            </div>
            <div>
                <div class="line"></div>
                <div class="who">Date</div>
            </div>
        </div>
    </div>
</body>
</html>