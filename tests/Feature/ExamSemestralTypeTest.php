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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Exam Type was Midterm / Final / Comprehensive / Module / Other, which does not
 * match how a semester is actually assessed. It is now Prelim, Midterm, Finals.
 */
class ExamSemestralTypeTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create(['created_by' => $this->instructor->id]);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
        ]);

        $this->actingAs($this->instructor);
    }

    private function exam(string $type, string $title = 'Exam'): Exam
    {
        return Exam::create([
            'class_id' => $this->class->id,
            'title' => $title,
            'slug' => str($title)->slug()->append('-'.uniqid())->value(),
            'exam_type' => $type,
            'created_by' => $this->instructor->id,
            'status' => 'draft',
        ]);
    }

    public function test_the_offered_types_are_exactly_the_three_semestral_exams(): void
    {
        $this->assertSame(
            ['prelim' => 'Prelim', 'midterm' => 'Midterm', 'finals' => 'Finals'],
            Exam::typeOptions()
        );
    }

    public function test_legacy_values_are_still_accepted_so_old_exams_stay_editable(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        foreach (Exam::acceptedTypes() as $type) {
            $exam = $this->exam($type);

            $this->put(route('admin.exams.update', $exam), [
                'title' => 'Renamed',
                'class_id' => $this->class->id,
                'exam_type' => $type,
                'duration_minutes' => 60,
                'passing_score_percent' => 60,
                'result_visibility' => 'after_grading',
                'status' => 'draft',
            ])->assertSessionHasNoErrors();

            $this->assertSame($type, $exam->fresh()->exam_type);
        }
    }

    public function test_each_semestral_type_persists_and_labels_itself(): void
    {
        $expected = [
            Exam::TYPE_PRELIM => 'Prelim Exam',
            Exam::TYPE_MIDTERM => 'Midterm Exam',
            Exam::TYPE_FINALS => 'Finals Exam',
        ];

        foreach ($expected as $type => $label) {
            $exam = $this->exam($type);

            $this->assertSame($type, $exam->fresh()->exam_type);
            $this->assertSame($label, $exam->getTypeLabel());
        }
    }

    public function test_scopes_select_the_matching_exams(): void
    {
        $this->exam(Exam::TYPE_PRELIM, 'P');
        $this->exam(Exam::TYPE_MIDTERM, 'M');
        $this->exam(Exam::TYPE_FINALS, 'F');

        $this->assertSame(1, Exam::prelim()->count());
        $this->assertSame(1, Exam::midterm()->count());
        $this->assertSame(1, Exam::finals()->count());
    }

    /**
     * Old rows named "final" or "comprehensive" have to land on "finals".
     * Without this the narrowed column would reject or silently blank them.
     */
    public function test_migration_maps_legacy_final_and_comprehensive_onto_finals(): void
    {
        // Roll the column back to the pre-migration enum so the legacy rows can
        // actually be written, then run the migration forward.
        $migration = require database_path('migrations/2026_10_10_100000_make_exam_types_semestral.php');
        $migration->down();

        DB::table('exams')->delete();

        $this->exam('midterm', 'Midterm kept');
        $this->exam('final', 'Legacy final');
        $this->exam('comprehensive', 'Legacy comprehensive');

        $migration->up();

        $types = DB::table('exams')->pluck('exam_type')->all();

        sort($types);

        $this->assertSame(['finals', 'finals', 'midterm'], $types);

        // A MySQL enum coerces a now-illegal value into '', which reads as a
        // blank type rather than an obvious error.
        $this->assertSame(0, DB::table('exams')->where('exam_type', '')->count());
    }

    public function test_instructor_create_form_offers_only_the_semestral_types(): void
    {
        $response = $this->get(route('instructor.exams.create', ['class' => $this->class->id]));

        $response->assertOk();
        $response->assertSee('Prelim', false);
        $response->assertSee('Midterm', false);
        $response->assertSee('Finals', false);
        $response->assertDontSee('>Comprehensive<', false);
    }

    public function test_instructor_list_filter_offers_only_the_semestral_types(): void
    {
        $response = $this->get(route('instructor.exams.index'));

        $response->assertOk();
        $response->assertDontSee('>Comprehensive<', false);
        $response->assertDontSee('value="module"', false);
    }
}