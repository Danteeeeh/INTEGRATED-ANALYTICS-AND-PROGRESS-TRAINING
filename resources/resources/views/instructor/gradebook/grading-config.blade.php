@extends('layouts.instructor')

@section('title', 'Grading Configuration')

@php
    $activeNav = 'gradebook';
    $pageTitle = 'Grading Configuration';
    $pageIcon = '<i class="fa-solid fa-sliders"></i>';
@endphp

@section('content')
    <div class="gc-page">
        <x-user-page-header
            title="Grading Configuration"
            subtitle="{{ $class->course?->title ?? $class->code }} — choose what is graded and how much each component is worth."
            icon="fa-sliders"
        >
            <x-slot name="actions">
                <a href="{{ route('instructor.classes.gradebook.index', $class) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back to Gradebook
                </a>
                @if ($configuration->exists)
                    <form method="POST" action="{{ route('instructor.classes.gradebook.grading-config.destroy', $class) }}"
                          onsubmit="return confirm('Remove the grading configuration and go back to per-item weights?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-secondary">
                            <i class="fa-solid fa-trash"></i> Remove Config
                        </button>
                    </form>
                @endif
            </x-slot>
        </x-user-page-header>

        @if ($configuration->exists && $configuration->isValid())
            <div class="gc-banner gc-banner-ok">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong>Active configuration.</strong>
                    Components total {{ number_format($configuration->totalWeight(), 0) }}&percnt; and the gradebook is
                    using these weights instead of the per-item factors.
                </div>
            </div>
        @elseif ($configuration->exists)
            <div class="gc-banner gc-banner-warn">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <strong>Saved but not in use.</strong>
                    {{ $configuration->validationMessage() }} Until it balances, grades are still computed from the
                    per-item factors so the gradebook stays accurate.
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('instructor.classes.gradebook.grading-config.update', $class) }}"
              id="gc-form" class="gc-card">
            @csrf

            <div class="gc-head">
                <h3><i class="fa-solid fa-list-check"></i> Graded components</h3>
                <p class="gc-hint">
                    Tick a component to include it, then give it a percentage. The total must equal
                    {{ number_format($requiredTotal, 0) }}%.
                </p>
            </div>

            <div class="gc-list">
                @foreach ($components as $type => $meta)
                    @php
                        $column = $meta['column'];
                        $value = (float) ($configuration->{$column} ?? 0);
                        $enabled = $value > 0;
                        $itemCount = (int) ($available[$type] ?? 0);
                        $mandatory = ! $meta['optional'];
                    @endphp

                    <div class="gc-row {{ $enabled ? 'is-on' : '' }}" data-component="{{ $type }}">
                        <label class="gc-check">
                            <input type="checkbox"
                                   class="gc-enabled"
                                   data-weight="weights[{{ $type }}]"
                                   {{ $enabled ? 'checked' : '' }}>
                            <span class="gc-check-box" aria-hidden="true"></span>
                        </label>

                        <div class="gc-name">
                            <strong>{{ $meta['label'] }}</strong>
                            <span class="gc-desc">{{ $meta['hint'] }}</span>

                            <span class="gc-badges">
                                @if ($mandatory)
                                    <span class="gc-badge gc-badge-core">Core</span>
                                @endif
                                @if ($meta['optional'])
                                    <span class="gc-badge gc-badge-optional">Optional</span>
                                @endif
                                <span class="gc-badge {{ $itemCount > 0 ? 'gc-badge-has' : 'gc-badge-none' }}">
                                    {{ $itemCount }} grade item{{ $itemCount === 1 ? '' : 's' }}
                                </span>
                            </span>
                        </div>

                        <div class="gc-weight">
                            <input type="number"
                                   name="weights[{{ $type }}]"
                                   class="gc-input"
                                   value="{{ $value > 0 ? rtrim(rtrim(number_format($value, 2), '0'), '.') : '' }}"
                                   min="0" max="100" step="0.01"
                                   placeholder="0"
                                   {{ $enabled ? '' : 'disabled' }}>
                            <span class="gc-suffix">%</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="gc-total" id="gc-total">
                <div class="gc-total-bar">
                    <div class="gc-total-fill" id="gc-total-fill" style="width:0%"></div>
                </div>

                <div class="gc-total-row">
                    <strong>Total weight</strong>
                    <span class="gc-total-value">
                        <span id="gc-total-value">0.00</span>% / {{ number_format($requiredTotal, 0) }}%
                        <span class="gc-total-state" id="gc-total-state"></span>
                    </span>
                </div>

                <p class="gc-total-hint" id="gc-total-hint"></p>
            </div>

            <div class="gc-actions">
                <a href="{{ route('instructor.classes.gradebook.index', $class) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="gc-submit">
                    <i class="fa-solid fa-save"></i> Save Configuration
                </button>
            </div>
        </form>

        <div class="gc-card gc-card-muted">
            <h3><i class="fa-solid fa-circle-info"></i> How the final grade is calculated</h3>
            <ol class="gc-steps">
                <li>You select the components you grade and give each a percentage weight.</li>
                <li>The LMS validates that the weights total exactly {{ number_format($requiredTotal, 0) }}%.</li>
                <li>Students complete their quizzes, assignments and exams.</li>
                <li>The system collects the scores for every released grade item.</li>
                <li>The grade calculation module turns those into a final grade and a letter grade.</li>
                <li>The result appears in the student grade report <em>and</em> the instructor gradebook, because both
                    read the same computation.</li>
            </ol>

            <p class="gc-note">
                <strong>Attendance is opt-in.</strong> It is never given a weight automatically — tick it and set a
                percentage if virtual class attendance should count toward the grade.
            </p>
        </div>
    </div>
