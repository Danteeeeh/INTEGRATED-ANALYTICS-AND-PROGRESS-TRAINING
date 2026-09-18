<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use Illuminate\View\View;

class GradebookController extends Controller
{
    public function index(ClassModel $class): View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('class_id', $class->id)
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        $class->load('course', 'gradeCategories', 'gradeItems.category');

        $gradeItems = GradeItem::where('class_id', $class->id)
            ->where('is_released', true)
            ->with('category')
            ->orderBy('position', 'asc')
            ->get();

        $grades = Grade::where('student_id', $studentId)
            ->whereIn('grade_item_id', $gradeItems->pluck('id'))
            ->get()
            ->keyBy('grade_item_id');

        $totalPoints = 0;
        $earnedPoints = 0;
        $totalWeighted = 0;
        $earnedWeighted = 0;

        foreach ($gradeItems as $item) {
            $grade = $grades->get($item->id);
            $maxPoints = $item->max_points ?? 0;
            $points = $grade?->points ?? 0;
            $factor = $item->factor ?? 1;

            if ($maxPoints > 0) {
                $totalPoints += $maxPoints * $factor;
                $earnedPoints += $points * $factor;
            }
        }

        $overallPercent = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;

        return view('student.gradebook.index', compact(
            'class',
            'enrollment',
            'gradeItems',
            'grades',
            'totalPoints',
            'earnedPoints',
            'overallPercent'
        ));
    }
}
