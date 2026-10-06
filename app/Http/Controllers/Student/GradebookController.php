<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Services\GradeService;
use Illuminate\View\View;

class GradebookController extends Controller
{
    public function index(ClassModel $class): View
    {
        $this->authorize('view', Grade::class);

        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('class_id', $class->id)
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        $class->load('course', 'gradeItems');

        $gradeItems = GradeItem::where('class_id', $class->id)
            ->where('is_released', true)
            ->orderBy('position', 'asc')
            ->get();

        $grades = Grade::where('student_id', $studentId)
            ->whereIn('grade_item_id', $gradeItems->pluck('id'))
            ->get()
            ->keyBy('grade_item_id');

        // Exactly the same computation the instructor gradebook and Performance
        // Analytics use, so a student and their instructor always see the same
        // number. Released-but-ungraded items no longer drag the total down.
        $summary = app(GradeService::class)->computeStudentClassGrade($studentId, $class->id);

        $totalPoints = $summary['max_points'];
        $earnedPoints = $summary['earned_points'];
        $overallPercent = $summary['percent'];

        return view('student.gradebook.index', compact(
            'class',
            'enrollment',
            'gradeItems',
            'grades',
            'totalPoints',
            'earnedPoints',
            'overallPercent',
            'summary'
        ));
    }
}
