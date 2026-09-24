<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
        $modules = collect();
        $students = collect();

        if ($request->filled('search')) {
            $term = "%{$search}%";

            $courseQuery = Course::with(['creator', 'department', 'program'])
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

            $moduleQuery = Module::with(['course', 'creator'])
                ->where(function ($q) use ($term) {
                    $q->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('objectives', 'like', $term);
                });

            if (! auth()->user()->isAdmin()) {
                if (auth()->user()->isInstructor()) {
                    $courseIds = auth()->user()->classesInstructing()->pluck('course_id')->merge(
                        auth()->user()->coursesCreated()->pluck('id')
                    )->unique();
                    $moduleQuery->whereIn('course_id', $courseIds);
                } elseif (auth()->user()->isStudent()) {
                    $courseIds = auth()->user()->enrolledClasses()->pluck('course_id')->unique();
                    $moduleQuery->whereIn('course_id', $courseIds);
                }
            }

            $modules = $moduleQuery->limit(20)->get();

            if (auth()->user()->isAdmin()) {
                $studentQuery = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
                    ->where(function ($q) use ($term) {
                        $q->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhere('identifier', 'like', $term);
                    });

                $students = $studentQuery->limit(20)->get();
            }
        }

        return view('admin.search', compact('search', 'courses', 'classes', 'lessons', 'materials', 'modules', 'students'));
    }

    public function suggest(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Course::class);

        $query = trim((string) $request->input('query', $request->input('search', '')));
        if (mb_strlen($query) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $term = '%'.$query.'%';
        $results = [];

        Course::query()
            ->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('title', 'like', $term);
            })
            ->limit(6)
            ->get(['id', 'title', 'code', 'status'])
            ->each(function (Course $course) use (&$results) {
                $results[] = [
                    'id' => $course->id,
                    'entity' => 'course',
                    'title' => $course->title,
                    'code' => $course->code,
                    'status' => $course->status,
                    'url' => route('admin.courses.show', $course),
                ];
            });

        ClassModel::with('course:id,title')
            ->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('room', 'like', $term);
            })
            ->limit(5)
            ->get()
            ->each(function (ClassModel $class) use (&$results) {
                $results[] = [
                    'id' => $class->id,
                    'entity' => 'class',
                    'title' => $class->code,
                    'code' => $class->code,
                    'course_title' => $class->course?->title,
                    'url' => route('admin.classes.show', $class),
                ];
            });

        Module::with('course:id,title')
            ->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            })
            ->limit(4)
            ->get()
            ->each(function (Module $module) use (&$results) {
                $results[] = [
                    'id' => $module->id,
                    'entity' => 'module',
                    'title' => $module->title,
                    'course_title' => $module->course?->title,
                    'url' => $module->course
                        ? route('admin.courses.modules.show', [$module->course, $module])
                        : route('admin.search', ['search' => $module->title]),
                ];
            });

        Lesson::with('module.course:id,title')
            ->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            })
            ->limit(4)
            ->get()
            ->each(function (Lesson $lesson) use (&$results) {
                $results[] = [
                    'id' => $lesson->id,
                    'entity' => 'lesson',
                    'title' => $lesson->title,
                    'course_title' => $lesson->module?->course?->title,
                    'url' => $lesson->module?->course
                        ? route('admin.courses.show', $lesson->module->course)
                        : route('admin.search', ['search' => $lesson->title]),
                ];
            });

        User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('identifier', 'like', $term);
            })
            ->limit(5)
            ->get()
            ->each(function (User $student) use (&$results) {
                $results[] = [
                    'id' => $student->id,
                    'entity' => 'student',
                    'title' => trim($student->first_name.' '.$student->last_name),
                    'email' => $student->email,
                    'url' => route('admin.students.show', $student),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => array_slice($results, 0, 16),
        ]);
    }
}
