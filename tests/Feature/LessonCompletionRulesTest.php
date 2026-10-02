<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\LessonMaterialProgress;
use App\Models\LessonProgress;
use App\Models\MediaFile;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Services\LessonCompletionService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonCompletionRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function seedRbac(): void
    {
        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    protected function makeUser(string $roleSlug): User
    {
        $this->seedRbac();

        $role = Role::where('slug', $roleSlug)->firstOrFail();

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    private function lessonWithRules(array $rules): Lesson
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        return Lesson::factory()->create([
            'module_id' => $module->id,
            'completion_rules' => $rules,
            'status' => 'published',
        ]);
    }

    private function enrollStudent(Course $course, User $student): Enrollment
    {
        $class = \App\Models\ClassModel::factory()->create(['course_id' => $course->id]);

        return Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);
    }

    public function test_no_rules_means_eligible(): void
    {
        $lesson = $this->lessonWithRules([]);
        $progress = LessonProgress::factory()->create([
            'lesson_id' => $lesson->id,
            'status' => 'in_progress',
        ]);

        $service = app(LessonCompletionService::class);

        $this->assertFalse($service->hasRules($lesson));
        $this->assertTrue($service->isEligible($lesson, $progress, []));
    }

    public function test_content_view_rule_blocks_until_lesson_opened(): void
    {
        $lesson = $this->lessonWithRules(['require_content_view' => true]);
        $progress = LessonProgress::factory()->create([
            'lesson_id' => $lesson->id,
            'status' => 'in_progress',
            'last_accessed_at' => null,
        ]);

        $service = app(LessonCompletionService::class);

        $this->assertFalse($service->isEligible($lesson, $progress, []));

        // Once opened (last_accessed_at set), content rule is met.
        $progress->forceFill(['last_accessed_at' => now()])->save();

        $this->assertTrue($service->isEligible($lesson, $progress, []));
    }

    public function test_all_materials_rule_requires_every_material(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'completion_rules' => ['require_all_materials' => true],
            'status' => 'published',
        ]);

        $mediaA = MediaFile::factory()->create();
        $mediaB = MediaFile::factory()->create();
        $materialA = LessonMaterial::factory()->create(['lesson_id' => $lesson->id, 'media_file_id' => $mediaA->id]);
        $materialB = LessonMaterial::factory()->create(['lesson_id' => $lesson->id, 'media_file_id' => $mediaB->id]);

        $progress = LessonProgress::factory()->create([
            'lesson_id' => $lesson->id,
            'status' => 'in_progress',
        ]);

        $service = app(LessonCompletionService::class);

        // No materials opened yet.
        $this->assertFalse($service->isEligible($lesson, $progress, []));

        // Only one of two materials opened.
        $this->assertFalse($service->isEligible($lesson, $progress, [$materialA->id]));

        // Both opened.
        $this->assertTrue($service->isEligible($lesson, $progress, [$materialA->id, $materialB->id]));
    }

    public function test_min_minutes_rule_blocks_until_time_accumulated(): void
    {
        $lesson = $this->lessonWithRules(['min_minutes' => 5]);
        $progress = LessonProgress::factory()->create([
            'lesson_id' => $lesson->id,
            'status' => 'in_progress',
            'total_seconds' => 0,
        ]);

        $service = app(LessonCompletionService::class);

        $this->assertFalse($service->isEligible($lesson, $progress, []));

        // 4 minutes: still short.
        $progress->forceFill(['total_seconds' => 240])->save();
        $this->assertFalse($service->isEligible($lesson, $progress, []));

        // 5 minutes: met.
        $progress->forceFill(['total_seconds' => 300])->save();
        $this->assertTrue($service->isEligible($lesson, $progress, []));
    }

    public function test_mark_material_accessed_is_idempotent(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        $media = MediaFile::factory()->create();
        $material = LessonMaterial::factory()->create(['lesson_id' => $lesson->id, 'media_file_id' => $media->id]);
        $student = $this->makeUser('student');

        $service = app(LessonCompletionService::class);

        $service->markMaterialAccessed($lesson, $material->id, $student->id);
        $service->markMaterialAccessed($lesson, $material->id, $student->id);

        $this->assertSame(
            1,
            LessonMaterialProgress::where('material_id', $material->id)
                ->where('student_id', $student->id)
                ->count()
        );
    }

    public function test_complete_route_rejects_when_rules_not_met(): void
    {
        $student = $this->makeUser('student');
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'completion_rules' => ['min_minutes' => 5],
            'status' => 'published',
        ]);
        $this->enrollStudent($course, $student);

        $response = $this->actingAs($student)->post(
            route('student.courses.modules.lessons.complete', [$course, $module, $lesson])
        );

        $response->assertSessionHas('error');
        $this->assertSame(
            'in_progress',
            LessonProgress::where('lesson_id', $lesson->id)->where('student_id', $student->id)->first()->status
        );
    }

    public function test_complete_route_succeeds_when_rules_met(): void
    {
        $student = $this->makeUser('student');
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'completion_rules' => ['min_minutes' => 5],
            'status' => 'published',
        ]);
        $this->enrollStudent($course, $student);

        LessonProgress::factory()->create([
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'status' => 'in_progress',
            'total_seconds' => 300,
        ]);

        $response = $this->actingAs($student)->post(
            route('student.courses.modules.lessons.complete', [$course, $module, $lesson])
        );

        $response->assertSessionHas('status', 'Lesson marked as completed!');
        $this->assertSame(
            'completed',
            LessonProgress::where('lesson_id', $lesson->id)->where('student_id', $student->id)->first()->status
        );
    }
}
