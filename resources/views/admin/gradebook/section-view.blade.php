@extends('layouts.admin')
@section('title', 'Gradebook - Section')
@php($activeNav = 'gradebook')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-graduation-cap"></i> Gradebook - {{ $section->code }}</h3>
        <span class="dash-section-kicker">{{ $section->program->code }} — {{ $section->program->name }}</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.gradebook.programs') }}"><i class="fa-solid fa-arrow-left"></i> Back to Programs</a>
</div>
<section class="dash-panel">
    @forelse($organizedGrades as $studentId => $studentData)
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 16px; border-radius: 8px 8px 0 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="margin: 0;">{{ $studentData['student']->name }}</h4>
                        <small>{{ $studentData['student']->email }}</small>
                    </div>
                    <div style="text-align: right;">
                        <small>Student ID: {{ $studentData['student']->id }}</small>
                    </div>
                </div>
            </div>
            <div class="card-body" style="padding: 16px;">
                @if(count($studentData['quizzes']) > 0)
                    <div style="margin-bottom: 20px;">
                        <h5 style="color: #10b981; margin-bottom: 12px; border-bottom: 2px solid #10b981; padding-bottom: 8px;">
                            <i class="fa-solid fa-clipboard-question"></i> Quizzes
                        </h5>
                        <table class="dash-table" style="font-size: 0.9rem;">
                            <thead>
                                <tr>
                                    <th>Quiz</th>
                                    <th>Class</th>
                                    <th>Max Points</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($studentData['quizzes'] as $quizData)
                                    <tr>
                                        <td>{{ $quizData['grade_item']->title }}</td>
                                        <td>{{ $quizData['class']->code }}</td>
                                        <td>{{ $quizData['grade_item']->max_points }}</td>
                                        <td>{{ $quizData['grade'] ? $quizData['grade']->points : '—' }}</td>
                                        <td>{{ $quizData['grade'] ? number_format($quizData['grade']->score_percent, 2) . '%' : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(count($studentData['assignments']) > 0)
                    <div style="margin-bottom: 20px;">
                        <h5 style="color: #3b82f6; margin-bottom: 12px; border-bottom: 2px solid #3b82f6; padding-bottom: 8px;">
                            <i class="fa-solid fa-file-alt"></i> Assignments
                        </h5>
                        <table class="dash-table" style="font-size: 0.9rem;">
                            <thead>
                                <tr>
                                    <th>Assignment</th>
                                    <th>Class</th>
                                    <th>Max Points</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($studentData['assignments'] as $assignmentData)
                                    <tr>
                                        <td>{{ $assignmentData['grade_item']->title }}</td>
                                        <td>{{ $assignmentData['class']->code }}</td>
                                        <td>{{ $assignmentData['grade_item']->max_points }}</td>
                                        <td>{{ $assignmentData['grade'] ? $assignmentData['grade']->points : '—' }}</td>
                                        <td>{{ $assignmentData['grade'] ? number_format($assignmentData['grade']->score_percent, 2) . '%' : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(count($studentData['exams']) > 0)
                    <div style="margin-bottom: 20px;">
                        <h5 style="color: #ef4444; margin-bottom: 12px; border-bottom: 2px solid #ef4444; padding-bottom: 8px;">
                            <i class="fa-solid fa-file-signature"></i> Exams
                        </h5>
                        <table class="dash-table" style="font-size: 0.9rem;">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Class</th>
                                    <th>Max Points</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($studentData['exams'] as $examData)
                                    <tr>
                                        <td>{{ $examData['grade_item']->title }}</td>
                                        <td>{{ $examData['class']->code }}</td>
                                        <td>{{ $examData['grade_item']->max_points }}</td>
                                        <td>{{ $examData['grade'] ? $examData['grade']->points : '—' }}</td>
                                        <td>{{ $examData['grade'] ? number_format($examData['grade']->score_percent, 2) . '%' : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(count($studentData['other']) > 0)
                    <div style="margin-bottom: 20px;">
                        <h5 style="color: #64748b; margin-bottom: 12px; border-bottom: 2px solid #64748b; padding-bottom: 8px;">
                            <i class="fa-solid fa-tasks"></i> Other
                        </h5>
                        <table class="dash-table" style="font-size: 0.9rem;">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Class</th>
                                    <th>Max Points</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($studentData['other'] as $otherData)
                                    <tr>
                                        <td>{{ $otherData['grade_item']->title }}</td>
                                        <td>{{ $otherData['class']->code }}</td>
                                        <td>{{ $otherData['grade_item']->max_points }}</td>
                                        <td>{{ $otherData['grade'] ? $otherData['grade']->points : '—' }}</td>
                                        <td>{{ $otherData['grade'] ? number_format($otherData['grade']->score_percent, 2) . '%' : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(count($studentData['quizzes']) === 0 && count($studentData['assignments']) === 0 && count($studentData['exams']) === 0 && count($studentData['other']) === 0)
                    <p style="color: #64748b; text-align: center; padding: 20px;">No grades recorded for this student.</p>
                @endif
            </div>
        </div>
    @empty
        <div class="search-no-results">No students found in this section.</div>
    @endforelse
</section>
@endsection
