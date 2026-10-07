{{--
    §8/§9 — "Add Questions from Test Bank" + "Random Draw with Quotas".

    Manual mode: tick questions one by one — linked, never copied.
    Random mode: say how many questions you want, plus optional per-category
    and per-difficulty quotas. The draw is resolved server-side with
    QuestionQuotaPicker so a quota that cannot be met never leaves a
    half-built quiz behind.

    Expects $testBankQuestions (already scoped to own + shared banks, minus
    what this quiz already uses) from Instructor\QuizController.
--}}
<div class="cc-section">
    <div class="cc-section-title"><i class="fa-solid fa-database"></i> From the Test Bank</div>

    <div class="cc-hint" style="margin-bottom: 12px;">
        Tick questions you already wrote, or that an admin shared with you. They are
        <strong>linked</strong> to this quiz rather than copied, so the original stays the single source
        and attempts already recorded keep pointing at it.
    </div>

    @if(($testBankQuestions ?? collect())->isEmpty())
        <div class="cc-hint">
            Nothing reusable yet — questions you type in the section above are saved to your bank
            automatically, and they will show up here next time.
        </div>
    @else
        <div class="cc-field full" style="margin-bottom: 10px;">
            <label class="radio-inline" style="display:inline-flex;align-items:center;gap:6px;margin-right:18px;">
                <input type="radio" name="selection_mode" value="manual" checked
                       style="accent-color:#8b5cf6;cursor:pointer;">
                <span style="font-size:.88rem;">Manual — pick questions yourself</span>
            </label>
            <label class="radio-inline" style="display:inline-flex;align-items:center;gap:6px;">
                <input type="radio" name="selection_mode" value="random"
                       style="accent-color:#8b5cf6;cursor:pointer;">
                <span style="font-size:.88rem;">Random draw — let the system pick</span>
            </label>
        </div>

        {{-- Manual mode — the tick list — visible by default --}}
        <div id="manualPicker" style="display:block;">
            <div class="cc-field full" style="margin-bottom: 10px;">
                <input type="text" id="tbSearch" class="form-input" placeholder="Search by question, bank or category…">
            </div>

            <div id="tbList"
                 style="max-height: 340px; overflow-y: auto; border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 12px; padding: 8px;">
                @foreach($testBankQuestions as $tbq)
                    <label class="tb-row"
                           data-search="{{ strtolower(trim($tbq->question_text.' '.($tbq->bank?->title ?? '').' '.($tbq->category?->name ?? ''))) }}"
                           style="display: flex; align-items: center; gap: 12px; padding: 10px 8px; border-radius: 9px; cursor: pointer;">
                        <input type="checkbox"
                               name="test_bank_question_ids[]"
                               value="{{ $tbq->id }}"
                               style="accent-color: #8b5cf6; cursor: pointer; flex-shrink: 0;">

                        <span style="flex: 1; min-width: 0;">
                            <span style="display: block; font-size: 0.86rem; line-height: 1.35;">{{ $tbq->question_text }}</span>
                            <span style="display: block; font-size: 0.72rem; color: var(--bcp-muted, #98a7c4); margin-top: 3px;">
                                {{ $tbq->bank?->title ?? 'Unfiled' }}
                                · {{ $tbq->typeLabel() }}
                                · {{ $tbq->difficultyLabel() }}
                                @if($tbq->category) · {{ $tbq->category->name }} @endif
                                @if($tbq->bank?->is_shared) · Shared with you @endif
                            </span>
                        </span>

                        <span style="display: flex; align-items: center; gap: 5px; flex-shrink: 0;">
                            <input type="number"
                                   name="test_bank_points[{{ $tbq->id }}]"
                                   value="{{ (int) old('test_bank_points.'.$tbq->id, ($tbq->default_points ?: 1)) }}"
                                   min="1" max="1000" title="Points awarded for this question"
                                   style="width: 68px; min-height: 32px; padding: 5px 7px; border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 7px; background: #101625; color: var(--bcp-ink, #eef4ff); font-size: 0.8rem;">
                            <span style="font-size: 0.7rem; color: var(--bcp-muted, #98a7c4);">pts</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div id="tbEmpty"
                 style="display: none; padding: 14px; font-size: 0.82rem; color: var(--bcp-muted, #98a7c4);">
                No question matches that search.
            </div>
        </div>

        {{-- Random draw mode — quotas --}}
        <div id="randomPicker" style="display:none;">
            <div class="cc-field full" style="margin-bottom: 10px;">
                <label>Total questions to draw <span class="req">*</span></label>
                <input type="number" name="draw_total" class="form-input" min="1" max="200"
                       value="{{ old('draw_total') }}"
                       placeholder="e.g. 20">
                <span class="cc-hint">The set will contain exactly this many questions.</span>
            </div>

            <div class="cc-field full" style="margin-bottom: 10px;">
                <label>Per-category quotas (optional)</label>
                <div class="cc-hint" style="margin-bottom: 8px;">
                    How many questions from each category. Categories not listed get no quota —
                    they may still contribute via the difficulty quotas or the free remainder.
                </div>
                <div id="categoryQuotaRows">
                    @foreach($testBankQuestions->groupBy('category_id') as $catId => $group)
                        @php
                            $cat = $group->first()?->category;
                            $catName = $cat ? $cat->name : 'Uncategorised';
                            $count = $group->count();
                        @endphp
                        <div class="quota-row" style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
                            <input type="hidden" name="draw_category[{{ $catId }}]" value="0">
                            <label style="display:flex;align-items:center;gap:6px;min-width:160px;">
                                <input type="checkbox" class="quota-enable" data-target="draw_category[{{ $catId }}]"
                                       style="accent-color:#8b5cf6;cursor:pointer;">
                                <span style="font-size:.82rem;">{{ $catName }} ({{ $count }} available)</span>
                            </label>
                            <input type="number" name="draw_category[{{ $catId }}]"
                                   value="{{ old('draw_category.'.$catId) }}"
                                   min="0" max="{{ $count }}" placeholder="0"
                                   style="width:68px;min-height:32px;padding:5px 7px;border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:7px;background:#101625;color:var(--bcp-ink,#eef4ff);font-size:.8rem;"
                                   disabled>
                            <span style="font-size:.7rem;color:var(--bcp-muted,#98a7c4);">q</span>
                        </div>
                    @endforeach
                </div>
                <div class="cc-hint" style="margin-top:6px;">Uncheck a row to give it no quota.</div>
            </div>

            <div class="cc-field full">
                <label>Per-difficulty quotas (optional)</label>
                <div class="cc-hint" style="margin-bottom: 8px;">
                    How many questions of each difficulty. Difficulties not listed get no quota —
                    they may still contribute via the category quotas or the free remainder.
                </div>
                <div style="display:flex;gap:18px;flex-wrap:wrap;">
                    @foreach(['easy','medium','hard'] as $diff)
                        @php
                            $count = $testBankQuestions->where('difficulty', $diff)->count();
                        @endphp
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input type="hidden" name="draw_difficulty[{{ $diff }}]" value="0">
                            <label style="display:flex;align-items:center;gap:6px;">
                                <input type="checkbox" class="quota-enable" data-target="draw_difficulty[{{ $diff }}]"
                                       style="accent-color:#8b5cf6;cursor:pointer;">
                                <span style="font-size:.82rem;text-transform:capitalize;">{{ $diff }} ({{ $count }} available)</span>
                            </label>
                            <input type="number" name="draw_difficulty[{{ $diff }}]"
                                   value="{{ old('draw_difficulty.'.$diff) }}"
                                   min="0" max="{{ $count }}" placeholder="0"
                                   style="width:68px;min-height:32px;padding:5px 7px;border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:7px;background:#101625;color:var(--bcp-ink,#eef4ff);font-size:.8rem;"
                                   disabled>
                            <span style="font-size:.7rem;color:var(--bcp-muted,#98a7c4);">q</span>
                        </div>
                    @endforeach
                </div>
                <div class="cc-hint" style="margin-top:6px;">Uncheck a row to give it no quota.</div>
            </div>

            <div class="cc-field full" style="margin-top:10px;">
                <label>Points per drawn question <span class="req">*</span></label>
                <div class="cc-hint" style="margin-bottom: 8px;">
                    If left blank, each question's default points will be used.
                </div>
                <input type="number" name="test_bank_draw" class="form-input" min="1" max="1000"
                       value="{{ old('test_bank_draw') }}"
                       placeholder="e.g. 1">
                <span class="cc-hint">Applied to every question in the drawn set.</span>
            </div>
        </div>

        <script>
            (function () {
                var search = document.getElementById('tbSearch');
                var list = document.getElementById('tbList');
                var empty = document.getElementById('tbEmpty');
                var manual = document.getElementById('manualPicker');
                var random = document.getElementById('randomPicker');

                var modeRadios = document.querySelectorAll('input[name="selection_mode"]');
                modeRadios.forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        if (manual) manual.style.display = this.value === 'manual' ? 'block' : 'none';
                        if (random) random.style.display = this.value === 'random' ? 'block' : 'none';
                    });
                });

                if (!search || !list) return;

                search.addEventListener('input', function () {
                    var needle = search.value.trim().toLowerCase();
                    var shown = 0;

                    list.querySelectorAll('.tb-row').forEach(function (row) {
                        var haystack = row.getAttribute('data-search') || '';
                        var hit = needle === '' || haystack.indexOf(needle) !== -1;

                        row.style.display = hit ? 'flex' : 'none';
                        if (hit) shown++;
                    });

                    if (empty) empty.style.display = shown === 0 ? 'block' : 'none';
                });

                // Enable/disable quota number inputs when their checkbox changes.
                document.querySelectorAll('.quota-enable').forEach(function (checkbox) {
                    var targetName = checkbox.getAttribute('data-target');
                    var input = document.querySelector('input[name="'+targetName+'"]');
                    if (!input) return;

                    function sync() {
                        input.disabled = !checkbox.checked;
                        if (!checkbox.checked) input.value = 0;
                    }
                    checkbox.addEventListener('change', sync);
                    sync();
                });
            })();
        </script>
    @endif
</div>