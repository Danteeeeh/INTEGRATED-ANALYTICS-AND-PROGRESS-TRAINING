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
 * Instructor "Performance Analytics" reported a Class Average and Grade
 * Distribution blended across every class the instructor teaches, and its
 * "This Month" period selector was validated and then never applied.
 *
 * The panel must be able to scope to one class, and the period filter must
 * actually change the window it measures.
 */
class InstructorAnalyticsScopingTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private ClassModel $mathClass;

    private ClassModel $itClass;

    private ClassModel $notMine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->mathClass = $this->classFor('Mathematics in the Modern World');
        $this->itClass = $this->classFor('Information Management');
        $this->notMine = $this->classFor('Someone Elses Class');

        $this->actingAs($this->instructor);
    }

    private function classFor(string $title): ClassModel
    {
        return ClassModel::factory()->create([
            'course_id' => Course::factory()->create(['title' => $title])->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);
    }

    private function gradeFor(ClassModel $class, User $student, float $percent, int $daysAgo = 2): Grade
    {
        Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'class_id' => $class->id],
            ['status' => 'active', 'enrolled_at' => now()->subDays(30)]
        );

        $item = GradeItem::create([
            'class_id' => $class->id,
            'title' => 'Item '.$percent,
            'max_points' => 100,
            'factor' => 1,
            'item_type' => 'exam',
            'position' => 0,
            'is_released' => true,
        ]);

        return Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $student->id,
            'points' => $percent,
            'score_percent' => $percent,
            'graded_at' => now()->subDays($daysAgo),
        ]);
    }

    private function student(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    /** @return array<string, mixed> */
    private function analytics(array $query): array
    {
        $response = $this->getJson(
            route('instructor.dashboard.analytics').'?'.http_build_query($query)
        );

        $response->assertOk();

        return $response->json('data');
    }

    public function test_class_filter_narrows_the_average_to_one_class(): void
    {
        $this->gradeFor($this->mathClass, $this->student(), 50);
        $this->gradeFor($this->mathClass, $this->student(), 70);
        $this->gradeFor($this->itClass, $this->student(), 100);

        $all = $this->analytics(['period' => 'year', 'type' => 'performance']);
        $scoped = $this->analytics([
            'period' => 'year',
            'type' => 'performance',
            'class_id' => $this->mathClass->id,
        ]);

        $this->assertEqualsWithDelta(60.0, $scoped['average_grade'], 0.01);
        $this->assertNotEquals(
            $all['average_grade'],
            $scoped['average_grade'],
            'Scoping to one class must change the reported average.'
        );
    }

    public function test_grade_distribution_is_scoped_to_the_selected_class(): void
    {
        $this->gradeFor($this->mathClass, $this->student(), 95); // A
        $this->gradeFor($this->itClass, $this->student(), 55);   // F

        $scoped = $this->analytics([
            'period' => 'year',
            'type' => 'performance',
            'class_id' => $this->mathClass->id,
        ]);

        $dist = $scoped['grade_distribution'];
        $this->assertSame(1, $dist['A'] ?? 0, 'The other class\'s F must not appear.');
        $this->assertSame(0, $dist['F'] ?? 0);
    }

    public function test_class_from_another_instructor_is_excluded(): void
    {
        $this->gradeFor($this->notMine, $this->student(), 40);

        $data = $this->analytics(['period' => 'year', 'type' => 'performance']);

        // notMine is assigned to this instructor by the fixture, so use a
        // genuinely foreign class instead.
        $foreign = ClassModel::factory()->create([
            'course_id' => Course::factory()->create(['title' => 'Not Taught'])->id,
            'status' => 'active',
        ]);

        $this->gradeFor($foreign, $this->student(), 30);

        $after = $this->analytics(['period' => 'year', 'type' => 'performance']);

        $this->assertGreaterThan(0, $after['graded_enrollments'] ?? 0);
        $this->assertNotContains(
            'Not Taught',
            array_column($after['class_performance'] ?? [], 'course')
        );
    }

    /**
     * The "This Month" selector is validated and then discarded, so every
     * period returns identical numbers.
     */
    public function test_period_filter_actually_narrows_the_window(): void
    {
        $this->gradeFor($this->mathClass, $this->student(), 90, 2);    // recent
        $this->gradeFor($this->mathClass, $this->student(), 40, 200);  // long ago

        $month = $this->analytics(['period' => 'month', 'type' => 'performance']);
        $year = $this->analytics(['period' => 'year', 'type' => 'performance']);

        $this->assertNotSame(
            $month['graded_enrollments'],
            $year['graded_enrollments'],
            'Choosing a different period must change the result, otherwise the filter is decorative.'
        );

        $this->assertGreaterThan(
            $year['average_grade'],
            $month['average_grade'],
            '"This month" should only see the recent grade (90), not the 200-day-old one (40).'
        );
    }

    public function test_dashboard_offers_a_class_selector_for_taught_classes(): void
    {
        $response = $this->get(route('instructor.dashboard'));

        $response->assertOk();
        $response->assertSee('analyticsClass', false);
        $this->assertStringContainsString('value="'.$this->mathClass->id.'"', $response->getContent());
        $this->assertStringContainsString('value="'.$this->itClass->id.'"', $response->getContent());
    }

public function test_class_taught_by_someone_else_is_rejected(): void
    {
        $foreign = ClassModel::factory()->create([
            'course_id' => Course::factory()->create(['title' => 'Not My Class'])->id,
            'instructor_id' => User::factory()->create([
                'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
                'status' => 'active',
            ])->id,
            'status' => 'active',
        ]);

        $this->getJson(
            route('instructor.dashboard.analytics')
            .'?period=year&type=performance&class_id='.$foreign->id
        )->assertStatus(422);

        // Their own class is still fine.
        $this->getJson(
            route('instructor.dashboard.analytics')
            .'?period=year&type=performance&class_id='.$this->mathClass->id
        )->assertOk();
    }
}