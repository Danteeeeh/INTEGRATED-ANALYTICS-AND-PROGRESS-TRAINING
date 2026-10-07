@extends('layouts.instructor')

@section('title', 'Gradebook')
@php
    $activeNav = 'gradebook';
    $pageTitle = 'Class Gradebook';
    $pageIcon = '<i class="fa-solid fa-chart-bar"></i>';
@endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $class->course->title }} — Gradebook"
        subtitle="Enter grades and review student performance."
        icon="fa-chart-bar"
    >
        <x-slot name="actions">
            <button type="button" onclick="exportGrades()" class="btn btn-secondary">
                <i class="fa-solid fa-download"></i> Export
            </button>
            <button type="button" onclick="showBulkGradeModal()" class="btn btn-primary">
                <i class="fa-solid fa-edit"></i> Bulk Grade
            </button>
        </x-slot>
    </x-user-page-header>

    <!-- Grade Overview Stats -->
    <div class="user-stat-grid">
        <x-user-stat-card label="Total Students" value="{{ $students->total() }}" icon="fa-users" />
        <x-user-stat-card label="Grade Items" value="{{ $gradeItems->count() }}" icon="fa-tasks" />
        <x-user-stat-card
            label="Class Average"
            value="{{ number_format($classAverage, 1) }}%"
            icon="fa-chart-line"
        />
    </div>

    <!-- Gradebook Table -->
    <div class="user-panel gradebook-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-table"></i> Student Grades</h3>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                @if (! empty($riskTally['at_risk']))
                    <span class="gb-chip gb-chip-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        {{ $riskTally['at_risk'] }} at risk
                    </span>
                @endif

                @if (! empty($riskTally['pending']))
                    <span class="gb-chip gb-chip-muted">{{ $riskTally['pending'] }} pending</span>
                @endif

                <a href="{{ route('instructor.classes.gradebook.grading-config.edit', $class) }}" class="btn btn-secondary"
                   style="padding:7px 13px;font-size:.8rem;">
                    <i class="fa-solid fa-sliders"></i>
                    Grading Configuration
                </a>
            </div>
        </div>

        @if ($configuration && ! $configuration->isValid())
            <div class="gb-notice">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <strong>Grading configuration is not in use.</strong>
                    {{ $configuration->validationMessage() }}
                    <a href="{{ route('instructor.classes.gradebook.grading-config.edit', $class) }}">Fix it</a>
                </div>
            </div>
        @elseif ($configuration && $configuration->isValid())
            @php
                // Built as a string first. Emitting the labels inline with
                // @foreach and a trailing @if on the same line compiled to
                // broken PHP.
                $weightSummary = collect($configuration->enabledWeights())
                    ->map(function ($weight, $type) {
                        $label = \App\Models\GradeConfiguration::COMPONENTS[$type]['label'] ?? ucfirst($type);
                        $formatted = rtrim(rtrim(number_format((float) $weight, 2), '0'), '.');

                        return $label.' '.$formatted.'%';
                    })
                    ->implode(', ');
            @endphp
            <div class="gb-notice gb-notice-ok">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong>Weighted configuration active.</strong>
                    {{ $weightSummary }} — totalling {{ number_format($configuration->totalWeight(), 0) }}%.
                </div>
            </div>
        @endif

        <!-- Filters -->
        <div class="user-toolbar">

            <select id="statusFilter" onchange="filterGrades()" class="form-control">
                <option value="">All Status</option>
                <option value="graded">Graded</option>
                <option value="ungraded">Ungraded</option>
                <option value="released">Released</option>
            </select>

            <input type="text" id="studentSearch" placeholder="Search students..." onkeyup="filterGrades()" class="form-control" style="flex:1">
        </div>

        <div class="user-panel-body">
        <!-- Grades Table -->
        <div class="user-table-wrap gradebook-table-wrap">
            <table class="table gradebook-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        @foreach($gradeItems as $item)
                            <th>
                                <div style="font-size:0.85rem;font-weight:600">{{ $item->title }}</div>
                                <div class="user-email">{{ $item->max_points }} pts</div>
                            </th>
                        @endforeach
                        <th title="Earned points out of possible points across released, graded items">
                            Total
                            <div class="user-email">released &amp; graded only</div>
                        </th>

                        @foreach ($componentColumns as $column)
                            <th title="{{ $column['label'] }}{{ $column['weight'] > 0 ? ' — '.$column['weight'].'% of the final grade' : '' }}">
                                {{ $column['label'] }}
                                <div class="user-email">
                                    @if ($column['weight'] > 0)
                                        {{ rtrim(rtrim(number_format($column['weight'], 2), '0'), '.') }}&percnt; weight
                                    @else
                                        component
                                    @endif
                                </div>
                            </th>
                        @endforeach

                        <th title="{{ $configuration && $configuration->isValid() ? 'Weighted final grade from the grading configuration' : 'Points-weighted class grade' }}">
                            Final Grade
                            <div class="user-email">{{ config('lms.passing_grade', 60) }}&percnt; to pass</div>
                        </th>
                        <th title="Deterministic signals: component floor, attendance, lesson progress and final grade">
                            Status
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $enrollment)
                        @php
                            $summary = $summaries[$enrollment->student_id] ?? null;
                            $rowGradeCount = $gradeItems->filter(fn ($item) => $item->grades->contains('student_id', $enrollment->student_id))->count();
                        @endphp
                        <tr class="student-row"
                            data-student="{{ $enrollment->student->name }}"
                            data-status="{{ ! $gradeItems->isEmpty() && $rowGradeCount === $gradeItems->count() ? 'graded' : 'ungraded' }}{{ ($summary['is_graded'] ?? false) ? ' released' : '' }}"
                            style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 12px;">
                                <div style="font-weight: 600; color: #1e293b;">{{ $enrollment->student->name }}</div>
                                <div style="font-size: 0.85rem; color: #64748b;">{{ $enrollment->student->email }}</div>
                            </td>
                            
                            @foreach($gradeItems as $item)
                                <td style="padding: 12px; text-align: center;">
                                    @php
                                        $grade = $item->grades->where('student_id', $enrollment->student_id)->first();
                                    @endphp
                                    
                                    @if($grade)
                                        <div style="font-weight: 600; color: {{ ($grade->score_percent >= 70 ? '#16a34a' : ($grade->score_percent >= 50 ? '#d97706' : '#dc2626')) }};">
                                            {{ $grade->points }}/{{ $item->max_points }}
                                        </div>
                                        <div style="font-size: 0.75rem; color: #64748b;">{{ number_format($grade->score_percent, 1) }}%</div>
                                        @if(!$item->is_released)
                                            <span style="background: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">
                                                Hidden
                                            </span>
                                        @endif
                                    @else
                                        <button type="button" onclick="enterGrade({{ $item->id }}, {{ $enrollment->student_id }})" 
                                                class="btn-modal-cancel" style="padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                            Enter
                                        </button>
                                    @endif
                                </td>
                            @endforeach

                            {{-- Released + graded items only, matching the student detail page and analytics. --}}
                            <td style="padding: 12px; text-align: center; font-weight: 600;">
                                @if($summary['is_graded'] ?? false)
                                    {{ $summary['earned_points'] }}/{{ $summary['max_points'] }}
                                @else
                                    <span style="color: #94a3b8; font-weight: 500;">—</span>
                                @endif
                            </td>

                            @php
                                $breakdown = $breakdowns[$enrollment->student_id] ?? null;
                                $rowRisk = $risk[$enrollment->student_id] ?? null;
                            @endphp

                            @foreach ($componentColumns as $column)
                                @php $component = $breakdown['components'][$column['type']] ?? null; @endphp
                                <td style="padding: 12px; text-align: center;">
                                    @if (! $component || ! ($component['is_graded'] ?? false))
                                        <span style="color: #cbd5e1;">—</span>
                                    @else
                                        @php $pct = (float) $component['percent']; @endphp
                                        <div style="font-weight: 600; color: {{ $pct >= 70 ? '#16a34a' : ($pct >= 50 ? '#d97706' : '#dc2626') }};">
                                            {{ number_format($pct, 1) }}%
                                        </div>
                                        <div style="font-size: 0.7rem; color: #94a3b8;">
                                            {{ $component['earned'] }}/{{ $component['possible'] }}
                                        </div>
                                    @endif
                                </td>
                            @endforeach

                            <td style="padding: 12px; text-align: center; font-weight: 600; color: {{ (($breakdown['final_grade'] ?? 0) >= 70 ? '#16a34a' : (($breakdown['final_grade'] ?? 0) >= 50 ? '#d97706' : '#dc2626')) }};">
                                @if ($breakdown['is_graded'] ?? false)
                                    {{ number_format((float) $breakdown['final_grade'], 2) }}%
                                    <div style="font-size: 0.7rem; color: #64748b;">{{ $breakdown['letter_grade'] }}</div>
                                @else
                                    <span style="color: #94a3b8; font-weight: 500;">Not graded</span>
                                @endif
                            </td>

                            <td style="padding: 12px; text-align: center;">
                                @if ($rowRisk)
                                    <span class="gb-status gb-status-{{ $rowRisk['tone'] }}"
                                          @if ($rowRisk['reasons'])
                                          title="{{ implode(' ', $rowRisk['reasons']) }}"
                                          @endif>
                                        {{ $rowRisk['label'] }}
                                    </span>

                                    @if ($rowRisk['reasons'] && $rowRisk['status'] === 'at_risk')
                                        <ul class="gb-reasons">
                                            @foreach (array_slice($rowRisk['reasons'], 0, 3) as $reason)
                                                <li>{{ $reason }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                @else
                                    <span style="color: #94a3b8;">—</span>
                                @endif
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <a href="{{ route('instructor.classes.gradebook.student', [$class, $enrollment->student_id]) }}"
                                   class="btn-add" title="View student grades" aria-label="View {{ $enrollment->student->name }}'s grades"
                                   style="padding: 6px 12px; border-radius: 6px; font-size: 0.85rem;">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div style="margin-top: 16px;">
            {{ $students->links() }}
        </div>
        </div>
    </div>
</div>

    <!-- Quick Grade Entry Modal -->
    <div id="gradeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: relative; max-width: 500px; margin: 100px auto; background: white; border-radius: 12px; padding: 24px;">
            <h3 style="margin: 0 0 16px 0;"><i class="fa-solid fa-edit"></i> Enter Grade</h3>
            
            <form id="gradeForm" onsubmit="saveGrade(event)">
                @csrf
                <input type="hidden" id="gradeItemId" name="grade_item_id">
                <input type="hidden" id="studentId" name="student_id">
                <input type="hidden" id="submissionId" name="submission_id">
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Points</label>
                    <input type="number" id="points" name="points" step="0.1" min="0" required
                           style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <div id="maxPointsInfo" style="font-size: 0.85rem; color: #64748b; margin-top: 4px;"></div>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Feedback</label>
                    <textarea id="feedback" name="feedback" rows="4"
                              style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; resize: vertical;"
                              placeholder="Provide feedback to the student..."></textarea>
                    <button type="button" id="suggestFeedbackBtn" onclick="suggestFeedback()" class="btn-secondary" style="margin-top: 8px; padding: 8px 14px; border-radius: 6px; cursor: pointer; display: none;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Suggest feedback (AI)
                    </button>
                </div>
                
                <!-- Rubric Integration -->
                <div id="rubricSection" style="margin-bottom: 16px; display: none;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                        <i class="fa-solid fa-list-check"></i> Rubric Assessment
                    </label>
                    <div id="rubricCriteria" style="padding: 12px; background: #f8fafc; border-radius: 6px;"></div>
                </div>
                
                <div style="display: flex; gap: 8px; margin-top: 16px;">
                    <button type="submit" class="btn-add" style="flex: 1; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-save"></i> Save Grade
                    </button>
                    <button type="button" onclick="closeGradeModal()" class="btn-modal-cancel" style="flex: 1; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Grade Modal -->
    <div id="bulkGradeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: relative; max-width: 600px; margin: 100px auto; background: white; border-radius: 12px; padding: 24px; max-height: 80vh; overflow-y: auto;">
            <h3 style="margin: 0 0 16px 0;"><i class="fa-solid fa-edit"></i> Bulk Grade Entry</h3>
            
            <form id="bulkGradeForm" onsubmit="saveBulkGrades(event)">
                @csrf
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Grade Item</label>
                    <select id="bulkGradeItem" name="grade_item_id" required onchange="loadBulkStudents()"
                            style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="">Select Grade Item</option>
                        @foreach($gradeItems as $item)
                            <option value="{{ $item->id }}">{{ $item->title }} ({{ $item->max_points }} pts)</option>
                        @endforeach
                    </select>
                </div>
                
                <div id="bulkStudentsContainer" style="display: none;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Student Grades</label>
                    <div id="bulkStudentsList" style="max-height: 300px; overflow-y: auto;"></div>
                </div>
                
                <div style="display: flex; gap: 8px; margin-top: 16px;">
                    <button type="submit" class="btn-add" style="flex: 1; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-save"></i> Save All Grades
                    </button>
                    <button type="button" onclick="closeBulkGradeModal()" class="btn-modal-cancel" style="flex: 1; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const studentSubmissionIds = {{ json_encode($students->mapWithKeys(fn ($e) => [$e->student_id => optional($e->student->submissions->first())->id ?? null])) }};

        function enterGrade(gradeItemId, studentId) {
            document.getElementById('gradeItemId').value = gradeItemId;
            document.getElementById('studentId').value = studentId;

            // Set submission id for AI feedback suggestions if one exists.
            const subId = studentSubmissionIds[studentId] || null;
            document.getElementById('submissionId').value = subId || '';
            document.getElementById('suggestFeedbackBtn').style.display = subId ? 'inline-block' : 'none';

            // Get grade item info
            const gradeItem = {{ json_encode($gradeItems) }}.find(item => item.id === gradeItemId);
            if (gradeItem) {
                document.getElementById('maxPointsInfo').textContent = `Max points: ${gradeItem.max_points}`;
                document.getElementById('points').max = gradeItem.max_points;
            }

            // Load existing grade if any
            // This would typically be done via AJAX

            // Check if rubric is available
            if (gradeItem && gradeItem.rubric_id) {
                loadRubric(gradeItem.rubric_id);
            }

            document.getElementById('gradeModal').style.display = 'block';
        }

        function suggestFeedback() {
            const subId = document.getElementById('submissionId').value;
            if (!subId) {
                alert('This student has no assignment submission to base feedback on.');
                return;
            }

            const btn = document.getElementById('suggestFeedbackBtn');
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';

            fetch('{{ route('instructor.submissions.suggest-feedback', '__ID__') }}'.replace('__ID__', subId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: '{}'
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.draft) {
                    document.getElementById('feedback').value = data.draft;
                }
            })
            .catch(function () {
                alert('Could not generate a suggestion. Please try again.');
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = original;
            });
        }

        function closeGradeModal() {
            document.getElementById('gradeModal').style.display = 'none';
            document.getElementById('gradeForm').reset();
        }

        function saveGrade(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('{{ route('instructor.classes.gradebook.grades.store', $class) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'text/html'
                },
                body: formData,
                redirect: 'follow'
            })
            .then(function() {
                closeGradeModal();
                location.reload();
            })
            .catch(function() {
                event.target.submit();
            });
        }

        function showBulkGradeModal() {
            document.getElementById('bulkGradeModal').style.display = 'block';
        }

        function closeBulkGradeModal() {
            document.getElementById('bulkGradeModal').style.display = 'none';
            document.getElementById('bulkGradeForm').reset();
            document.getElementById('bulkStudentsContainer').style.display = 'none';
        }

        function loadBulkStudents() {
            const gradeItemId = document.getElementById('bulkGradeItem').value;
            if (!gradeItemId) return;
            
            const students = {{ json_encode($students->items()) }};
            const container = document.getElementById('bulkStudentsList');
            container.innerHTML = '';
            
            students.forEach(enrollment => {
                const studentDiv = document.createElement('div');
                studentDiv.style.cssText = 'padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px;';
                studentDiv.innerHTML = `
                    <div style="font-weight: 600; margin-bottom: 8px;">${enrollment.student.name}</div>
                    <input type="number" name="grades[${enrollment.student_id}]" 
                           placeholder="Points" step="0.1" min="0"
                           style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    <textarea name="feedback[${enrollment.student_id}]" rows="2" placeholder="Feedback"
                              style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; margin-top: 8px; resize: vertical;"></textarea>
                `;
                container.appendChild(studentDiv);
            });
            
            document.getElementById('bulkStudentsContainer').style.display = 'block';
        }

        function saveBulkGrades(event) {
            event.preventDefault();

            const form = event.target;
            const gradeItemId = document.getElementById('bulkGradeItem').value;
            if (!gradeItemId) {
                alert('Please select a grade item first.');
                return;
            }

            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const original = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

            fetch('{{ route('instructor.classes.gradebook.grades.bulk', $class) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'text/html'
                },
                body: formData,
                redirect: 'follow'
            })
            .then(function() {
                closeBulkGradeModal();
                location.reload();
            })
            .catch(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = original;
                form.submit();
            });
        }

        function showStudentGrades(studentId) {
            window.location.href = '{{ url('/instructor/classes/' . $class->id . '/gradebook/students') }}/' + studentId;
        }

        function filterGrades() {
            const statusFilter = document.getElementById('statusFilter').value;
            const studentSearch = document.getElementById('studentSearch').value.toLowerCase();

            const rows = document.querySelectorAll('.student-row');

            rows.forEach(row => {
                const studentName = (row.dataset.student || '').toLowerCase();
                const showByStudent = !studentSearch || studentName.includes(studentSearch);

                const flags = (row.dataset.status || '').split(' ');
                let showByStatus = true;

                if (statusFilter === 'graded') {
                    showByStatus = flags.includes('graded');
                } else if (statusFilter === 'ungraded') {
                    showByStatus = flags.includes('ungraded');
                } else if (statusFilter === 'released') {
                    showByStatus = flags.includes('released');
                }

                row.style.display = (showByStudent && showByStatus) ? '' : 'none';
            });
        }

        function exportGrades() {
            window.location.href = '{{ route('instructor.classes.gradebook.export', $class) }}';
        }

        function loadRubric(rubricId) {
            // Load rubric criteria via AJAX
            // This would populate the rubric section with criteria and levels
            document.getElementById('rubricSection').style.display = 'block';
        }

        // Close modals on outside click
        window.onclick = function(event) {
            if (event.target.id === 'gradeModal') {
                closeGradeModal();
            }
            if (event.target.id === 'bulkGradeModal') {
                closeBulkGradeModal();
            }
        }
    </script>
