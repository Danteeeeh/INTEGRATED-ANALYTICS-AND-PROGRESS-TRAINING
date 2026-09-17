@extends('layouts.instructor')

@section('title', 'Create Discussion')
@php
    $activeNav = 'courses';
    $pageTitle = 'Create Discussion';
    $pageIcon = '<i class="fa-solid fa-plus"></i>';
    $formAction = route('instructor.courses.discussions.store', $course);
    $backUrl = route('instructor.courses.discussions.index', $course);
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-plus"></i>
            Create Discussion
        </h2>
        <div class="page-actions">
            <a href="{{ $backUrl }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Discussions
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
                radial-gradient(circle at 90% 10%, rgba(34,211,238,.28), transparent 42%),
                linear-gradient(135deg, rgba(8,145,178,.85), rgba(10,16,32,.96));
            box-shadow: var(--bcp-shadow, 0 16px 36px rgba(3,8,20,.3));
        }
        .cc-icon {
            width: 50px; height: 50px; flex: none;
            display: grid; place-items: center;
            background: linear-gradient(135deg, #22d3ee, #0e7490);
            color: #fff; font-size: 21px; border-radius: 13px;
            box-shadow: 0 10px 22px rgba(34,211,238,.32);
        }
        .cc-body { min-width: 0; }
        .cc-kicker {
            display: block; color: #67e8f9;
            font-size: .64rem; font-weight: 800; letter-spacing: .15em; text-transform: uppercase; margin-bottom: 4px;
        }
        .cc-body h3 { margin: 0; color: #fff; font-size: 1.05rem; font-weight: 800; }
        .cc-body p { margin: 4px 0 0; color: #c8d9f8; font-size: .78rem; }
        .cc-badge {
            margin-left: auto; flex: none;
            padding: 6px 13px; border-radius: 999px;
            background: rgba(34,211,238,.12); border: 1px solid rgba(34,211,238,.3);
            color: #67e8f9; font-size: .72rem; font-weight: 750;
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
            background: linear-gradient(90deg, rgba(34,211,238,.1), transparent);
        }
        .cc-card-head h3 {
            margin: 0; color: var(--bcp-ink, #eef4ff);
            font-size: .85rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
            display: flex; align-items: center; gap: 9px;
        }
        .cc-card-head h3 i { color: #67e8f9; font-size: .82rem; }

        .cc-section { padding: 22px 24px; }
        .cc-section + .cc-section { border-top: 1px solid rgba(153,174,214,.1); }
        .cc-section-title {
            display: flex; align-items: center; gap: 8px;
            margin: 0 0 16px; color: var(--bcp-ink, #eef4ff);
            font-size: .78rem; font-weight: 750; letter-spacing: .05em; text-transform: uppercase;
        }
        .cc-section-title i { color: #67e8f9; }

        .cc-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .cc-field { display: flex; flex-direction: column; gap: 6px; }
        .cc-field.full { grid-column: 1 / -1; }
        .cc-field label { color: var(--bcp-muted, #98a7c4); font-size: .72rem; font-weight: 750; }
        .cc-field label .req { color: #fda4af; }
        .cc-field input, .cc-field select, .cc-field textarea {
            width: 100%; min-height: 40px; padding: 9px 12px;
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 9px;
            background: #101625; color: var(--bcp-ink, #eef4ff);
            font-size: .88rem; transition: border-color .18s, box-shadow .18s;
        }
        .cc-field input:focus, .cc-field select:focus, .cc-field textarea:focus {
            outline: 0; border-color: #67e8f9; box-shadow: 0 0 0 3px rgba(34,211,238,.16);
        }
        .cc-field textarea { resize: vertical; min-height: 86px; }
        .cc-field input::placeholder, .cc-field textarea::placeholder { color: #7f91b0; }
        .cc-hint { color: var(--bcp-muted, #98a7c4); font-size: .68rem; margin-top: 4px; }
        .cc-char { display: block; text-align: right; color: var(--bcp-muted, #98a7c4); font-size: .66rem; font-weight: 600; margin-top: 4px; }

        .cc-footer {
            display: flex; justify-content: flex-end; gap: 10px;
            padding: 18px 24px;
            border-top: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            background: rgba(77,143,240,.04);
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
            background: linear-gradient(135deg, #22d3ee, #0e7490);
            border: 1px solid #06b6d4;
            box-shadow: 0 8px 20px rgba(34,211,238,.28);
        }
        .cc-btn-save:hover { background: linear-gradient(135deg, #67e8f9, #22d3ee); }

        @media (max-width: 680px) {
            .cc-grid { grid-template-columns: 1fr; }
            .cc-hero { padding: 16px 18px; }
            .cc-badge { display: none; }
            .cc-footer { flex-direction: column-reverse; }
            .cc-btn { justify-content: center; }
        }
    </style>

    {{-- ═══ HERO ═══ --}}
    <section class="cc-hero">
        <div class="cc-icon"><i class="fa-solid fa-comments"></i></div>
        <div class="cc-body">
            <span class="cc-kicker">Course engagement</span>
            <h3>New Discussion — {{ $course->name ?? $course->title }}</h3>
            <p>{{ $course->code }} · Start a conversation with your students</p>
        </div>
        <span class="cc-badge"><i class="fa-solid fa-school"></i> {{ $classes->count() }} classes</span>
    </section>

    {{-- ═══ FORM ═══ --}}
    <div class="cc-card">
        <div class="cc-card-head">
            <h3><i class="fa-solid fa-comments"></i> Discussion Details</h3>
        </div>
        <form method="POST" action="{{ $formAction }}" id="createForm" data-dirty-warn="true">
            @csrf
            <div class="cc-section">
                <div class="cc-grid">
                    <div class="cc-field full">
                        <label>Discussion Title <span class="req">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Week 1: Introduce Yourself" maxlength="255" data-char-count="titleCount">
                        <span class="cc-char" id="titleCount">0 / 255</span>
                        @error('title')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="cc-field">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">Course-wide</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>{{ $class->code }}</option>
                            @endforeach
                        </select>
                        <span class="cc-hint">Course-wide = lahat ng classes</span>
                        @error('class_id')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="cc-field">
                        <label>Discussion Type</label>
                        <select name="type">
                            @foreach($discussionTypes as $key => $label)
                                <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="cc-section">
                <div class="cc-section-title"><i class="fa-solid fa-align-left"></i> Discussion Prompt</div>
                <div class="cc-grid">
                    <div class="cc-field full">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Discussion prompt para sa mga estudyante..." data-char-count="descCount">{{ old('description') }}</textarea>
                        <span class="cc-char" id="descCount">0 characters</span>
                        @error('description')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="cc-footer">
                <a href="{{ $backUrl }}" class="cc-btn cc-btn-cancel">
                    <i class="fa-solid fa-xmark"></i>
                    Cancel
                </a>
                <button type="submit" class="cc-btn cc-btn-save">
                    <i class="fa-solid fa-save"></i>
                    Create Discussion
                </button>
            </div>
        </form>
    </div>

    <script>
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
        const form = document.getElementById('createForm');
        let dirty = false;
        form.addEventListener('input', () => { dirty = true; });
        form.addEventListener('change', () => { dirty = true; });
        form.addEventListener('submit', () => { dirty = false; });
        window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    </script>
@endsection
