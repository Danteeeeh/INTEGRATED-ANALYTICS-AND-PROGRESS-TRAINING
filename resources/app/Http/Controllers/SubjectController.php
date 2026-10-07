<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\User;
use App\Services\SubjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The "LMS Subjects" list.
 *
 * One controller serves all three panels because the layout, columns and
 * filters are identical — only the row scope differs, which is decided inside
 * SubjectService. That keeps the three sidebars in sync for free.
 */
class SubjectController extends Controller
{
    public function __construct(private SubjectService $subjects) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'search',
            'subject_status',
            'enrollment_status',
            'section_id',
            'program_id',
            'period_id',
        ]);

        $subjects = $this->subjects->paginateForUser(
            $request->user(),
            $filters,
            (int) $request->input('per_page', 10) ?: 10,
        );

        $options = $this->subjects->filterOptions();

        // Each panel has its own thin wrapper so the shared partial inherits the
        // right layout and nav highlight. The route prefix is passed through
        // because route names are role-scoped (admin.subjects.* vs
        // instructor.subjects.*) while the partial is shared.
        [$view, $prefix] = match (true) {
            $request->user()->isAdmin() => ['admin.subjects.index', 'admin.'],
            $request->user()->isInstructor() => ['instructor.subjects.index', 'instructor.'],
            default => ['student.subjects.index', 'student.'],
        };

        return view($view, [
            'subjects' => $subjects,
            'filters' => $filters,
            'sections' => $options['sections'],
            'periods' => $options['periods'],
            'routePrefix' => $prefix,
            'canUnverify' => $request->user()->isAdmin(),
        ]);
    }

    /**
     * "Check Available Scores" — how complete a subject's grading is.
     */
    public function scores(Request $request, ClassModel $class): View
    {
        $this->authorizeSubjectAccess($request->user(), $class);

        $report = $this->subjects->scoreAvailability($class);

        $view = match (true) {
            $request->user()->isAdmin() => 'admin.subjects.scores',
            $request->user()->isInstructor() => 'instructor.subjects.scores',
            default => 'student.subjects.scores',
        };

        return view($view, $report + [
            'routePrefix' => $request->route()->getName() === 'admin.subjects.scores' ? 'admin.' : (
                $request->route()->getName() === 'instructor.subjects.scores' ? 'instructor.' : 'student.'
            ),
            'canUnverify' => $request->user()->isAdmin(),
        ]);
    }

    /**
     * Sign a subject off as final.
     */
    public function verify(Request $request, ClassModel $class): RedirectResponse
    {
        $user = $request->user();

        $this->authorizeSubjectAccess($user, $class, true);

        // Verifying a subject whose grades are still incomplete would hand the
        // registrar a record that is wrong on arrival, so it is refused with a
        // reason instead of silently allowed.
        $report = $this->subjects->scoreAvailability($class);

        if (! $report['is_complete']) {
            return back()->with(
                'error',
                'Cannot verify yet — '.implode(' ', $report['blockers'])
            );
        }

        $class->markSubjectVerified($user->id);

        return back()->with('status', "{$class->code} verified. It can now be included in an academic record.");
    }

    /**
     * Send a subject back to draft so grades can be corrected.
     */
    public function unverify(Request $request, ClassModel $class): RedirectResponse
    {
        $user = $request->user();

        $this->authorizeSubjectAccess($user, $class, true);

        if (! $user->isAdmin()) {
            return back()->with('error', 'Only an administrator can reopen a verified subject.');
        }

        $class->markSubjectDraft();

        return back()->with('status', "{$class->code} returned to draft.");
    }

    /**
     * Students never get the completeness view — that is instructor information.
     */
    protected function authorizeSubjectAccess(User $user, ClassModel $class, bool $write = false): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isInstructor()) {
            abort_unless($class->instructor_id === $user->id, 403);

            return;
        }

        abort_if($write, 403);

        abort_unless(
            $class->enrollments()
                ->where('student_id', $user->id)
                ->countable()
                ->exists(),
            403
        );
    }
}