<?php

namespace App\Http\Controllers\Aa;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only course listing for AA Staff — view only, no editing.
 */
class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::withCount(['classes'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('aa.courses.index', compact('courses'));
    }

    public function show(Course $course): View
    {
        $course->loadCount(['classes']);

        $classes = $course->classes()
            ->with('instructor')
            ->withCount(['enrollments'])
            ->orderByDesc('enrollments_count')
            ->get();

        return view('aa.courses.show', compact('course', 'classes'));
    }
}
