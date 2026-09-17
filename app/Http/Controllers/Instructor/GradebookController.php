<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeCategory;
use App\Models\GradeItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradebookController extends Controller
{
    public function index(ClassModel $class): View
    {
        $this->authorize('viewAny', Grade::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $class->load(['gradeCategories.items', 'enrollments.student']);

        $students = $class->enrollments()
            ->where('status', 'active')
            ->with('student')
            ->paginate(20);

        $gradeCategories = GradeCategory::where('class_id', $class->id)
            ->with('items.grades')
            ->orderBy('position', 'asc')
            ->get();

        $gradeItems = GradeItem::where('class_id', $class->id)
            ->with('grades', 'category')
            ->orderBy('position', 'asc')
            ->get();

        return view('instructor.gradebook.index', compact('class', 'students', 'gradeCategories', 'gradeItems'));
    }

    public function storeGrade(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('create', Grade::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $validated = $request->validate([
            'grade_item_id' => 'required|exists:grade_items,id',
            'student_id' => 'required|exists:users,id',
            'points' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        $gradeItem = GradeItem::findOrFail($validated['grade_item_id']);
        abort_if($gradeItem->class_id !== $class->id, 403);

        $enrollment = Enrollment::where('class_id', $class->id)
            ->where('student_id', $validated['student_id'])
            ->firstOrFail();

        $scorePercent = $gradeItem->max_points > 0 ? ($validated['points'] / $gradeItem->max_points) * 100 : 0;

        Grade::create([
            'grade_item_id' => $gradeItem->id,
            'student_id' => $validated['student_id'],
            'points' => $validated['points'],
            'score_percent' => $scorePercent,
            'feedback' => $validated['feedback'] ?? null,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        return redirect()->route('instructor.classes.gradebook.index', $class)
            ->with('success', 'Grade saved successfully.');
    }

    public function updateGrade(Request $request, ClassModel $class, Grade $grade): RedirectResponse
    {
        $this->authorize('update', $grade);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $validated = $request->validate([
            'points' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
            'override_note' => 'nullable|string',
            'is_override' => 'boolean',
        ]);

        $gradeItem = GradeItem::findOrFail($grade->grade_item_id);
        $scorePercent = $gradeItem->max_points > 0 ? ($validated['points'] / $gradeItem->max_points) * 100 : 0;

        $grade->update([
            'points' => $validated['points'],
            'score_percent' => $scorePercent,
            'feedback' => $validated['feedback'] ?? null,
            'override_note' => $validated['override_note'] ?? null,
            'is_override' => $validated['is_override'] ?? false,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        return redirect()->route('instructor.classes.gradebook.index', $class)
            ->with('success', 'Grade updated successfully.');
    }

    public function releaseGrades(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('update', Grade::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $validated = $request->validate([
            'grade_item_ids' => 'nullable|array',
            'grade_item_ids.*' => 'exists:grade_items,id',
        ]);

        $query = GradeItem::where('class_id', $class->id);

        if (isset($validated['grade_item_ids']) && count($validated['grade_item_ids']) > 0) {
            $query->whereIn('id', $validated['grade_item_ids']);
        }

        $query->update([
            'is_released' => true,
            'released_at' => now(),
        ]);

        return redirect()->route('instructor.classes.gradebook.index', $class)
            ->with('success', 'Grades released successfully.');
    }

    public function storeBulkGrades(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('create', Grade::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $validated = $request->validate([
            'grade_item_id' => 'required|exists:grade_items,id',
            'grades' => 'required|array',
            'grades.*' => 'nullable|numeric|min:0',
            'feedback' => 'nullable|array',
            'feedback.*' => 'nullable|string',
        ]);

        $gradeItem = GradeItem::findOrFail($validated['grade_item_id']);
        abort_if($gradeItem->class_id !== $class->id, 403);

        $saved = 0;

        foreach ($validated['grades'] as $studentId => $points) {
            if ($points === null || $points === '') {
                continue;
            }

            // Only grade students actually enrolled in this class
            $enrolled = Enrollment::where('class_id', $class->id)
                ->where('student_id', $studentId)
                ->exists();

            if (! $enrolled) {
                continue;
            }

            $points = min((float) $points, (float) $gradeItem->max_points);
            $scorePercent = $gradeItem->max_points > 0 ? ($points / $gradeItem->max_points) * 100 : 0;

            Grade::updateOrCreate(
                [
                    'grade_item_id' => $gradeItem->id,
                    'student_id' => $studentId,
                ],
                [
                    'points' => $points,
                    'score_percent' => $scorePercent,
                    'feedback' => $validated['feedback'][$studentId] ?? null,
                    'graded_by' => auth()->id(),
                    'graded_at' => now(),
                ]
            );

            $saved++;
        }

        return redirect()->route('instructor.classes.gradebook.index', $class)
            ->with('success', "Bulk grades saved for {$saved} student(s).");
    }

    public function export(Request $request, ClassModel $class): StreamedResponse
    {
        $this->authorize('viewAny', Grade::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $gradeItems = GradeItem::where('class_id', $class->id)
            ->with('grades', 'category')
            ->orderBy('position', 'asc')
            ->get();

        $enrollments = $class->enrollments()
            ->where('status', 'active')
            ->with('student')
            ->get();

        $filename = 'gradebook-'.Str::slug($class->code ?? 'class-'.$class->id).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($gradeItems, $enrollments) {
            $out = fopen('php://output', 'w');

            // Header row: student info + one column per grade item
            $header = ['Student Name', 'Email'];
            foreach ($gradeItems as $item) {
                $header[] = $item->title.' (/'.$item->max_points.')';
            }
            $header[] = 'Total Earned';
            $header[] = 'Total Possible';
            $header[] = 'Percent';
            fputcsv($out, $header);

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                $row = [$student?->name ?? 'Unknown', $student?->email ?? ''];
                $earned = 0;
                $possible = 0;

                foreach ($gradeItems as $item) {
                    $grade = $item->grades->firstWhere('student_id', $enrollment->student_id);
                    $row[] = $grade ? $grade->points : '';
                    if ($grade) {
                        $earned += $grade->points;
                    }
                    $possible += $item->max_points;
                }

                $row[] = $earned;
                $row[] = $possible;
                $row[] = $possible > 0 ? number_format(($earned / $possible) * 100, 1).'%' : '0.0%';
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function studentGrades(ClassModel $class, User $student): View
    {
        $this->authorize('viewAny', Grade::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $enrollment = Enrollment::where('class_id', $class->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $gradeItems = GradeItem::where('class_id', $class->id)
            ->with(['category', 'grades' => fn ($q) => $q->where('student_id', $student->id)])
            ->orderBy('position', 'asc')
            ->get();

        $gradeCategories = GradeCategory::where('class_id', $class->id)
            ->with('items.grades')
            ->orderBy('position', 'asc')
            ->get();

        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($gradeItems as $item) {
            $grade = $item->grades->first();
            if ($grade && $item->is_released) {
                $totalPoints += $item->max_points;
                $earnedPoints += $grade->points;
            }
        }

        return view('instructor.gradebook.student', compact(
            'class',
            'student',
            'enrollment',
            'gradeItems',
            'gradeCategories',
            'totalPoints',
            'earnedPoints'
        ));
    }
}
