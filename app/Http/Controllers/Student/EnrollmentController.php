<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function index(): View
    {
        $enrollments = Enrollment::query()
            ->where('student_id', auth()->id())
            ->with(['class.course', 'class.instructor', 'class.academicPeriod'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('student.enrollments.index', compact('enrollments'));
    }

    public function show(Enrollment $enrollment): View
    {
        $this->authorize('view', $enrollment);

        $enrollment->load(['class.course', 'class.instructor', 'class.academicPeriod']);

        return view('student.enrollments.show', compact('enrollment'));
    }
}
