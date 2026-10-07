<?php

namespace Tests\Feature;

use App\Models\QuestionBank;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Test Bank's "Add Question" button was dead for instructors.
 *
 * The page filtered its bank picker on `created_by` and `is_shared`, but the
 * controller had selected only `id` and `title`, so both attributes were null on
 * every bank. The picker came back empty, the modal was never rendered, and
 * clicking the button did nothing at all.
 *
 * The picker must therefore list the banks the viewer may actually write to, and
 * the modal must exist whenever such a bank does.
 */
class TestBankAddQuestionPickerTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = $this->makeUser('instructor');
        $this->other = $this->makeUser('instructor');
    }

    private function makeUser(string $slug): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', $slug)->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    private function bank(User $owner, string $title, bool $shared = false): QuestionBank
    {
        return QuestionBank::create([
            'title' => $title,
            'created_by' => $owner->id,
            'is_shared' => $shared,
            'status' => 'active',
        ]);
    }

    /**
     * The HTML of the add-question modal, or '' when it was not rendered.
     *
     * Assertions have to be scoped to the modal: the same banks are also listed
     * in the page's filter toolbar, so a whole-page substring check passes even
     * when the picker itself is empty.
     */
    private function modalHtml(string $html): string
    {
        $start = strpos($html, 'id="add-question-modal"');

        if ($start === false) {
            return '';
        }

        // Run to the end of the bank picker, which is the part under test.
        $end = strpos($html, '</select>', $start);

        return $end === false ? substr($html, $start) : substr($html, $start, $end - $start);
    }

    /** The library and its routes are served under the viewer's own prefix. */
    private function indexRoute(User $user): string
    {
        return $user->isAdmin() ? 'admin.test_bank.index' : 'instructor.test_bank.index';
    }

    private function storeRoute(User $user): string
    {
        return $user->isAdmin() ? 'admin.test_bank.questions.store' : 'instructor.test_bank.questions.store';
    }

    private function indexFor(User $user): string
    {
        $response = $this->actingAs($user)->get(route($this->indexRoute($user)));

        $response->assertOk();

        return $this->modalHtml($response->getContent());
    }

    private function pickerFor(User $user): string
    {
        $modal = $this->indexFor($user);

        $start = strpos($modal, '<select id="lib-bank"');

        if ($start === false) {
            return '';
        }

        $end = strpos($modal, '</select>', $start);

        return $end === false ? substr($modal, $start) : substr($modal, $start, $end - $start);
    }

    public function test_the_add_question_modal_renders_when_the_viewer_owns_a_bank(): void
    {
        $this->bank($this->instructor, 'Biology Bank');

        $modal = $this->indexFor($this->instructor);

        $this->assertNotSame(
            '',
            $modal,
            'The Add Question button has nothing to open unless the modal renders.'
        );
    }

    public function test_the_viewers_own_bank_is_offered_in_the_picker(): void
    {
        $bank = $this->bank($this->instructor, 'Biology Bank');

        $picker = $this->pickerFor($this->instructor);

        $this->assertStringContainsString(
            'value="'.$bank->id.'"',
            $picker,
            'A bank the viewer owns must be selectable.'
        );
    }

    public function test_a_private_bank_owned_by_someone_else_is_not_offered(): void
    {
        $foreign = $this->bank($this->other, 'Their Private Bank');

        $picker = $this->pickerFor($this->instructor);

        $this->assertStringNotContainsString(
            'value="'.$foreign->id.'"',
            $picker,
            'Somebody else\'s private bank is not a place this question may be saved.'
        );
    }

    public function test_a_shared_bank_is_readable_but_not_writable_for_an_instructor(): void
    {
        $mine = $this->bank($this->instructor, 'Mine');
        $shared = $this->bank($this->other, 'Shared With Me', true);
        $private = $this->bank($this->other, 'Private');

        $picker = $this->pickerFor($this->instructor);

        $this->assertStringContainsString('value="'.$mine->id.'"', $picker);

        // Reading the library spans other people's banks, but a question cannot
        // be parked in one they do not maintain.
        $this->assertStringNotContainsString('value="'.$shared->id.'"', $picker);
        $this->assertStringNotContainsString('value="'.$private->id.'"', $picker);
    }

    public function test_an_admin_may_write_into_shared_banks(): void
    {
        $admin = $this->makeUser('admin');

        $shared = $this->bank($this->other, 'Shared With Me', true);
        $private = $this->bank($this->other, 'Private');

        $picker = $this->pickerFor($admin);

        $this->assertStringContainsString('value="'.$shared->id.'"', $picker);
        $this->assertStringNotContainsString('value="'.$private->id.'"', $picker);
    }

    public function test_the_picker_and_the_store_agree_on_what_is_writable(): void
    {
        $admin = $this->makeUser('admin');

        $shared = $this->bank($this->other, 'Shared With Me', true);
        $private = $this->bank($this->other, 'Private');

        $offered = str_contains($this->pickerFor($admin), 'value="'.$shared->id.'"');

        $accepted = true;
        $this->actingAs($admin)
            ->post(route($this->storeRoute($admin)), [
                'question_bank_id' => $shared->id,
                'question_type' => 'short_answer',
                'question_text' => 'Name the powerhouse of the cell.',
                'difficulty' => 'easy',
                'default_points' => 1,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertTrue(
            $offered === $accepted,
            'A bank the picker offers must be one the store accepts.'
        );

        // ...and the reverse: a bank the picker hides is one the store refuses.
        $this->actingAs($admin)
            ->post(route($this->storeRoute($admin)), [
                'question_bank_id' => $private->id,
                'question_type' => 'short_answer',
                'question_text' => 'Should be refused.',
                'difficulty' => 'easy',
                'default_points' => 1,
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->assertStringNotContainsString('value="'.$private->id.'"', $this->pickerFor($admin));
    }

    public function test_the_modal_is_withheld_when_there_is_nowhere_to_save(): void
    {
        // No bank of their own and nothing shared: offering the form would only
        // produce a submission that cannot be accepted.
        $modal = $this->indexFor($this->instructor);

        $this->assertSame('', $modal);
    }

    public function test_a_question_can_actually_be_added_from_the_picker(): void
    {
        $bank = $this->bank($this->instructor, 'Biology Bank');

        $this->actingAs($this->instructor)
            ->post(route($this->storeRoute($this->instructor)), [
                'question_bank_id' => $bank->id,
                'question_type' => 'multiple_choice',
                'question_text' => 'Which organelle produces ATP?',
                'difficulty' => 'medium',
                'default_points' => 2,
                'status' => 'active',
                'new' => [
                    ['text' => 'Mitochondrion', 'correct' => '1'],
                    ['text' => 'Ribosome', 'correct' => '0'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'question_bank_id' => $bank->id,
            'question_text' => 'Which organelle produces ATP?',
        ]);
    }
}
