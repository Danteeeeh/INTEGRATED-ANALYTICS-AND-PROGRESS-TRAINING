@php
    $routePrefix = auth()->user()->isAdmin() ? 'admin.' : 'instructor.';
    $layout = auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.instructor';

    // Banks this viewer may actually add a question to. A shared bank owned by
    // somebody else stays read-only, so the picker cannot offer a place the
    // question would be rejected from.
    $writableBanks = $banks->filter(function ($bank) {
        return $bank->created_by === auth()->id()
            || (auth()->user()->isAdmin() && $bank->is_shared);
    })->values();

    // Types that render the choices/answer-key block.
    $libChoiceTypes = ['multiple_choice', 'multiple_answer', 'true_false'];
    $libSingleAnswerTypes = ['multiple_choice', 'true_false'];
    $libTextKeyTypes = ['identification', 'short_answer'];
    $libKeyTypes = array_merge($libChoiceTypes, $libTextKeyTypes);

    $libTypeLabels = [
        'multiple_choice' => 'Multiple Choice',
        'multiple_answer' => 'Multiple Answer',
        'true_false' => 'True / False',
        'identification' => 'Identification',
        'short_answer' => 'Short Answer',
        'essay' => 'Essay',
    ];
@endphp
@extends($layout)

@section('title', 'Test Bank ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â Questions')
@php $activeNav = 'question_banks'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Test Bank"
        subtitle="Every question you may use ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â your own banks plus anything shared with you."
        icon="fa-list-check"
    >
        <x-slot name="actions">
            <button type="button" class="btn btn-primary" data-open-modal="add-question-modal"><i class="fa-solid fa-plus"></i> Add Question</button>
            <a href="{{ route($routePrefix.'question_banks.create') }}" class="btn btn-secondary"><i class="fa-solid fa-layer-group"></i> New Bank</a>
        </x-slot>
    </x-user-page-header>

    @include('instructor.question-banks._tabs', ['activeTab' => 'questions'])

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route($routePrefix.'test_bank.index') }}">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search question text..." aria-label="Search questions">

            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->code }} ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â {{ $course->title }}</option>
                @endforeach
            </select>

            <select class="form-control" name="bank_id" aria-label="Filter bank">
                <option value="">All Banks</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank->id }}" @selected(request('bank_id') == $bank->id)>{{ $bank->title }}</option>
                @endforeach
            </select>

            <select class="form-control" name="category_id" aria-label="Filter category">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>

            <select class="form-control" name="question_type" aria-label="Filter type">
                <option value="">All Types</option>
                @foreach([
                    'multiple_choice' => 'Multiple Choice',
                    'multiple_answer' => 'Multiple Answer',
                    'true_false' => 'True / False',
                    'identification' => 'Identification',
                    'short_answer' => 'Short Answer',
                    'essay' => 'Essay',
                ] as $value => $label)
                    <option value="{{ $value }}" @selected(request('question_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select class="form-control" name="difficulty" aria-label="Filter difficulty">
                <option value="">All Difficulties</option>
                @foreach(['easy' => 'Easy', 'medium' => 'Moderate', 'hard' => 'Difficult'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('difficulty') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">Live Statuses</option>
                @foreach(['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Inactive'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select class="form-control" name="scope" aria-label="Filter ownership">
                <option value="">Owned by Anyone</option>
                <option value="mine" @selected(request('scope') === 'mine')>Mine Only</option>
                <option value="shared" @selected(request('scope') === 'shared')>Shared With Me</option>
            </select>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
            @if(request()->hasAny(['q', 'course_id', 'bank_id', 'category_id', 'question_type', 'difficulty', 'status', 'scope']))
                <a href="{{ route($routePrefix.'test_bank.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>

        @if(session('success'))
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        @if($questions->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-list-check"></i>
                <p>No questions match these filters.</p>
                <a href="{{ route($routePrefix.'question_banks.index') }}" class="btn btn-primary"><i class="fa-solid fa-database"></i> Open a bank to add questions</a>
            </div>
        @else
            <div class="user-table-wrap">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Bank</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Difficulty</th>
                            <th>Status</th>
                            <th>Used In</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($questions as $question)
                            <tr>
                                <td>
                                    <a href="{{ route($routePrefix.'test_bank.questions.preview', $question) }}" class="user-cell-primary">
                                        {{ \Illuminate\Support\Str::limit($question->question_text, 90) }}
                                    </a>
                                    <div class="user-email">{{ $question->creator?->name ?? 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â' }}</div>
                                </td>
                                <td>
                                    {{ $question->bank?->title ?? 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â' }}
                                    @if($question->bank?->is_shared)
                                        <div class="user-email"><em>Shared</em></div>
                                    @elseif($question->bank?->created_by === auth()->id())
                                        <div class="user-email"><em>You</em></div>
                                    @endif
                                </td>
                                <td>{{ $question->category?->name ?? 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â' }}</td>
                                <td>{{ $question->typeLabel() }}</td>
                                <td>{{ $question->difficultyLabel() }}</td>
                                <td><x-user-status-badge :status="$question->status" :label="$question->statusLabel()" /></td>
                                <td>
                                    {{ $question->quizzes_count }} quiz / {{ $question->exams_count }} exam
                                </td>
                                <td>
                                    <div class="user-actions">
                                        <a href="{{ route($routePrefix.'test_bank.questions.preview', $question) }}" class="btn btn-icon" title="Preview"><i class="fa-solid fa-eye"></i></a>
                                        @if($question->bank && ($question->bank->created_by === auth()->id() || auth()->user()->isAdmin()))
                                            <a href="{{ route($routePrefix.'question_banks.edit', $question->bank) }}#question-{{ $question->id }}" class="btn btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="user-pagination">
                {{ $questions->links() }}
            </div>
        @endif
    </div>
    {{-- Add question modal. Creating a question no longer requires walking into a
         bank's long edit page; the same validation runs here so the answer key
         is captured and checked identically. --}}
    @if($writableBanks->isNotEmpty())
        <div class="lib-modal" id="add-question-modal" hidden>
            <div class="lib-modal__backdrop" data-close-modal></div>

            <div class="lib-modal__panel" role="dialog" aria-modal="true" aria-labelledby="add-question-title">
                <header class="lib-modal__head">
                    <div>
                        <h3 id="add-question-title"><i class="fa-solid fa-circle-plus"></i> Add Question</h3>
                        <p>Write the question and mark the answer key before saving.</p>
                    </div>
                    <button type="button" class="lib-modal__close" data-close-modal aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </header>

                <form action="{{ route($routePrefix.'test_bank.questions.store') }}" method="POST" class="lib-modal__body">
                    @csrf

                    @if($errors->any())
                        <div class="lib-errors">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="lib-grid">
                        <div class="lib-field lib-field--wide">
                            <label for="lib-bank">Save into bank <span class="lib-req">*</span></label>
                            <select id="lib-bank" name="question_bank_id" required>
                                <option value="">Choose a bank...</option>
                                @foreach($writableBanks as $bank)
                                    <option value="{{ $bank->id }}" @selected((int) old('question_bank_id') === $bank->id)>{{ $bank->title }}</option>
                                @endforeach
                            </select>
                            <small>Questions always belong to a bank. Pick the one this question is for.</small>
                        </div>

                        <div class="lib-field">
                            <label for="lib-type">Type <span class="lib-req">*</span></label>
                            <select id="lib-type" name="question_type" class="js-lib-type" required>
                                @foreach($libTypeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('question_type', 'multiple_choice') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lib-field">
                            <label for="lib-difficulty">Difficulty <span class="lib-req">*</span></label>
                            <select id="lib-difficulty" name="difficulty" required>
                                @foreach(['easy' => 'Easy', 'medium' => 'Moderate', 'hard' => 'Difficult'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('difficulty', 'medium') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lib-field">
                            <label for="lib-points">Points <span class="lib-req">*</span></label>
                            <input id="lib-points" type="number" name="default_points" step="0.5" min="0" value="{{ old('default_points', 1) }}" required>
                        </div>

                        <div class="lib-field">
                            <label for="lib-status">Status</label>
                            <select id="lib-status" name="status">
                                @foreach(['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Inactive'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lib-field">
                            <label for="lib-category">Category</label>
                            <select id="lib-category" name="category_id">
                                <option value="">Uncategorised</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('category_id') === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lib-field lib-field--wide">
                            <label for="lib-text">Question <span class="lib-req">*</span></label>
                            <textarea id="lib-text" name="question_text" rows="3" required placeholder="Type your question...">{{ old('question_text') }}</textarea>
                        </div>

                        <div class="lib-field lib-field--wide js-lib-key">
                            <label class="js-lib-key-label">Choices</label>
                            <div class="js-lib-choices">
                                @for($i = 0; $i < 4; $i++)
                                    <div class="js-lib-row">
                                        <span class="js-lib-index">{{ $i + 1 }}</span>
                                        <input type="text" name="new[{{ $i }}][text]" placeholder="{{ $i === 0 ? 'Choice text...' : 'Choice '.($i + 1) }}">
                                        <label class="js-lib-correct-wrap">
                                            <input type="checkbox" class="js-lib-correct" name="new[{{ $i }}][correct]" value="1">
                                            <span>Correct</span>
                                        </label>
                                        <button type="button" class="js-lib-remove" aria-label="Remove choice" title="Remove choice">
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                    </div>
                                @endfor

                                <button type="button" class="btn btn-secondary js-lib-add">
                                    <i class="fa-solid fa-plus"></i> Add Choice
                                </button>
                            </div>
                            <small class="js-lib-hint">Tick the row that is the correct answer.</small>
                        </div>

                        <div class="lib-field lib-field--wide js-lib-case">
                            <label class="lib-check">
                                <input type="checkbox" name="is_case_sensitive" value="1" @checked(old('is_case_sensitive'))>
                                <span>Case sensitive</span>
                            </label>
                            <small>Only for Identification and Short Answer. "CPU" and "cpu" count as different answers.</small>
                        </div>

                        <div class="lib-field lib-field--wide">
                            <label for="lib-explanation">Explanation</label>
                            <textarea id="lib-explanation" name="explanation" rows="2" placeholder="Optional. Shown after the student answers.">{{ old('explanation') }}</textarea>
                        </div>

                        <div class="lib-field lib-field--wide">
                            <label for="lib-tags">Tags</label>
                            <input id="lib-tags" type="text" name="tags" value="{{ old('tags') }}" placeholder="comma separated, e.g. chapter1, networking">
                        </div>
                    </div>

                    <footer class="lib-modal__foot">
                        <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Question</button>
                    </footer>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
@push('styles')
<style>
    /* Add-question modal. Deliberately self-contained so it renders identically
       on the instructor and admin Test Bank without depending on shared CSS. */
    .lib-modal{position:fixed;inset:0;z-index:1200;display:flex;align-items:center;justify-content:center;padding:20px}
    .lib-modal[hidden]{display:none}
    .lib-modal__backdrop{position:absolute;inset:0;background:rgba(8,14,28,.66)}
    .lib-modal__panel{position:relative;width:min(880px,100%);max-height:92vh;display:flex;flex-direction:column;
        border:1px solid var(--bcp-border,rgba(153,174,214,.22));border-radius:16px;background:var(--bcp-card,#141c2c);
        box-shadow:0 24px 60px rgba(3,8,20,.45);overflow:hidden}
    .lib-modal__head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;
        padding:18px 20px;border-bottom:1px solid var(--bcp-line,rgba(153,174,214,.16))}
    .lib-modal__head h3{margin:0;color:#eef4ff;font-size:1rem}
    .lib-modal__head h3 i{color:#67e8f9;margin-right:6px}
    .lib-modal__head p{margin:4px 0 0;color:#93a3c0;font-size:.76rem}
    .lib-modal__close{border:0;background:transparent;color:#93a3c0;font-size:1rem;cursor:pointer;padding:4px}
    .lib-modal__close:hover{color:#eef4ff}
    .lib-modal__body{padding:18px 20px;overflow-y:auto}
    .lib-modal__foot{display:flex;justify-content:flex-end;gap:10px;padding:14px 20px;
        border-top:1px solid var(--bcp-line,rgba(153,174,214,.16));background:rgba(10,16,32,.45)}

    .lib-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .lib-field{display:flex;flex-direction:column;gap:6px;min-width:0}
    .lib-field--wide{grid-column:1/-1}
    .lib-field>label{color:#cbd5e1;font-size:.76rem;font-weight:700}
    .lib-req{color:#fca5a5}
    .lib-field input[type=text],.lib-field input[type=number],.lib-field select,.lib-field textarea{
        width:100%;padding:10px 12px;border:1px solid var(--bcp-border,rgba(153,174,214,.24));border-radius:9px;
        background:rgba(8,14,28,.55);color:#eef4ff;font-size:.85rem}
    .lib-field input:focus,.lib-field select:focus,.lib-field textarea:focus{
        outline:none;border-color:#67e8f9;box-shadow:0 0 0 3px rgba(103,232,249,.14)}
    .lib-field small{color:#8494b0;font-size:.7rem;line-height:1.45}

    .js-lib-choices{display:flex;flex-direction:column;gap:8px}
    .js-lib-row{display:grid;grid-template-columns:auto 1fr auto auto;gap:10px;align-items:center;
        padding:8px 10px;border:1px solid var(--bcp-border,rgba(153,174,214,.18));border-radius:10px;
        background:rgba(8,14,28,.35)}
    .js-lib-row:focus-within{border-color:#67e8f9;box-shadow:0 0 0 3px rgba(103,232,249,.12)}
    .js-lib-index{display:grid;place-items:center;width:24px;height:24px;border-radius:7px;font-size:.7rem;
        font-weight:800;color:#67e8f9;background:rgba(103,232,249,.12)}
    .js-lib-row input[type=text]{background:transparent;border:0;padding:6px 4px;color:#eef4ff;font-size:.85rem}
    .js-lib-row input[type=text]:focus{outline:none;box-shadow:none}
    .js-lib-correct-wrap{display:flex;align-items:center;gap:6px;margin:0;white-space:nowrap;
        color:#cbd5e1;font-size:.78rem;cursor:pointer}
    .js-lib-remove{border:1px solid var(--bcp-border,rgba(153,174,214,.24));border-radius:8px;
        background:transparent;color:#93a3c0;cursor:pointer;padding:6px 9px;font-size:.72rem}
    .js-lib-remove:hover{color:#fca5a5;border-color:rgba(252,165,165,.5)}

    .lib-check{display:flex;align-items:center;gap:8px;color:#cbd5e1;font-size:.8rem;cursor:pointer;margin:0}

    .lib-errors{display:flex;gap:10px;padding:12px 14px;margin-bottom:14px;border-radius:10px;
        border:1px solid rgba(252,165,165,.4);background:rgba(252,165,165,.1);color:#fecaca;font-size:.8rem}
    .lib-errors ul{margin:0;padding-left:18px}

    @media(max-width:640px){
        .lib-grid{grid-template-columns:1fr}
        .js-lib-row{grid-template-columns:auto 1fr auto}
        .js-lib-remove{grid-column:1/-1;justify-self:start}
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('add-question-modal');
        if (!modal) return;

        var typeSelect = modal.querySelector('.js-lib-type');
        var keyBlock   = modal.querySelector('.js-lib-key');
        var keyLabel   = modal.querySelector('.js-lib-key-label');
        var keyHint    = modal.querySelector('.js-lib-hint');
        var choicesBox = modal.querySelector('.js-lib-choices');
        var caseBlock  = modal.querySelector('.js-lib-case');
        var addBtn     = modal.querySelector('.js-lib-add');

        var CHOICE_TYPES   = @json($libChoiceTypes);
        var SINGLE_ANSWER  = @json($libSingleAnswerTypes);
        var TEXT_KEY_TYPES = @json($libTextKeyTypes);
        var KEY_TYPES      = @json($libKeyTypes);

        var KEY_LABELS = { choice: 'Choices', answer: 'Accepted Answers' };
        var KEY_HINTS  = {
            choice: 'Tick the row that is the correct answer.',
            answer: 'One accepted answer per row. Tick each one you accept, or leave all unticked to grade by hand.',
            blank:  'Not used for this type. Essays are graded by hand.'
        };

        // The same block does double duty: options for choice types, the answer
        // key for free-text types, and it disappears for essays.
        function syncKeyVisibility() {
            if (!typeSelect || !keyBlock) return;

            var value     = typeSelect.value;
            var isChoice  = CHOICE_TYPES.indexOf(value) !== -1;
            var isTextKey = TEXT_KEY_TYPES.indexOf(value) !== -1;

            keyBlock.style.display = KEY_TYPES.indexOf(value) !== -1 ? '' : 'none';

            if (keyLabel) keyLabel.textContent = isTextKey ? KEY_LABELS.answer : KEY_LABELS.choice;
            if (keyHint) keyHint.textContent = (isChoice || isTextKey)
                ? (isTextKey ? KEY_HINTS.answer : KEY_HINTS.choice)
                : KEY_HINTS.blank;

            if (caseBlock) caseBlock.style.display = isTextKey ? '' : 'none';
        }

        // Row names must stay contiguous so the submitted array has no holes.
        function renumberRows() {
            var rows = choicesBox.querySelectorAll('.js-lib-row');

            rows.forEach(function (row, index) {
                row.querySelector('.js-lib-index').textContent = index + 1;
                row.querySelector('input[type="text"]').setAttribute('name', 'new[' + index + '][text]');
                row.querySelector('.js-lib-correct').setAttribute('name', 'new[' + index + '][correct]');
            });

            addBtn.style.display = rows.length >= 12 ? 'none' : '';
        }

        addBtn.addEventListener('click', function () {
            var clone = choicesBox.querySelector('.js-lib-row').cloneNode(true);

            clone.querySelector('input[type="text"]').value = '';
            clone.querySelector('.js-lib-correct').checked = false;

            choicesBox.insertBefore(clone, addBtn);
            renumberRows();
            clone.querySelector('input[type="text"]').focus();
        });

        choicesBox.addEventListener('click', function (event) {
            var remove = event.target.closest('.js-lib-remove');
            if (!remove) return;

            var rows = choicesBox.querySelectorAll('.js-lib-row');

            // Never drop below the two choices a choice-type question needs.
            if (rows.length <= 2) {
                rows[0].querySelector('input[type="text"]').value = '';
                rows[0].querySelector('.js-lib-correct').checked = false;
                return;
            }

            remove.closest('.js-lib-row').remove();
            renumberRows();
        });

        // Single-answer types behave like radios: ticking one clears the rest so
        // two conflicting "Correct" marks can never be submitted together.
        choicesBox.addEventListener('change', function (event) {
            var box = event.target.closest('.js-lib-correct');
            if (!box || !box.checked) return;
            if (SINGLE_ANSWER.indexOf(typeSelect.value) === -1) return;

            choicesBox.querySelectorAll('.js-lib-correct').forEach(function (other) {
                if (other !== box) other.checked = false;
            });
        });

        typeSelect.addEventListener('change', syncKeyVisibility);

        function open() {
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            var first = modal.querySelector('select[name="question_bank_id"]');
            if (first) first.focus();
        }

        function close() {
            modal.hidden = true;
            document.body.style.overflow = '';
        }

        document.querySelectorAll('[data-open-modal]').forEach(function (trigger) {
            trigger.addEventListener('click', open);
        });

        modal.querySelectorAll('[data-close-modal]').forEach(function (trigger) {
            trigger.addEventListener('click', close);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) close();
        });

        syncKeyVisibility();
        renumberRows();

        // Reopen after a validation failure so the typed answer key stays on
        // screen instead of silently disappearing.
        @if($errors->any())
            open();
        @endif
    })();
</script>
@endpush
