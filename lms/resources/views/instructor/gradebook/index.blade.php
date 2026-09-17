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
            value="{{ number_format($gradeItems->flatMap(fn ($item) => $item->grades)->avg('score_percent') ?? 0, 1) }}%"
            icon="fa-chart-line"
        />
        <x-user-stat-card label="Categories" value="{{ $gradeCategories->count() }}" icon="fa-layer-group" />
    </div>

    <!-- Grade Categories -->
    @if($gradeCategories->isNotEmpty())
        <div class="user-panel gradebook-panel">
            <div class="user-panel-head"><h3><i class="fa-solid fa-layer-group"></i> Grade Categories</h3></div>
            <div class="user-panel-body" style="padding-top:15px">
                @foreach($gradeCategories as $category)
                    <div class="user-toolbar" style="margin-bottom:10px;justify-content:space-between">
                        <div>
                            <strong>{{ $category->name }}</strong>
                            <div class="user-email">{{ $category->items->count() }} items • {{ $category->weight }}% weight</div>
                        </div>
                        <div style="text-align:right">
                            <div style="color:var(--user-accent);font-weight:700">{{ number_format($category->items->flatMap(fn ($item) => $item->grades)->avg('score_percent') ?? 0, 1) }}%</div>
                            <div class="user-email">Average</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Gradebook Table -->
    <div class="user-panel gradebook-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-table"></i> Student Grades</h3></div>

        <!-- Filters -->
        <div class="user-toolbar">
            <select id="categoryFilter" onchange="filterGrades()" class="form-control">
                <option value="">All Categories</option>
                @foreach($gradeCategories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

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
                                <div style="font-size:0.85rem;font-weight:600">{{ $item->name }}</div>
                                <div class="user-email">{{ $item->max_points }} pts</div>
                            </th>
                        @endforeach
                        <th>Total</th>
                        <th>%</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $enrollment)
                        <tr class="student-row" data-student="{{ $enrollment->student->name }}" style="border-bottom: 1px solid #e2e8f0;">
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
                                        <div style="font-weight: 600; color: {{ $grade->score_percent >= 70 ? '#16a34a' : ($grade->score_percent >= 50 ? '#d97706' : '#dc2626') }};">
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
                            
                            @php
                                $totalPoints = $gradeItems->sum('max_points');
                                $earnedPoints = $gradeItems->flatMap(fn ($item) => $item->grades)
                                    ->where('student_id', $enrollment->student_id)
                                    ->sum('points');
                                $totalPercent = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
                            @endphp
                            
                            <td style="padding: 12px; text-align: center; font-weight: 600;">
                                {{ $earnedPoints }}/{{ $totalPoints }}
                            </td>
                            <td style="padding: 12px; text-align: center; font-weight: 600; color: {{ $totalPercent >= 70 ? '#16a34a' : ($totalPercent >= 50 ? '#d97706' : '#dc2626') }};">
                                {{ number_format($totalPercent, 1) }}%
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <button type="button" onclick="showStudentGrades({{ $enrollment->student_id }})" 
                                        class="btn-add" style="padding: 6px 12px; border-radius: 6px; font-size: 0.85rem;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
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
        function enterGrade(gradeItemId, studentId) {
            document.getElementById('gradeItemId').value = gradeItemId;
            document.getElementById('studentId').value = studentId;
            
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
            const categoryFilter = document.getElementById('categoryFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const studentSearch = document.getElementById('studentSearch').value.toLowerCase();
            
            const rows = document.querySelectorAll('.student-row');
            
            rows.forEach(row => {
                const studentName = row.dataset.student.toLowerCase();
                const showByStudent = !studentSearch || studentName.includes(studentSearch);
                
                // Add category and status filtering logic here
                row.style.display = showByStudent ? '' : 'none';
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