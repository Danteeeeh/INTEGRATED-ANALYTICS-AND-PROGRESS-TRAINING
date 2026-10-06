<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Role;
use App\Models\User;
use App\Services\SubjectService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Produces the academic record (transcript) the registrar's office receives.
 *
 * Records are generated from released, graded grade items only, using the same
 * GradeService computation as every gradebook in the app, so what the registrar
 * reads is exactly what the instructor submitted.
 */
class AcademicRecordController extends Controller
{
    public function __construct(private SubjectService $subjects) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->input('search', ''));
        $periodId = $request->integer('period_id') ?: null;

        $students = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->with(['section', 'program'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('identifier', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.academic-records.index', [
            'students' => $students,
            'search' => $search,
            'periodId' => $periodId,
            'periods' => AcademicPeriod::orderByDesc('start_date')->get(),
        ]);
    }

    public function show(Request $request, User $student): View
    {
        $this->authorize('view', $student);

        $periodId = $request->integer('period_id') ?: null;

        $record = $this->subjects->academicRecord($student, $periodId);

        return view('admin.academic-records.show', [
            'record' => $record,
            'periodId' => $periodId,
            'periods' => AcademicPeriod::orderByDesc('start_date')->get(),
        ]);
    }

    /**
     * CSV hand-off so the registrar can load the record into their own sheet.
     */
    public function export(Request $request, User $student)
    {
        $this->authorize('view', $student);

        $record = $this->subjects->academicRecord(
            $student,
            $request->integer('period_id') ?: null,
        );

        $filename = 'academic_record_'.($student->identifier ?: $student->id).'.csv';

        return response()->streamDownload(function () use ($record) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Student No', 'Last Name', 'First Name', 'Program', 'Period',
                'Subject Code', 'Shortname', 'Subject Name', 'Section',
                'Instructor', 'Units', 'Final Grade', 'Letter Grade', 'Remark',
            ]);

            foreach ($record['lines'] as $line) {
                fputcsv($handle, [
                    $record['identifier'],
                    $record['student']->last_name,
                    $record['student']->first_name,
                    $record['program'],
                    $line['period'],
                    $line['code'],
                    $line['shortname'],
                    $line['name'],
                    $line['section'],
                    $line['instructor'],
                    $line['units'],
                    $line['final_grade'],
                    $line['letter_grade'],
                    $line['remark'],
                ]);
            }

            fputcsv($handle, [
                $record['identifier'],
                $record['student']->last_name,
                $record['student']->first_name,
                $record['program'],
                '',
                '', '', '', '', '', $record['units_earned'],
                $record['gwa'], '',
                $record['overall_remark'].' ('.$record['graded_count'].' of '.$record['total_count'].' subjects graded)',
            ]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}