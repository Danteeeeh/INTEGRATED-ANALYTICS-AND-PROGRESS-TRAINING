<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Every exam saved before the Type field existed is stamped "module", a value
 * no instructor chose. Relabelling them is a judgement call, so the command
 * must never guess and must apply exactly the mapping it is given.
 */
class RelabelExamsTest extends TestCase
{
    use RefreshDatabase;

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
        ]);

        $this->actingAs($instructor);
    }

    private function exam(string $title, string $type): Exam
    {
        return Exam::create([
            'class_id' => $this->class->id,
            'title' => $title,
            'slug' => str($title)->slug()->append('-'.uniqid())->value(),
            'exam_type' => $type,
            'created_by' => auth()->id(),
            'status' => 'draft',
        ]);
    }

    public function test_it_lists_legacy_typed_exams_without_changing_them(): void
    {
        $exam = $this->exam('Prelim Exam', Exam::TYPE_MODULE);

        Artisan::call('lms:relabel-exams');

        $output = Artisan::output();

        $this->assertStringContainsString('Prelim Exam', $output);
        $this->assertStringContainsString('Dry run', $output);
        $this->assertSame(Exam::TYPE_MODULE, $exam->fresh()->exam_type);
    }

    public function test_a_bulk_map_relabels_by_title_and_leaves_the_rest(): void
    {
        $prelim = $this->exam('Prelim Exam', Exam::TYPE_MODULE);
        $finals = $this->exam('Final Exam', Exam::TYPE_MODULE);
        $unclear = $this->exam('Pop Quiz', Exam::TYPE_MODULE);

        Artisan::call('lms:relabel-exams --map="Prelim Exam:prelim,Final Exam:finals" --apply');

        $this->assertSame(Exam::TYPE_PRELIM, $prelim->fresh()->exam_type);
        $this->assertSame(Exam::TYPE_FINALS, $finals->fresh()->exam_type);
        $this->assertSame(
            Exam::TYPE_MODULE,
            $unclear->fresh()->exam_type,
            'An exam whose title matches nothing must keep its current type.'
        );
    }

    public function test_the_longest_matching_rule_wins(): void
    {
        // "Exam" must not swallow "Final Exam" depending on map order.
        $exam = $this->exam('Final Exam', Exam::TYPE_MODULE);

        Artisan::call('lms:relabel-exams --map="Exam:midterm,Final Exam:finals" --apply');

        $this->assertSame(Exam::TYPE_FINALS, $exam->fresh()->exam_type);
    }

    public function test_single_exam_can_be_relabelled(): void
    {
        $exam = $this->exam('Midterm', Exam::TYPE_MODULE);

        Artisan::call('lms:relabel-exams --exam='.$exam->id.' --map="Midterm:midterm" --apply');

        $this->assertSame(Exam::TYPE_MIDTERM, $exam->fresh()->exam_type);
    }

    public function test_an_unknown_target_type_is_rejected_rather_than_written(): void
    {
        $exam = $this->exam('Prelim Exam', Exam::TYPE_MODULE);

        Artisan::call('lms:relabel-exams --map="Prelim Exam:banana" --apply');

        $this->assertSame(Exam::TYPE_MODULE, $exam->fresh()->exam_type);
    }

    public function test_already_semestral_exams_are_not_touched(): void
    {
        $exam = $this->exam('Prelim Exam', Exam::TYPE_PRELIM);

        Artisan::call('lms:relabel-exams --map="Prelim Exam:finals" --apply');

        $this->assertSame(
            Exam::TYPE_PRELIM,
            $exam->fresh()->exam_type,
            'Only legacy-typed exams are in scope for relabelling.'
        );
    }

    public function test_it_reports_nothing_to_do_when_no_legacy_exams_exist(): void
    {
        $this->exam('Prelim Exam', Exam::TYPE_PRELIM);

        Artisan::call('lms:relabel-exams --apply');

        $this->assertStringContainsString('nothing to relabel', Artisan::output());
    }
}