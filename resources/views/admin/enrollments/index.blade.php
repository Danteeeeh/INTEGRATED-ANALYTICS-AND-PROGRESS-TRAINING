@extends('layouts.admin')

@section('title', 'Enrollments')
@php
    $activeNav = 'enrollment';
    $pageTitle = 'Enrollment Management';
    $pageIcon = '<i class="fa-solid fa-graduation-cap"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-graduation-cap"></i>
            Enrollment Management
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>All Enrollments</h3>
            <div style="display:flex;gap:8px;">
                <a href="{{ route('admin.enrollments.create') }}" class="btn-add">
                    <i class="fa-solid fa-plus"></i>
                    New Enrollment
                </a>
                <button type="button" onclick="toggleBulkEnroll()" class="btn-add" style="background:linear-gradient(135deg,#8b5cf6,#6366f1);">
                    <i class="fa-solid fa-users"></i>
                    Bulk Auto-Enroll
                </button>
            </div>
        </div>

        <div id="bulk-enroll-form" style="display:none;padding:20px;margin-bottom:20px;background:linear-gradient(135deg,rgba(139,92,246,.08),rgba(99,102,241,.08));border:1px solid rgba(139,92,246,.3);border-radius:12px;">
            <h4 style="margin:0 0 16px;color:#6366f1;font-size:.95rem;"><i class="fa-solid fa-users-gear"></i> Bulk Auto-Enroll Students</h4>
            <p style="margin:0 0 16px;color:#64748b;font-size:.8rem;">Automatically enroll all active students based on selected criteria. Select at least one filter.</p>
            <form method="POST" action="{{ route('admin.enrollments.bulk-auto-enroll') }}">
                @csrf
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:16px;">
                    <div>
                        <label style="display:block;margin-bottom:6px;font-size:.8rem;font-weight:600;color:#475569;">Department</label>
                        <select name="department_id" style="width:100%;padding:10px;border:1px solid #dce4f0;border-radius:8px;font-size:.85rem;">
                            <option value="">All Departments</option>
                            @foreach(\App\Models\Department::orderBy('name')->get() as $department)
                                <option value="{{ $department->id }}">{{ $department->name }} ({{ $department->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:6px;font-size:.8rem;font-weight:600;color:#475569;">Program</label>
                        <select name="program_id" style="width:100%;padding:10px;border:1px solid #dce4f0;border-radius:8px;font-size:.85rem;">
                            <option value="">All Programs</option>
                            @foreach(\App\Models\Program::with('department')->orderBy('name')->get() as $program)
                                <option value="{{ $program->id }}">{{ $program->name }} ({{ $program->code }}) — {{ $program->department?->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:6px;font-size:.8rem;font-weight:600;color:#475569;">Section</label>
                        <select name="section_id" style="width:100%;padding:10px;border:1px solid #dce4f0;border-radius:8px;font-size:.85rem;">
                            <option value="">All Sections</option>
                            @foreach(\App\Models\Section::with('program')->orderBy('name')->get() as $section)
                                <option value="{{ $section->id }}">{{ $section->name }} ({{ $section->code }}) — {{ $section->program?->code ?? '—' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:6px;font-size:.8rem;font-weight:600;color:#475569;">Course</label>
                        <select name="course_id" style="width:100%;padding:10px;border:1px solid #dce4f0;border-radius:8px;font-size:.85rem;">
                            <option value="">All Courses</option>
                            @foreach(\App\Models\Course::orderBy('title')->get() as $course)
                                <option value="{{ $course->id }}">{{ $course->title }} ({{ $course->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:6px;font-size:.8rem;font-weight:600;color:#475569;">Specific Class</label>
                        <select name="class_id" style="width:100%;padding:10px;border:1px solid #dce4f0;border-radius:8px;font-size:.85rem;">
                            <option value="">All Classes</option>
                            @foreach(\App\Models\ClassModel::with('course')->orderBy('code')->get() as $class)
                                <option value="{{ $class->id }}">{{ $class->code }} — {{ $class->course?->title ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:6px;font-size:.8rem;font-weight:600;color:#475569;">Academic Period</label>
                        <select name="academic_period_id" style="width:100%;padding:10px;border:1px solid #dce4f0;border-radius:8px;font-size:.85rem;">
                            <option value="">All Periods</option>
                            @foreach(\App\Models\AcademicPeriod::orderBy('name')->get() as $period)
                                <option value="{{ $period->id }}">{{ $period->name }} ({{ $period->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:6px;font-size:.8rem;font-weight:600;color:#475569;">Enrollment Status</label>
                        <select name="status" style="width:100%;padding:10px;border:1px solid #dce4f0;border-radius:8px;font-size:.85rem;">
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <button type="submit" name="preview" value="0" class="btn-add" style="background:linear-gradient(135deg,#8b5cf6,#6366f1);">
                        <i class="fa-solid fa-rocket"></i> Enroll Students
                    </button>
                    <button type="submit" name="preview" value="1" class="btn-add" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                        <i class="fa-solid fa-eye"></i> Preview First
                    </button>
                    <button type="button" onclick="toggleBulkEnroll()" class="btn-modal-cancel">Cancel</button>
                </div>
            </form>

            @if(session('preview_data'))
                <div style="margin-top:20px;padding:16px;background:rgba(255,255,255,.9);border:1px solid #e2e8f0;border-radius:8px;">
                    <h5 style="margin:0 0 12px;color:#475569;font-size:.9rem;"><i class="fa-solid fa-list-check"></i> Enrollment Preview</h5>
                    <div style="max-height:300px;overflow-y:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:.8rem;">
                            <thead>
                                <tr style="background:#f8fafc;">
                                    <th style="padding:8px;text-align:left;border-bottom:1px solid #e2e8f0;">Student</th>
                                    <th style="padding:8px;text-align:left;border-bottom:1px solid #e2e8f0;">Class</th>
                                    <th style="padding:8px;text-align:left;border-bottom:1px solid #e2e8f0;">Course</th>
                                    <th style="padding:8px;text-align:center;border-bottom:1px solid #e2e8f0;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(session('preview_data') as $item)
                                    <tr style="border-bottom:1px solid #f1f5f9;">
                                        <td style="padding:8px;">{{ $item['student'] }}</td>
                                        <td style="padding:8px;">{{ $item['class'] }}</td>
                                        <td style="padding:8px;">{{ $item['course'] }}</td>
                                        <td style="padding:8px;text-align:center;">
                                            @if($item['can_enroll'])
                                                <span style="color:#16a34a;font-weight:600;">✓ Can Enroll</span>
                                            @elseif($item['is_full'])
                                                <span style="color:#dc2626;">✗ Class Full</span>
                                            @elseif($item['already_enrolled'])
                                                <span style="color:#f59e0b;">⚠ Already Enrolled</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <form class="table-toolbar" method="GET" action="{{ route('admin.enrollments.index') }}">
            <input name="search" value="{{ request('search') }}" placeholder="Search student..." aria-label="Search enrollments">
            <select name="student_id" aria-label="Filter student">
                <option value="">All Students</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>{{ $student->full_name }}</option>
                @endforeach
            </select>
            <select name="class_id" aria-label="Filter class">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                @endforeach
            </select>
            <select name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['pending' => 'Pending', 'active' => 'Active', 'completed' => 'Completed', 'dropped' => 'Dropped'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="{{ route('admin.enrollments.index') }}" class="btn btn-secondary">Clear</a>
        </form>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Grade</th>
                    <th>Enrolled Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $enrollment)
                    <tr>
                        <td>{{ $enrollment->student->full_name ?? '-' }}</td>
                        <td>{{ $enrollment->class->code ?? '-' }} - {{ $enrollment->class->name ?? '-' }}</td>
                        <td>{{ $enrollment->class->course->name ?? '-' }}</td>
                        <td>
                            <span class="badge badge-{{ $enrollment->status }}">{{ ucfirst($enrollment->status) }}</span>
                        </td>
                        <td>{{ $enrollment->final_grade ?? '-' }}</td>
                        <td>{{ $enrollment->enrolled_at->format('M d, Y') }}</td>
                        <td class="actions-cell action-buttons">
                            @if($enrollment->status === 'pending')
                                <form method="POST" action="{{ route('admin.enrollments.approve', $enrollment) }}" class="inline-form">@csrf<button type="submit" class="btn-icon btn-success" title="Approve"><i class="fa-solid fa-check"></i></button></form>
                                <form method="POST" action="{{ route('admin.enrollments.reject', $enrollment) }}" class="inline-form">@csrf<button type="submit" class="btn-icon btn-danger" title="Reject" onclick="return confirm('Reject this enrollment request?')"><i class="fa-solid fa-xmark"></i></button></form>
                            @elseif(in_array($enrollment->status, ['active', 'completed']))
                                <form method="POST" action="{{ route('admin.enrollments.drop', $enrollment) }}" class="inline-form">@csrf<button type="submit" class="btn-icon btn-warning" title="Drop" onclick="return confirm('Drop this enrollment?')"><i class="fa-solid fa-user-minus"></i></button></form>
                            @endif
                            <a href="{{ route('admin.enrollments.show', $enrollment) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.enrollments.edit', $enrollment) }}" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.enrollments.destroy', $enrollment) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this enrollment?');">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:24px;color:#aaa;">
                            No enrollments found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($enrollments->hasPages())
            <div class="pagination">{{ $enrollments->appends(request()->query())->links() }}</div>
        @endif
    </div>

    <script>
    function toggleBulkEnroll() {
        const form = document.getElementById('bulk-enroll-form');
        if (form) {
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    }
    </script>
@endsection