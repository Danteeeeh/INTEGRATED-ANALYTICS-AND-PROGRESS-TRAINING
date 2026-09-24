<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Registrar (Staff) attendance oversight: view attendance records across
 * classes and generate per-class attendance reports. Read-only — marking
 * attendance stays with instructors.
 */
class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $query = AttendanceRecord::with(['class.course', 'student']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->integer('class_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->orderBy('attendance_date', 'desc')->paginate(20)->withQueryString();
        $classes = ClassModel::with('course')->orderBy('code')->get();

        return view('registrar.attendance.index', compact('records', 'classes'));
    }

    public function report(ClassModel $class): View
    {
        $class->load(['course', 'instructor']);

        $records = AttendanceRecord::with('student')
            ->where('class_id', $class->id)
            ->orderBy('attendance_date', 'desc')
            ->get();

        $summary = [
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'excused' => $records->where('status', 'excused')->count(),
        ];

        return view('registrar.attendance.report', compact('class', 'records', 'summary'));
    }
}
