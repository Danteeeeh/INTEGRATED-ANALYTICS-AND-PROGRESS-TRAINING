<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPanelCapTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_cap_partial_renders_hint_and_view_all_only_after_exceeding_the_cap(): void
    {
        $capped = view('partials.dashboard-panel-cap', [
            'count' => 6,
            'shown' => 5,
            'total' => 6,
            'href' => '/records',
            'viewAllUrl' => '/records',
            'viewAllLabel' => 'All records',
            'viewAllOnlyWhenCapped' => true,
        ])->render();

        $this->assertSame([
            ['text' => 'Showing 5 of 6', 'href' => '/records'],
            ['text' => 'All records', 'href' => '/records'],
        ], $this->renderedLinks($capped));

        $atBoundary = view('partials.dashboard-panel-cap', [
            'count' => 5,
            'shown' => 5,
            'total' => 5,
            'href' => '/records',
            'viewAllUrl' => '/records',
            'viewAllLabel' => 'All records',
            'viewAllOnlyWhenCapped' => true,
        ])->render();

        $this->assertSame([], $this->renderedLinks($atBoundary));
        $this->assertStringNotContainsString('Showing 5 of 5', $atBoundary);
        $this->assertStringNotContainsString('All records', $atBoundary);
    }

    public function test_faculty_header_cap_hints_stay_hidden_at_the_boundary_and_appear_when_exceeded(): void
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        $course = Course::factory()->create(['code' => 'HINTCAP101', 'status' => 'published']);
        $class = ClassModel::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'code' => 'HINTCAP101-A',
            'status' => 'active',
        ]);

        $students = collect(range(1, 5))->map(function ($index) use ($class) {
            $student = User::factory()->student()->create(['status' => 'active']);

            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'status' => 'active',
                'enrolled_at' => now()->subMinutes($index),
            ]);

            return $student;
        });

        $gradeItem = GradeItem::create([
            'class_id' => $class->id,
            'title' => 'Cap hint grade',
            'max_points' => 100,
            'factor' => 1,
            'item_type' => GradeItem::TYPE_ASSIGNMENT,
            'is_released' => true,
            'released_at' => now(),
        ]);

        foreach ($students->take(4) as $student) {
            $this->failStudent($gradeItem, $student, $instructor);
        }

        $assignment = Assignment::create([
            'class_id' => $class->id,
            'title' => 'Cap hint assignment',
            'slug' => 'cap-hint-assignment-'.uniqid(),
            'status' => Assignment::STATUS_PUBLISHED,
            'due_date' => now()->addDay(),
            'created_by' => $instructor->id,
        ]);

        foreach ($students as $index => $student) {
            $this->submitAssignment($assignment, $student, 1, $index);
        }

        // Four at-risk students and five submitted items are exactly at the
        // four- and five-row caps, so neither hint should render.
        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));
        $response->assertOk();
        $this->assertSame([], $this->renderedShowingHints($response->getContent()));

        // Cross each cap by one.
        $this->failStudent($gradeItem, $students->last(), $instructor);
        $this->submitAssignment($assignment, $students->first(), 2, 6);

        $this->actingAs($instructor)
            ->post(route('instructor.dashboard.clear-cache'))
            ->assertRedirect();

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));
        $response->assertOk();
        $this->assertSame([
            ['text' => 'Showing 4 of 5', 'href' => route('instructor.dashboard.at-risk')],
            ['text' => 'Showing 5 of 6', 'href' => route('instructor.dashboard.to-check')],
        ], $this->renderedShowingHints($response->getContent()));
    }

    public function test_student_header_cap_hint_stays_hidden_at_the_boundary_and_appears_when_exceeded(): void
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        $student = User::factory()->student()->create(['status' => 'active']);

        foreach (range(1, 4) as $index) {
            $this->enrollInNewCourse($student, $instructor, $index);
        }

        // Four courses are exactly at the My courses cap, so the hint stays hidden.
        $response = $this->actingAs($student)->get(route('student.dashboard'));
        $response->assertOk();
        $this->assertSame([], $this->renderedShowingHints($response->getContent()));

        $this->enrollInNewCourse($student, $instructor, 5);

        $response = $this->actingAs($student)->get(route('student.dashboard'));
        $response->assertOk();
        $this->assertSame([
            ['text' => 'Showing 4 of 5', 'href' => route('student.courses.index')],
        ], $this->renderedShowingHints($response->getContent()));
    }

    private function failStudent(GradeItem $gradeItem, User $student, User $instructor): void
    {
        Grade::create([
            'grade_item_id' => $gradeItem->id,
            'student_id' => $student->id,
            'points' => 30,
            'score_percent' => 30,
            'letter_grade' => 'F',
            'graded_by' => $instructor->id,
            'graded_at' => now(),
        ]);
    }

    private function submitAssignment(Assignment $assignment, User $student, int $attempt, int $index): void
    {
        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'attempt_number' => $attempt,
            'submission_text' => 'Cap hint submission '.$index,
            'submitted_at' => now()->subHours($index + 1),
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
        ]);
    }

    private function enrollInNewCourse(User $student, User $instructor, int $index): void
    {
        $course = Course::factory()->create([
            'code' => 'CAP'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'status' => 'published',
        ]);

        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'code' => $course->code.'-A',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'enrolled_at' => now()->subMinutes($index),
        ]);
    }

    /**
     * Return only the shared header "Showing X of Y" hint anchors.
     */
    private function renderedShowingHints(string $html): array
    {
        return array_values(array_filter(
            $this->renderedLinks($html),
            fn (array $link) => preg_match('/^Showing \d+ of \d+$/', $link['text']) === 1
        ));
    }

    /**
     * Return the rendered anchors as text/href pairs.
     */
    private function renderedLinks(string $html): array
    {
        $previousErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        $xpath = new \DOMXPath($dom);

        $links = [];
        foreach ($xpath->query('//a') as $anchor) {
            $links[] = [
                'text' => trim($anchor->textContent),
                'href' => $anchor->getAttribute('href'),
            ];
        }

        return $links;
    }
}
