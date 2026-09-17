<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Course::class);

        $search = $request->input('search');

        $courses = collect();
        $classes = collect();
        $lessons = collect();
        $materials = collect();

        if ($request->filled('search')) {
            $term = "%{$search}%";

            $courseQuery = Course::with(['category', 'creator'])
                ->where(function ($q) use ($term) {
                    $q->where('code', 'like', $term)
                        ->orWhere('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('objectives', 'like', $term)
                        ->orWhere('syllabus', 'like', $term)
                        ->orWhere('prerequisites', 'like', $term);
                });

            if (! auth()->user()->isAdmin()) {
                if (auth()->user()->isInstructor()) {
                    $courseIds = auth()->user()->classesInstructing()->pluck('course_id')->merge(
                        auth()->user()->coursesCreated()->pluck('id')
                    )->unique();
                    $courseQuery->whereIn('id', $courseIds);
                } elseif (auth()->user()->isStudent()) {
                    $courseIds = auth()->user()->enrolledClasses()->pluck('course_id')->unique();
                    $courseQuery->whereIn('id', $courseIds);
                }
            }

            $courses = $courseQuery->limit(20)->get();

            $classQuery = ClassModel::with(['course', 'instructor', 'academicPeriod'])
                ->where(function ($q) use ($term) {
                    $q->where('code', 'like', $term)
                        ->orWhere('schedule', 'like', $term)
                        ->orWhere('room', 'like', $term);
                });

            if (! auth()->user()->isAdmin()) {
                if (auth()->user()->isInstructor()) {
                    $classQuery->where('instructor_id', auth()->id());
                } elseif (auth()->user()->isStudent()) {
                    $classIds = auth()->user()->enrolledClasses()->pluck('classes.id')->unique();
                    $classQuery->whereIn('id', $classIds);
                }
            }

            $classes = $classQuery->limit(20)->get();

            $lessonQuery = Lesson::with(['module.course', 'creator'])
                ->where(function ($q) use ($term) {
                    $q->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('objectives', 'like', $term)
                        ->orWhere('content', 'like', $term);
                });

            if (! auth()->user()->isAdmin()) {
                if (auth()->user()->isInstructor()) {
                    $courseIds = auth()->user()->classesInstructing()->pluck('course_id')->unique();
                    $lessonQuery->whereHas('module', fn ($q) => $q->whereIn('course_id', $courseIds));
                } elseif (auth()->user()->isStudent()) {
                    $courseIds = auth()->user()->enrolledClasses()->pluck('course_id')->unique();
                    $lessonQuery->whereHas('module', fn ($q) => $q->whereIn('course_id', $courseIds));
                }
            }

            $lessons = $lessonQuery->limit(20)->get();

            $materialQuery = LessonMaterial::with(['lesson.module.course', 'mediaFile'])
                ->where(function ($q) use ($term) {
                    $q->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });

            if (! auth()->user()->isAdmin()) {
                if (auth()->user()->isInstructor()) {
                    $courseIds = auth()->user()->classesInstructing()->pluck('course_id')->unique();
                    $materialQuery->whereHas('lesson.module', fn ($q) => $q->whereIn('course_id', $courseIds));
                } elseif (auth()->user()->isStudent()) {
                    $courseIds = auth()->user()->enrolledClasses()->pluck('course_id')->unique();
                    $materialQuery->whereHas('lesson.module', fn ($q) => $q->whereIn('course_id', $courseIds));
                }
            }

            $materials = $materialQuery->limit(20)->get();
        }

        return view('admin.search', compact('search', 'courses', 'classes', 'lessons', 'materials'));
    }
}
