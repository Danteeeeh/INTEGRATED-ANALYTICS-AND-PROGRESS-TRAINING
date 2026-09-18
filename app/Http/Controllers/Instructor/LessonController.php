<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function index(Course $course, Module $module): View
    {
        $this->authorize('viewAny', Lesson::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);

        $lessons = Lesson::where('module_id', $module->id)
            ->with('module', 'materials')
            ->orderBy('position', 'asc')
            ->paginate(15);

        return view('instructor.courses.modules.lessons.index', compact('course', 'module', 'lessons'));
    }

    public function create(Course $course, Module $module): View
    {
        $this->authorize('create', Lesson::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);

        $lessonTypes = [
            Lesson::TYPE_TEXT => 'Text',
            Lesson::TYPE_VIDEO => 'Video',
            Lesson::TYPE_AUDIO => 'Audio',
            Lesson::TYPE_PDF => 'PDF',
            Lesson::TYPE_DOCUMENT => 'Document',
            Lesson::TYPE_PRESENTATION => 'Presentation',
            Lesson::TYPE_EXTERNAL => 'External',
        ];

        return view('instructor.courses.modules.lessons.create', compact('course', 'module', 'lessonTypes'));
    }

    public function store(Request $request, Course $course, Module $module): RedirectResponse
    {
        $this->authorize('create', Lesson::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'content' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:0',
            'position' => 'nullable|integer',
            'lesson_type' => 'required|string|in:text,video,audio,pdf,document,presentation,external',
            'external_url' => 'nullable|url|required_if:lesson_type,external',
            'is_required' => 'boolean',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date|after:availability_from',
            'status' => 'required|string|in:draft,published,archived',
        ]);

        $validated['module_id'] = $module->id;
        $validated['created_by'] = auth()->id();
        $validated['position'] = $validated['position'] ?? Lesson::where('module_id', $module->id)->max('position') + 1;
        $validated['is_required'] = $validated['is_required'] ?? false;

        Lesson::create($validated);

        return redirect()->route('instructor.courses.modules.lessons.index', [$course, $module])
            ->with('success', 'Lesson created successfully.');
    }

    public function show(Course $course, Module $module, Lesson $lesson): View
    {
        $this->authorize('view', $lesson);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);
        abort_if($lesson->module_id !== $module->id, 404);

        $lesson->load('module', 'materials');

        return view('instructor.courses.modules.lessons.show', compact('course', 'module', 'lesson'));
    }

    public function edit(Course $course, Module $module, Lesson $lesson): View
    {
        $this->authorize('update', $lesson);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);
        abort_if($lesson->module_id !== $module->id, 404);

        $lessonTypes = [
            Lesson::TYPE_TEXT => 'Text',
            Lesson::TYPE_VIDEO => 'Video',
            Lesson::TYPE_AUDIO => 'Audio',
            Lesson::TYPE_PDF => 'PDF',
            Lesson::TYPE_DOCUMENT => 'Document',
            Lesson::TYPE_PRESENTATION => 'Presentation',
            Lesson::TYPE_EXTERNAL => 'External',
        ];

        return view('instructor.courses.modules.lessons.edit', compact('course', 'module', 'lesson', 'lessonTypes'));
    }

    public function update(Request $request, Course $course, Module $module, Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $lesson);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);
        abort_if($lesson->module_id !== $module->id, 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'content' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:0',
            'position' => 'nullable|integer',
            'lesson_type' => 'required|string|in:text,video,audio,pdf,document,presentation,external',
            'external_url' => 'nullable|url|required_if:lesson_type,external',
            'is_required' => 'boolean',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date|after:availability_from',
            'status' => 'required|string|in:draft,published,archived',
        ]);

        $validated['is_required'] = $validated['is_required'] ?? false;

        $lesson->update($validated);

        return redirect()->route('instructor.courses.modules.lessons.index', [$course, $module])
            ->with('success', 'Lesson updated successfully.');
    }

    public function destroy(Course $course, Module $module, Lesson $lesson): RedirectResponse
    {
        $this->authorize('delete', $lesson);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);
        abort_if($lesson->module_id !== $module->id, 404);

        $lesson->delete();

        return redirect()->route('instructor.courses.modules.lessons.index', [$course, $module])
            ->with('success', 'Lesson deleted successfully.');
    }

    public function reorder(Request $request, Course $course, Module $module): RedirectResponse
    {
        $this->authorize('update', Lesson::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:lessons,id',
        ]);

        foreach ($validated['order'] as $position => $lessonId) {
            Lesson::where('id', $lessonId)
                ->where('module_id', $module->id)
                ->update(['position' => $position + 1]);
        }

        return redirect()->route('instructor.courses.modules.lessons.index', [$course, $module])
            ->with('success', 'Lessons reordered successfully.');
    }

    public function materials(Course $course, Module $module, Lesson $lesson): View
    {
        $this->authorize('view', $lesson);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);
        abort_if($lesson->module_id !== $module->id, 404);

        $lesson->load('materials.mediaFile');

        return view('instructor.courses.modules.lessons.materials', compact('course', 'module', 'lesson'));
    }

    public function addMaterial(Request $request, Course $course, Module $module, Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $lesson);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);
        abort_if($lesson->module_id !== $module->id, 404);

        $validated = $request->validate([
            'media_file_id' => 'required|exists:media_files,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'position' => 'nullable|integer|min:1',
            'is_required' => 'boolean',
            'access_until' => 'nullable|date',
        ]);

        $validated['lesson_id'] = $lesson->id;
        $validated['position'] = $validated['position'] ?? LessonMaterial::where('lesson_id', $lesson->id)->max('position') + 1;
        $validated['is_required'] = $validated['is_required'] ?? false;

        LessonMaterial::create($validated);

        return redirect()->route('instructor.courses.modules.lessons.materials', [$course, $module, $lesson])
            ->with('success', 'Material added successfully.');
    }

    public function updateMaterial(Request $request, Course $course, Module $module, Lesson $lesson, LessonMaterial $material): RedirectResponse
    {
        $this->authorize('update', $lesson);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);
        abort_if($lesson->module_id !== $module->id, 404);
        abort_if($material->lesson_id !== $lesson->id, 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'position' => 'nullable|integer|min:1',
            'is_required' => 'boolean',
            'access_until' => 'nullable|date',
        ]);

        $validated['is_required'] = $validated['is_required'] ?? false;

        $material->update($validated);

        return redirect()->route('instructor.courses.modules.lessons.materials', [$course, $module, $lesson])
            ->with('success', 'Material updated successfully.');
    }

    public function deleteMaterial(Course $course, Module $module, Lesson $lesson, LessonMaterial $material): RedirectResponse
    {
        $this->authorize('update', $lesson);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);
        abort_if($lesson->module_id !== $module->id, 404);
        abort_if($material->lesson_id !== $lesson->id, 404);

        $material->delete();

        return redirect()->route('instructor.courses.modules.lessons.materials', [$course, $module, $lesson])
            ->with('success', 'Material deleted successfully.');
    }

    public function setAllMaterialsRequired(Course $course, Module $module, Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $lesson);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);
        abort_if($lesson->module_id !== $module->id, 404);

        LessonMaterial::where('lesson_id', $lesson->id)->update(['is_required' => true]);

        return redirect()->route('instructor.courses.modules.lessons.materials', [$course, $module, $lesson])
            ->with('success', 'All materials set as required.');
    }

    public function setAllMaterialsOptional(Course $course, Module $module, Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $lesson);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);
        abort_if($lesson->module_id !== $module->id, 404);

        LessonMaterial::where('lesson_id', $lesson->id)->update(['is_required' => false]);

        return redirect()->route('instructor.courses.modules.lessons.materials', [$course, $module, $lesson])
            ->with('success', 'All materials set as optional.');
    }
}
