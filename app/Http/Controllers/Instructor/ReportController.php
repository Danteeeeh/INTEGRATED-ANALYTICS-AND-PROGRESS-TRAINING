<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\View\View;

/**
 * LMS Reports for Faculty — real data, exportable as CSV.
 *
 * Reports intended for AA are explicitly labelled "LMS assessment reports /
 * grade-reference exports"; they are NOT official final grade records.
 * Official final grades remain under the authority of AA.
 */
class ReportController extends Controller
{
    public function index(): View
    {
        $classes = ClassModel::where('instructor_id', auth()->id())
            ->with(['course', 'enrollments'])
            ->withCount(['enrollments'])
            ->orderBy('code')
            ->get();

        return view('instructor.reports.index', compact('classes'));
    }
}
