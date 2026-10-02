<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\User;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

class ContentCreationWorkflowTest extends TestCase
{
    use CreatesLmsUsers;

    public function test_admin_can_create_assignment_with_form_payload(): void
    {
        $admin = $this->makeUser('admin');
        $instructor = $this->makeUser('instructor');
        $course = Course::factory()->create(['created_by' => $instructor->id]);
        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.assignments.store'), [
                'class_id' => $class->id,
                'title' => 'Midterm Project',
                'instructions' => 'Submit the completed project file.',
                'points' => 100,
                'submission_type' => Assignment::TYPE_FILE,
                'status' => Assignment::STATUS_DRAFT,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'class_id' => $class->id,
            'title' => 'Midterm Project',
            'instructions' => 'Submit the completed project file.',
            'status' => Assignment::STATUS_DRAFT,
        ]);
    }

    public function test_admin_can_create_discussion_with_form_payload(): void
    {
        $admin = $this->makeUser('admin');
        $course = Course::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)
            ->post(route('admin.discussions.store'), [
                'course_id' => $course->id,
                'title' => 'Course Q and A',
                'description' => 'Share questions about the lesson here.',
                'discussion_type' => Discussion::TYPE_QA,
                'status' => Discussion::STATUS_PUBLISHED,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('discussions', [
            'course_id' => $course->id,
            'title' => 'Course Q and A',
            'description' => 'Share questions about the lesson here.',
            'discussion_type' => Discussion::TYPE_QA,
            'status' => Discussion::STATUS_PUBLISHED,
        ]);
    }
}
