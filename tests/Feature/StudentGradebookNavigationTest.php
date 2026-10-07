<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "My Grades" in the sidebar went to the class picker, whose cards only had an
 * "Open" button. From there the gradebook was reachable only by opening the
 * class and finding it among a row of resource cards — so the link looked dead.
 *
 * Every enrolled class must offer a direct route to its gradebook.
 */
class StudentGradebookNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create(['title' => 'Mathematics in the Modern World']);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(5),
        ]);

        $this->actingAs($this->student);
    }

    public function test_my_grades_reaches_a_page_offering_the_gradebook(): void
    {
        // Follow the sidebar link the student actually clicks.
        $this->get(route('student.classes.index'))
            ->assertOk()
            ->assertSee(
                route('student.classes.gradebook.index', $this->class),
                false,
                'The page behind "My Grades" must offer a direct link to the gradebook.'
            );
    }

    public function test_each_enrolled_class_card_links_to_its_own_gradebook(): void
    {
        $second = ClassModel::factory()->create([
            'course_id' => Course::factory()->create(['title' => 'Information Management'])->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'class_id' => $second->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(5),
        ]);

        $response = $this->get(route('student.classes.index'));

        $response->assertOk();
        $response->assertSee(route('student.classes.gradebook.index', $this->class), false);
        $response->assertSee(route('student.classes.gradebook.index', $second), false);
    }

    public function test_the_gradebook_itself_still_loads(): void
    {
        $this->get(route('student.classes.gradebook.index', $this->class))->assertOk();
    }

    public function test_a_dropped_class_is_not_offered_as_a_gradebook(): void
    {
        $dropped = ClassModel::factory()->create([
            'course_id' => Course::factory()->create(['title' => 'Dropped Subject'])->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'class_id' => $dropped->id,
            'student_id' => $this->student->id,
            'status' => 'dropped',
            'enrolled_at' => now()->subDays(30),
        ]);

        $this->get(route('student.classes.index'))
            ->assertOk()
            ->assertDontSee(route('student.classes.gradebook.index', $dropped), false);
    }
}