@endsection

<style>
    .gc-page{display:flex;flex-direction:column;gap:18px}
    .gc-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px}
    .gc-card-muted{background:#f8fafc}
    .gc-head h3{margin:0 0 6px;font-size:1rem;font-weight:700;color:#0f172a}
    .gc-hint{margin:0 0 18px;font-size:.83rem;color:#64748b}
    .gc-list{display:flex;flex-direction:column;gap:10px}
    .gc-row{display:grid;grid-template-columns:auto 1fr auto;gap:16px;align-items:center;padding:14px 16px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;transition:border-color .15s,background .15s}
    .gc-row.is-on{border-color:#6366f1;background:#f5f5ff}
    .gc-check input{position:absolute;opacity:0;width:0;height:0}
    .gc-check-box{display:block;width:22px;height:22px;border:2px solid #cbd5e1;border-radius:6px;background:#fff;cursor:pointer;position:relative;transition:background .15s,border-color .15s}
    .gc-check input:checked + .gc-check-box{background:#4f46e5;border-color:#4f46e5}
    .gc-check input:checked + .gc-check-box::after{content:"";position:absolute;left:6px;top:2px;width:5px;height:10px;border:solid #fff;border-width:0 2px 2px 0;transform:rotate(45deg)}
    .gc-name strong{display:block;font-size:.9rem;color:#0f172a}
    .gc-desc{display:block;margin-top:2px;font-size:.77rem;color:#64748b}
    .gc-badges{display:flex;flex-wrap:wrap;gap:6px;margin-top:7px}
    .gc-badge{font-size:.64rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:2px 7px;border-radius:999px;background:#e2e8f0;color:#475569}
    .gc-badge-core{background:#dbeafe;color:#1d4ed8}
    .gc-badge-optional{background:#fef3c7;color:#b45309}
    .gc-badge-has{background:#dcfce7;color:#15803d}
    .gc-badge-none{background:#fee2e2;color:#b91c1c}
    .gc-weight{position:relative;display:flex;align-items:center}
    .gc-input{width:96px;padding:9px 30px 9px 11px;border:1px solid #d5deeb;border-radius:9px;font-size:.87rem;text-align:right;font-variant-numeric:tabular-nums}
    .gc-input:disabled{background:#f1f5f9;color:#94a3b8;cursor:not-allowed}
    .gc-suffix{position:absolute;right:11px;font-size:.8rem;color:#94a3b8;pointer-events:none}
    .gc-total{margin-top:20px;padding-top:18px;border-top:1px solid #e2e8f0}
    .gc-total-bar{height:8px;border-radius:999px;background:#e2e8f0;overflow:hidden}
    .gc-total-fill{height:100%;background:#4f46e5;transition:width .2s,background .2s}
    .gc-total-fill.is-short{background:#f59e0b}
    .gc-total-fill.is-over{background:#ef4444}
    .gc-total-fill.is-ok{background:#10b981}
    .gc-total-row{display:flex;align-items:center;justify-content:space-between;margin-top:10px;font-size:.86rem}
    .gc-total-value{font-variant-numeric:tabular-nums;color:#334155}
    .gc-total-state{font-weight:700;margin-left:6px}
    .gc-total-state.is-ok{color:#059669}
    .gc-total-state.is-bad{color:#dc2626}
    .gc-total-hint{margin:8px 0 0;font-size:.78rem;color:#b91c1c;min-height:1.1em}
    .gc-banner{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:12px;font-size:.84rem}
    .gc-banner i{margin-top:2px}
    .gc-banner-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
    .gc-banner-warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
    .gc-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
    .gc-steps{margin:14px 0 0;padding-left:20px;font-size:.83rem;color:#475569}
    .gc-steps li{margin-bottom:5px}
    .gc-note{margin:16px 0 0;padding:12px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;font-size:.81rem;color:#92400e}
</style>

@push('scripts')
<script>
(function () {
    const REQUIRED = {{ \App\Models\GradeConfiguration::REQUIRED_TOTAL }};

    const form = document.getElementById('gc-form');
    if (! form) return;

    const rows = Array.from(form.querySelectorAll('.gc-row'));
    const fill = document.getElementById('gc-total-fill');
    const totalValue = document.getElementById('gc-total-value');
    const totalState = document.getElementById('gc-total-state');
    const totalHint = document.getElementById('gc-total-hint');

    function rowFor(type) {
        return form.querySelector('.gc-row[data-component="' + type + '"]');
    }

    function recalc() {
        let total = 0;
        let enabled = 0;

        rows.forEach(function (row) {
            const toggle = row.querySelector('.gc-enabled');
            const input = row.querySelector('.gc-input');
            const on = toggle.checked;

            row.classList.toggle('is-on', on);
            input.disabled = ! on;

            if (! on) {
                input.value = '';
                return;
            }

            enabled++;
            const value = parseFloat(input.value);
            if (! isNaN(value)) total += value;
        });

        total = Math.round(total * 100) / 100;

        const pct = Math.min(total / REQUIRED, 1) * 100;
        fill.style.width = pct + '%';
        fill.className = 'gc-total-fill ' + (total > REQUIRED ? 'is-over' : (Math.abs(total - REQUIRED) < 0.001 ? 'is-ok' : 'is-short'));

        totalValue.textContent = total.toFixed(2);

        if (enabled === 0) {
            fill.className = 'gc-total-fill is-short';
            totalState.textContent = '— select at least one component';
            totalState.className = 'gc-total-state is-bad';
            totalHint.textContent = '';
            return;
        }

        if (Math.abs(total - REQUIRED) < 0.001) {
            totalState.textContent = 'Balanced';
            totalState.className = 'gc-total-state is-ok';
            totalHint.textContent = '';
            return;
        }

        const diff = Math.abs(REQUIRED - total);
        const word = total < REQUIRED ? 'add' : 'remove';
        totalState.textContent = 'Not balanced';
        totalState.className = 'gc-total-state is-bad';
        totalHint.textContent = 'Weights must total exactly ' + REQUIRED + '%. ' +
            (total < REQUIRED ? 'Add ' : 'Remove ') + diff.toFixed(2) + '%.';
    }

    form.addEventListener('input', recalc);
    form.addEventListener('change', recalc);

    recalc();
})();
</script>
@endpush