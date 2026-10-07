<?php

namespace Tests\Feature;

use App\Models\QuestionBank;
use App\Models\QuestionCategory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * A bank titled "CS101 ÃƒÂ¢Ãâ€šÂ¬Ãâ‚¬Â Questions" instead of "CS101 — Questions".
 *
 * The title was written through a UTF-8 -> Windows-1252 -> UTF-8 round trip, so
 * every non-ASCII byte survived but was re-labelled as a Windows-1252 character.
 * Reversing that labelling restores the original text exactly.
 */
class RepairMojibakeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class]);
    }

    /** Reproduce the bug: each UTF-8 byte re-encoded as a Windows-1252 character. */
    private function corrupt(string $value): string
    {
        return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }

    public function test_dry_run_reports_but_changes_nothing(): void
    {
        $bank = QuestionBank::factory()->create([
            'title' => $this->corrupt('CS101 — Questions'),
        ]);

        $before = $bank->fresh()->title;

        Artisan::call('lms:repair-mojibake');

        $this->assertStringContainsString('Dry run', Artisan::output());
        $this->assertSame($before, $bank->fresh()->title, 'Dry run must not write.');
    }

    public function test_apply_repairs_a_re_encoded_bank_title(): void
    {
        QuestionBank::factory()->create(['title' => $this->corrupt('CS101 — Questions')]);

        Artisan::call('lms:repair-mojibake --apply');

        $this->assertSame('CS101 — Questions', QuestionBank::first()->title);
    }

    public function test_apply_repairs_a_re_encoded_category_name(): void
    {
        QuestionCategory::factory()->create(['name' => $this->corrupt('Categoría 1')]);

        Artisan::call('lms:repair-mojibake --apply');

        $this->assertSame('Categoría 1', QuestionCategory::first()->name);
    }

    public function test_apply_repairs_text_that_was_encoded_twice(): void
    {
        QuestionBank::factory()->create([
            'title' => $this->corrupt($this->corrupt('CS101 — Questions')),
        ]);

        Artisan::call('lms:repair-mojibake --apply');

        $this->assertSame('CS101 — Questions', QuestionBank::first()->title);
    }

    public function test_apply_repairs_curly_quotes_and_bullets(): void
    {
        QuestionBank::factory()->create(['title' => $this->corrupt('Teacher’s Notes • Term 1')]);

        Artisan::call('lms:repair-mojibake --apply');

        $this->assertSame('Teacher’s Notes • Term 1', QuestionBank::first()->title);
    }

    public function test_clean_text_is_left_alone(): void
    {
        // ASCII, legitimately accented, and non-latin text must all survive.
        QuestionBank::factory()->create(['title' => 'Midterm Biology Questions']);
        QuestionBank::factory()->create(['title' => 'Biología Cellular']);
        QuestionBank::factory()->create(['title' => '量子力学']);
        QuestionBank::factory()->create(['title' => 'É Reliance Café']);

        Artisan::call('lms:repair-mojibake --apply');

        $titles = QuestionBank::pluck('title')->all();

        $this->assertContains('Midterm Biology Questions', $titles);
        $this->assertContains('Biología Cellular', $titles);
        $this->assertContains('量子力学', $titles);
        $this->assertContains('É Reliance Café', $titles);
    }

    public function test_command_reports_nothing_to_do_on_clean_data(): void
    {
        QuestionBank::factory()->create(['title' => 'CS101 Questions']);

        Artisan::call('lms:repair-mojibake --apply');

        $this->assertStringContainsString('Nothing to repair', Artisan::output());
    }
}