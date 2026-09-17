<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\AuditLog;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'total_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))->count(),
            'active_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
                ->where('status', 'active')->count(),
            'pending_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
                ->where('status', 'pending')->count(),
            'active_enrollments' => Enrollment::where('status', 'active')->count(),
            'pending_enrollments' => Enrollment::where('status', 'pending')->count(),
            'completed_enrollments' => Enrollment::where('status', 'completed')->count(),
            'dropped_enrollments' => Enrollment::where('status', 'dropped')->count(),
            'total_classes' => ClassModel::count(),
            'active_classes' => ClassModel::where('status', 'active')->count(),
            'total_courses' => Course::count(),
            'published_courses' => Course::where('status', 'published')->count(),
            'enrollment_rate' => $this->completionRate(),
            'recent_enrollments' => Enrollment::with(['student', 'class.course'])
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get(),
            'recent_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get(),
            'recent_activity' => AuditLog::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get(),
            'recent_courses' => Course::orderBy('created_at', 'desc')->limit(6)->get(),
            'current_period' => AcademicPeriod::where('is_current', true)->first()
                ?? AcademicPeriod::latest()->first(),
        ];

        return view('registrar.dashboard', compact('stats'));
    }

    private function completionRate(): float
    {
        $total = Enrollment::whereIn('status', ['active', 'completed'])->count();
        if ($total === 0) {
            return 0;
        }

        return (Enrollment::where('status', 'completed')->count() / $total) * 100;
    }

    /**
     * Clear dashboard cache (mirrors instructor behavior)
     */
    public function clearCache(): RedirectResponse
    {
        Cache::forget('registrar_dashboard');

        return redirect()->route('registrar.dashboard')
            ->with('success', 'Dashboard cache cleared successfully.');
    }

    /**
     * Real-time stats para sa auto-refresh tiles
     */
    public function getRealTimeStats()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))->count(),
                    'active_enrollments' => Enrollment::where('status', 'active')->count(),
                    'pending_enrollments' => Enrollment::where('status', 'pending')->count(),
                    'total_classes' => ClassModel::count(),
                    'recent_enrollments_count' => Enrollment::count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch real-time stats: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Copy/adapted search across registrar-scoped data
     */
    public function search(Request $request)
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string|min:2',
                'type' => 'required|in:all,students,enrollments,classes,courses',
            ]);

            $query = $validated['query'];
            $type = $validated['type'];
            $results = [];
            $types = $type === 'all' ? ['students', 'enrollments', 'classes', 'courses'] : [$type];

            foreach ($types as $t) {
                $results = array_merge($results, $this->searchType($t, $query));
            }

            return response()->json([
                'success' => true,
                'data' => array_slice($results, 0, 30),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Search failed: '.$e->getMessage(),
            ], 500);
        }
    }

    private function searchType(string $type, string $query): array
    {
        switch ($type) {
            case 'students':
                return User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
                    ->where(function ($q) use ($query) {
                        $q->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('email', 'like', "%{$query}%")
                            ->orWhere('identifier', 'like', "%{$query}%");
                    })
                    ->limit(10)
                    ->get()
                    ->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'identifier' => $u->identifier ?? '',
                        'status' => $u->status ?? 'active',
                    ])
                    ->toArray();

            case 'enrollments':
                return Enrollment::with(['student', 'class.course'])
                    ->whereHas('student', function ($q) use ($query) {
                        $q->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('email', 'like', "%{$query}%");
                    })
                    ->limit(10)
                    ->get()
                    ->map(fn ($e) => [
                        'id' => $e->id,
                        'name' => $e->student?->name,
                        'class_code' => $e->class?->code,
                        'course_title' => $e->class?->course?->title,
                        'status' => $e->status,
                    ])
                    ->toArray();

            case 'classes':
                return ClassModel::where('code', 'like', "%{$query}%")
                    ->with('course')
                    ->limit(10)
                    ->get()
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'code' => $c->code,
                        'course_title' => $c->course?->title ?? 'N/A',
                        'status' => $c->status ?? 'active',
                    ])
                    ->toArray();

            case 'courses':
                return Course::where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('code', 'like', "%{$query}%");
                })
                    ->limit(10)
                    ->get()
                    ->map(fn ($c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'code' => $c->code,
                    'status' => $c->status,
                ])
                    ->toArray();

            default:
                return [];
        }
    }

    /**
     * Analytics for charts
     */
    public function getAnalytics(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:enrollment,classes,students',
            ]);

            $type = $validated['type'];

            // Gather base stats inline (avoid re-rendering the view)
            $stats = [
                'active_enrollments' => Enrollment::where('status', 'active')->count(),
                'pending_enrollments' => Enrollment::where('status', 'pending')->count(),
                'completed_enrollments' => Enrollment::where('status', 'completed')->count(),
                'dropped_enrollments' => Enrollment::where('status', 'dropped')->count(),
                'recent_enrollments' => Enrollment::with(['student', 'class.course'])
                    ->orderBy('created_at', 'desc')->limit(10)->get(),
                'total_classes' => ClassModel::count(),
                'active_classes' => ClassModel::where('status', 'active')->count(),
                'total_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))->count(),
                'active_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
                    ->where('status', 'active')->count(),
                'pending_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
                    ->where('status', 'pending')->count(),
            ];

            switch ($type) {
                case 'enrollment':
                    $analytics = [
                        'active' => $stats['active_enrollments'],
                        'pending' => $stats['pending_enrollments'],
                        'completed' => $stats['completed_enrollments'],
                        'dropped' => $stats['dropped_enrollments'],
                        'recent' => $stats['recent_enrollments']->map(fn ($e) => [
                            'date' => $e->created_at?->format('Y-m-d'),
                            'student' => $e->student?->name,
                            'course' => $e->class?->course?->title,
                        ])->toArray(),
                    ];
                    break;

                case 'classes':
                    $analytics = [
                        'total' => $stats['total_classes'],
                        'active' => $stats['active_classes'],
                        'by_course' => ClassModel::selectRaw('course_id, count(*) as total')
                            ->groupBy('course_id')
                            ->with('course')
                            ->get()
                            ->map(fn ($c) => [
                                'course' => $c->course?->title ?? 'N/A',
                                'total' => $c->total,
                            ])->toArray(),
                    ];
                    break;

                case 'students':
                    $analytics = [
                        'total' => $stats['total_students'],
                        'active' => $stats['active_students'],
                        'pending' => $stats['pending_students'],
                    ];
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch analytics: '.$e->getMessage(),
            ], 500);
        }
    }
}
