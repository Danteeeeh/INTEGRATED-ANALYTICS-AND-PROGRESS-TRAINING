<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /instructor/classes/{class}/gradebook/students/{student} returned 500:
 * "Cannot end a section without first starting one".
 *
 * The view carried three stray blocks of
 * "@section('scripts') @show @push('scripts') @show" - not valid Blade - which
 * opened sections nothing ever closed, leaving the single @endsection at the
 * bottom unmatched.
 */
class InstructorGradebookStudentPageTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $student;

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create(['status' => 'published']);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(10),
        ]);

        $item = GradeItem::create([
            'class_id' => $this->class->id,
            'title' => 'Prelim Exam',
            'item_type' => 'exam',
            'max_points' => 100,
            'is_released' => true,
            'released_at' => now()->subDay(),
            'position' => 0,
        ]);

        Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $this->student->id,
            'points' => 82,
            'score_percent' => 82,
            'graded_at' => now()->subDay(),
        ]);
    }

    public function test_student_gradebook_page_renders(): void
    {
        $this->actingAs($this->instructor)
            ->get(route('instructor.classes.gradebook.student', [$this->class, $this->student]))
            ->assertOk();
    }

    public function test_student_gradebook_page_renders_without_any_grades(): void
    {
        // A student with nothing graded must still render, not explode.
        $empty = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $empty->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(10),
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.classes.gradebook.student', [$this->class, $empty]))
            ->assertOk();
    }

    /**
     * Guards the section structure directly, so the page cannot silently
     * regress to unbalanced @section/@endsection again.
     */
    public function test_view_sections_are_balanced(): void
    {
        $view = file_get_contents(
            resource_path('views/instructor/gradebook/student.blade.php')
        );

        preg_match_all('/@section\(/', $view, $opens);
        preg_match_all('/@endsection/', $view, $closes);

        // "@section('title', '...')" is the inline form and needs no @endsection,
        // so it is not counted as an unbalanced open.
        $inline = preg_match_all("/@section\('[^']+',\s*'/", $view);

        $this->assertSame(
            count($opens[0]) - $inline,
            count($closes[0]),
            'Every block @section must have a matching @endsection.'
        );
    }
}