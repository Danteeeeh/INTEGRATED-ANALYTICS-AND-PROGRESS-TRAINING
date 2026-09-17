<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))->count(),
            'active_enrollments' => Enrollment::where('status', 'active')->count(),
            'completed_enrollments' => Enrollment::where('status', 'completed')->count(),
            'dropped_enrollments' => Enrollment::where('status', 'dropped')->count(),
            'total_classes' => ClassModel::count(),
        ];

        return view('registrar.reports.index', compact('stats'));
    }
}