@endsection
<style>
    .gb-chip{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:999px;font-size:.7rem;font-weight:700}
    .gb-chip-danger{background:#fee2e2;color:#b91c1c}
    .gb-chip-muted{background:#f1f5f9;color:#64748b}
    .gb-notice{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;margin:0 0 16px;border-radius:10px;background:#fffbeb;border:1px solid #fde68a;font-size:.8rem;color:#92400e}
    .gb-notice-ok{background:#f0fdf4;border-color:#bbf7d0;color:#166534}
    .gb-notice i{margin-top:2px}
    .gb-notice a{color:inherit;font-weight:700;text-decoration:underline}
    .gb-status{display:inline-block;padding:4px 10px;border-radius:999px;font-size:.68rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase;white-space:nowrap}
    .gb-status-success{background:#dcfce7;color:#15803d}
    .gb-status-danger{background:#fee2e2;color:#b91c1c}
    .gb-status-warning{background:#fef3c7;color:#b45309}
    .gb-status-muted{background:#f1f5f9;color:#64748b}
    .gb-reasons{margin:6px 0 0;padding:0;list-style:none;text-align:left;font-size:.68rem;color:#b91c1c;line-height:1.35}
    .gb-reasons li{padding-left:10px;position:relative;margin-bottom:2px}
    .gb-reasons li::before{content:"•";position:absolute;left:0}
</style>
