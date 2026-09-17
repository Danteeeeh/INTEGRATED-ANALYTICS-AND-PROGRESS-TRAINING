<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Certificate::class);

        $query = Certificate::with(['course', 'class', 'student', 'issuedBy']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('issued_from')) {
            $query->whereDate('issued_at', '>=', $request->issued_from);
        }

        if ($request->filled('issued_to')) {
            $query->whereDate('issued_at', '<=', $request->issued_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                    ->orWhere('verification_code', 'like', "%{$search}%")
                    ->orWhere('student_name_display', 'like', "%{$search}%")
                    ->orWhere('course_name_display', 'like', "%{$search}%");
            });
        }

        $certificates = $query->orderByDesc('issued_at')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $students = User::whereHas('enrollments')->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);

        return view('admin.certificates.index', compact('certificates', 'courses', 'classes', 'students'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Certificate::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $preselectedClassId = $request->get('class_id');
        $preselectedStudentId = $request->get('student_id');

        $students = collect();
        if ($preselectedClassId) {
            $class = ClassModel::find($preselectedClassId);
            if ($class) {
                $students = $class->students()->orderBy('users.last_name')->orderBy('users.first_name')->get(['users.id', 'users.first_name', 'users.last_name', 'users.email']);
            }
        } else {
            $students = User::whereHas('enrollments')->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);
        }

        return view('admin.certificates.create', compact(
            'courses',
            'classes',
            'students',
            'preselectedClassId',
            'preselectedStudentId'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Certificate::class);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'course_id' => 'nullable|exists:courses,id',
            'student_id' => 'required|exists:users,id',
            'template_name' => 'nullable|string|max:255',
            'student_name_display' => 'nullable|string|max:255',
            'course_name_display' => 'nullable|string|max:255',
            'completion_date' => 'nullable|date',
            'final_grade' => 'nullable|numeric|min:0|max:100',
        ]);

        $class = ClassModel::findOrFail($validated['class_id']);
        $student = User::findOrFail($validated['student_id']);

        $validated['issued_by'] = $request->user()->id;
        $validated['issued_at'] = now();
        $validated['course_id'] = $validated['course_id'] ?? $class->course_id;
        $validated['student_name_display'] = $validated['student_name_display'] ?? $student->name;
        $validated['course_name_display'] = $validated['course_name_display'] ?? ($class->course->title ?? '');
        $validated['completion_date'] = $validated['completion_date'] ?? now()->toDateString();
        $validated['certificate_number'] = 'CERT-'.strtoupper(uniqid());
        $validated['verification_code'] = strtoupper(substr(md5(uniqid()), 0, 12));
        $validated['status'] = Certificate::STATUS_ISSUED;

        $certificate = Certificate::create($validated);

        session()->flash('success', 'Certificate issued successfully.');

        return redirect()->route('admin.certificates.show', $certificate);
    }

    public function show(Certificate $certificate): View
    {
        $this->authorize('view', $certificate);

        $certificate->load(['course', 'class', 'student', 'issuedBy', 'completions']);

        return view('admin.certificates.show', compact('certificate'));
    }

    public function edit(Certificate $certificate): View
    {
        $this->authorize('update', $certificate);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $students = User::whereHas('enrollments')->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);

        return view('admin.certificates.edit', compact('certificate', 'courses', 'classes', 'students'));
    }

    public function update(Request $request, Certificate $certificate): RedirectResponse
    {
        $this->authorize('update', $certificate);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'course_id' => 'nullable|exists:courses,id',
            'student_id' => 'required|exists:users,id',
            'template_name' => 'nullable|string|max:255',
            'student_name_display' => 'nullable|string|max:255',
            'course_name_display' => 'nullable|string|max:255',
            'completion_date' => 'nullable|date',
            'final_grade' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:issued,revoked',
            'revoke_reason' => 'nullable|required_if:status,revoked|string|max:500',
        ]);

        if ($validated['status'] === Certificate::STATUS_REVOKED && $certificate->status !== Certificate::STATUS_REVOKED) {
            $validated['revoked_at'] = now();
        } elseif ($validated['status'] === Certificate::STATUS_ISSUED) {
            $validated['revoked_at'] = null;
            $validated['revoke_reason'] = null;
        }

        $certificate->update($validated);

        session()->flash('success', 'Certificate updated successfully.');

        return redirect()->route('admin.certificates.show', $certificate);
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        $this->authorize('delete', $certificate);

        try {
            $certificate->delete();
            session()->flash('success', 'Certificate deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.certificates.index');
    }

    public function issue(Request $request): RedirectResponse
    {
        $this->authorize('create', Certificate::class);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
            'template_name' => 'nullable|string|max:255',
            'completion_date' => 'nullable|date',
        ]);

        $class = ClassModel::with('course')->findOrFail($validated['class_id']);
        $issued = 0;
        $issuedBy = $request->user()->id;
        $issuedAt = now();

        foreach ($validated['student_ids'] as $studentId) {
            $student = User::findOrFail($studentId);

            $existing = Certificate::where('class_id', $validated['class_id'])
                ->where('student_id', $studentId)
                ->where('status', Certificate::STATUS_ISSUED)
                ->first();

            if (! $existing) {
                Certificate::create([
                    'class_id' => $validated['class_id'],
                    'course_id' => $class->course_id,
                    'student_id' => $studentId,
                    'issued_by' => $issuedBy,
                    'issued_at' => $issuedAt,
                    'certificate_number' => 'CERT-'.strtoupper(uniqid()),
                    'verification_code' => strtoupper(substr(md5(uniqid()), 0, 12)),
                    'template_name' => $validated['template_name'] ?? null,
                    'student_name_display' => $student->name,
                    'course_name_display' => $class->course->title ?? '',
                    'completion_date' => $validated['completion_date'] ?? now()->toDateString(),
                    'status' => Certificate::STATUS_ISSUED,
                ]);
                $issued++;
            }
        }

        session()->flash('success', "{$issued} certificate(s) issued successfully.");

        return back();
    }

    public function download(Certificate $certificate): RedirectResponse
    {
        $this->authorize('view', $certificate);

        session()->flash('success', "Certificate download ({$certificate->certificate_number}) queued.");

        return back();
    }
}
