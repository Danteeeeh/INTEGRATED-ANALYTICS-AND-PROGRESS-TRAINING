@extends('layouts.instructor')

@section('title', 'Enroll Student')
@php
    $activeNav = 'enrollment';
    $pageTitle = 'Enroll Student';
    $pageIcon = '<i class="fa-solid fa-graduation-cap"></i>';
    $formAction = route('instructor.enrollments.store');
    $backUrl = route('instructor.enrollments.index');
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-graduation-cap"></i>
            Enroll Student
        </h2>
        <div class="page-actions">
            <a href="{{ $backUrl }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Enrollments
            </a>
        </div>
    </div>
@endsection

@section('content')
    <style>
        .cc-hero {
            position: relative; overflow: hidden;
            display: flex; align-items: center; gap: 16px;
            padding: 20px 26px; margin-bottom: 22px;
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 16px;
            background:
                radial-gradient(circle at 90% 10%, rgba(16,185,129,.28), transparent 42%),
                linear-gradient(135deg, rgba(5,150,105,.82), rgba(10,16,32,.96));
            box-shadow: var(--bcp-shadow, 0 16px 36px rgba(3,8,20,.3));
        }
        .cc-icon {
            width: 50px; height: 50px; flex: none;
            display: grid; place-items: center;
            background: linear-gradient(135deg, #34d399, #059669);
            color: #03130c; font-size: 21px; border-radius: 13px;
            box-shadow: 0 10px 22px rgba(16,185,129,.35);
        }
        .cc-body { min-width: 0; }
        .cc-kicker {
            display: block; color: #6ee7b7;
            font-size: .64rem; font-weight: 800; letter-spacing: .15em; text-transform: uppercase; margin-bottom: 4px;
        }
        .cc-body h3 { margin: 0; color: #fff; font-size: 1.05rem; font-weight: 800; }
        .cc-body p { margin: 4px 0 0; color: #c8d9f8; font-size: .78rem; }
        .cc-badge {
            margin-left: auto; flex: none;
            padding: 6px 13px; border-radius: 999px;
            background: rgba(16,185,129,.13); border: 1px solid rgba(16,185,129,.3);
            color: #6ee7b7; font-size: .72rem; font-weight: 750;
            display: inline-flex; align-items: center; gap: 6px;
        }

        .cc-card {
            max-width: 960px; margin: 0 auto;
            background: var(--bcp-card, var(--dash-surface, #151c2c));
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            border-radius: 16px; overflow: hidden;
            box-shadow: var(--bcp-shadow, 0 18px 40px rgba(3,8,20,.3));
        }
        .cc-card-head {
            display: flex; align-items: center; gap: 10px;
            padding: 16px 24px;
            border-bottom: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            background: linear-gradient(90deg, rgba(16,185,129,.1), transparent);
        }
        .cc-card-head h3 {
            margin: 0; color: var(--bcp-ink, #eef4ff);
            font-size: .85rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
            display: flex; align-items: center; gap: 9px;
        }
        .cc-card-head h3 i { color: #6ee7b7; font-size: .82rem; }

        .cc-section { padding: 22px 24px; }
        .cc-section + .cc-section { border-top: 1px solid rgba(153,174,214,.1); }
        .cc-section-title {
            display: flex; align-items: center; gap: 8px;
            margin: 0 0 16px; color: var(--bcp-ink, #eef4ff);
            font-size: .78rem; font-weight: 750; letter-spacing: .05em; text-transform: uppercase;
        }
        .cc-section-title i { color: #6ee7b7; }

        .cc-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .cc-field { display: flex; flex-direction: column; gap: 6px; }
        .cc-field.full { grid-column: 1 / -1; }
        .cc-field label { color: var(--bcp-muted, #98a7c4); font-size: .72rem; font-weight: 750; }
        .cc-field label .req { color: #fda4af; }
        .cc-field select, .cc-field input, .cc-field textarea {
            width: 100%; min-height: 42px; padding: 9px 12px;
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 9px;
            background: #101625; color: var(--bcp-ink, #eef4ff);
            font-size: .88rem; transition: border-color .18s, box-shadow .18s;
        }
        .cc-field select:focus, .cc-field input:focus, .cc-field textarea:focus {
            outline: 0; border-color: #6ee7b7; box-shadow: 0 0 0 3px rgba(16,185,129,.16);
        }
        .cc-field textarea { resize: vertical; min-height: 80px; }
        .cc-field select option { background: #151c2c; color: #eef4ff; }
        .cc-hint { color: var(--bcp-muted, #98a7c4); font-size: .68rem; margin-top: 4px; }
        .cc-char { display: block; text-align: right; color: var(--bcp-muted, #98a7c4); font-size: .66rem; font-weight: 600; margin-top: 4px; }

        /* Status picker */
        .status-picker { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .status-option { position: relative; }
        .status-option input { position: absolute; opacity: 0; pointer-events: none; }
        .status-card {
            display: flex; align-items: center; gap: 11px;
            padding: 12px 14px;
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            border-radius: 11px; cursor: pointer;
            background: var(--dash-surface, #101625);
            transition: border-color .16s, background .16s, transform .15s;
        }
        .status-card:hover { transform: translateY(-1px); border-color: rgba(153,174,214,.35); }
        .status-option input:checked + .status-card {
            border-color: rgba(16,185,129,.55);
            background: rgba(16,185,129,.1);
            box-shadow: 0 0 0 3px rgba(16,185,129,.12);
        }
        .status-option input:focus-visible + .status-card { outline: 2px solid rgba(16,185,129,.5); outline-offset: 2px; }
        .st-icon {
            width: 38px; height: 38px; flex: none; border-radius: 10px;
            display: grid; place-items: center; font-size: 15px;
        }
        .st-active    { background: rgba(52,211,153,.15); color: #6ee7b7; }
        .st-pending   { background: rgba(251,191,36,.15); color: #fcd34d; }
        .st-completed { background: rgba(98,201,245,.15); color: #7dd3fc; }
        .st-dropped   { background: rgba(244,63,94,.15); color: #fda4af; }
        .status-card-body { min-width: 0; }
        .status-card-title { display: block; color: var(--bcp-ink, #eef4ff); font-size: .82rem; font-weight: 750; }
        .status-card-sub { display: block; color: var(--bcp-muted, #98a7c4); font-size: .66rem; margin-top: 2px; }

        .cc-footer {
            display: flex; justify-content: flex-end; gap: 10px;
            padding: 18px 24px;
            border-top: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            background: rgba(16,185,129,.04);
        }
        .cc-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 24px; border-radius: 9px;
            font-size: .84rem; font-weight: 700; text-decoration: none; cursor: pointer;
            transition: transform .15s, box-shadow .15s, background .15s;
        }
        .cc-btn:hover { transform: translateY(-1px); }
        .cc-btn-cancel {
            color: var(--bcp-text, #eef4ff);
            background: var(--dash-surface-raised, #1b2437);
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18));
        }
        .cc-btn-cancel:hover { color: var(--bcp-cyan-400, #62c9f5); background: rgba(77,143,240,.15); }
        .cc-btn-save {
            color: #fff;
            background: linear-gradient(135deg, #10b981, #059669);
            border: 1px solid #059669;
            box-shadow: 0 8px 20px rgba(16,185,129,.3);
        }
        .cc-btn-save:hover { background: linear-gradient(135deg, #34d399, #10b981); }

        @media (max-width: 680px) {
            .cc-grid { grid-template-columns: 1fr; }
            .status-picker { grid-template-columns: 1fr; }
            .cc-hero { padding: 16px 18px; }
            .cc-badge { display: none; }
            .cc-footer { flex-direction: column-reverse; }
            .cc-btn { justify-content: center; }
        }
    </style>

    {{-- ═══ HERO ═══ --}}
    <section class="cc-hero">
        <div class="cc-icon"><i class="fa-solid fa-user-plus"></i></div>
        <div class="cc-body">
            <span class="cc-kicker">Enrollment management</span>
            <h3>Enroll a Student</h3>
            <p>Idagdag ang isang estudyante sa isa sa iyong mga klase</p>
        </div>
        <span class="cc-badge"><i class="fa-solid fa-school"></i> {{ $classes->count() }} classes · {{ $students->count() }} students</span>
    </section>

    {{-- ═══ FORM ═══ --}}
    <div class="cc-card">
        <div class="cc-card-head">
            <h3><i class="fa-solid fa-user-plus"></i> Enrollment Information</h3>
        </div>
        <form method="POST" action="{{ $formAction }}" id="createForm" data-dirty-warn="true">
            @csrf

            <div class="cc-section">
                <div class="cc-section-title"><i class="fa-solid fa-user"></i> Student Information</div>
                <div class="cc-grid">
                    <div class="cc-field full">
                        <label>Student <span class="req">*</span></label>
                        <select name="student_id" required>
                            <option value="">Select Student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>{{ $student->full_name }} ({{ $student->email }})</option>
                            @endforeach
                        </select>
                        <span class="cc-hint">Piliin ang estudyanteng ie-enroll</span>
                        @error('student_id')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="cc-section">
                <div class="cc-section-title"><i class="fa-solid fa-school"></i> Class Information</div>
                <div class="cc-grid">
                    <div class="cc-field full">
                        <label>Class <span class="req">*</span></label>
                        <select name="class_id" required>
                            <option value="">Select Your Class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>{{ $class->code }} — {{ $class->name }} ({{ $class->course->name }})</option>
                            @endforeach
                        </select>
                        <span class="cc-hint">Kung saang klase ilalagay ang estudyante</span>
                        @error('class_id')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="cc-section">
                <div class="cc-section-title"><i class="fa-solid fa-info-circle"></i> Enrollment Details</div>
                <div class="cc-grid">
                    <div class="cc-field full">
                        <label>Status <span class="req">*</span></label>
                        <div class="status-picker" role="radiogroup" aria-label="Enrollment status">
                            <label class="status-option">
                                <input type="radio" name="status" value="active" {{ old('status', 'active') === 'active' ? 'checked' : '' }}>
                                <span class="status-card">
                                    <span class="st-icon st-active"><i class="fa-solid fa-circle-check"></i></span>
                                    <span class="status-card-body">
                                        <span class="status-card-title">Active</span>
                                        <span class="status-card-sub">Agad na enrolled at pwedeng mag-aral</span>
                                    </span>
                                </span>
                            </label>
                            <label class="status-option">
                                <input type="radio" name="status" value="pending" {{ old('status') === 'pending' ? 'checked' : '' }}>
                                <span class="status-card">
                                    <span class="st-icon st-pending"><i class="fa-solid fa-hourglass-half"></i></span>
                                    <span class="status-card-body">
                                        <span class="status-card-title">Pending</span>
                                        <span class="status-card-sub">Naghihintay ng kumpirmasyon</span>
                                    </span>
                                </span>
                            </label>
                            <label class="status-option">
                                <input type="radio" name="status" value="completed" {{ old('status') === 'completed' ? 'checked' : '' }}>
                                <span class="status-card">
                                    <span class="st-icon st-completed"><i class="fa-solid fa-graduation-cap"></i></span>
                                    <span class="status-card-body">
                                        <span class="status-card-title">Completed</span>
                                        <span class="status-card-sub">Natapos na ang kurso</span>
                                    </span>
                                </span>
                            </label>
                            <label class="status-option">
                                <input type="radio" name="status" value="dropped" {{ old('status') === 'dropped' ? 'checked' : '' }}>
                                <span class="status-card">
                                    <span class="st-icon st-dropped"><i class="fa-solid fa-user-slash"></i></span>
                                    <span class="status-card-body">
                                        <span class="status-card-title">Dropped</span>
                                        <span class="status-card-sub">Hindi na nagpatuloy sa kurso</span>
                                    </span>
                                </span>
                            </label>
                        </div>
                        @error('status')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="cc-field full">
                        <label>Notes</label>
                        <textarea name="notes" rows="3" placeholder="Optional notes tungkol sa enrollment..." data-char-count="notesCount">{{ old('notes') }}</textarea>
                        <span class="cc-char" id="notesCount">0 characters</span>
                        @error('notes')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="cc-footer">
                <a href="{{ $backUrl }}" class="cc-btn cc-btn-cancel">
                    <i class="fa-solid fa-xmark"></i>
                    Cancel
                </a>
                <button type="submit" class="cc-btn cc-btn-save">
                    <i class="fa-solid fa-user-plus"></i>
                    Enroll Student
                </button>
            </div>
        </form>
    </div>

    <script>
        // Char counter
        document.querySelectorAll('[data-char-count]').forEach(el => {
            const counter = document.getElementById(el.dataset.charCount);
            const update = () => {
                if (!counter) return;
                const max = el.maxLength;
                counter.textContent = max ? `${el.value.length.toLocaleString()} / ${max}` : el.value.length.toLocaleString() + ' characters';
            };
            el.addEventListener('input', update);
            update();
        });

        // Dirty form warning
        const form = document.getElementById('createForm');
        let dirty = false;
        form.addEventListener('input', () => { dirty = true; });
        form.addEventListener('change', () => { dirty = true; });
        form.addEventListener('submit', () => { dirty = false; });
        window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    </script>
@endsection
