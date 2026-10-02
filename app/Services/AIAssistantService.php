<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\CourseProgress;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AIAssistantService
{
    public function __construct(private StudentPerformanceAssessmentService $performanceAssessment) {}

    /**
     * Answer a student's message from real LMS data, with a learning-support disclaimer.
     */
    public function respond(User $student, string $message): string
    {
        $text = mb_strtolower(trim($message));

        $classIds = $student->enrollments()
            ->where('status', 'active')
            ->pluck('class_id');

        if ($classIds->isEmpty()) {
            return 'You are not enrolled in any active class yet. Ask your instructor or registrar about enrolling — once you have a class, I can help you plan your studies.';
        }

        if ($this->containsAny($text, ['deadline', 'due', 'due date', 'upcoming', 'takdang', 'aralin', 'naka-isked', 'schedule'])) {
            return $this->deadlines($student, $classIds);
        }

        if ($this->containsAny($text, ['progress', 'status', 'percent', 'completion', 'paano na', 'kamusta', 'advance', 'bagsak', 'grade'])) {
            return $this->progress($student, $classIds);
        }

        if ($this->containsAny($text, ['ano uunahin', 'uunahin', 'prioritize', 'priority', 'ano una', 'what first', 'where to start', 'anu-una'])) {
            return $this->prioritize($student, $classIds);
        }

        if ($this->containsAny($text, ['paano bumawi', 'bumawi', 'recover', 'catch up', 'behind', 'habol', 'back on track', 'bawi'])) {
            return $this->recover($student, $classIds);
        }

        // Fallback: point the student at what's actionable right now.
        $next = Assignment::whereIn('class_id', $classIds)
            ->published()
            ->where('due_date', '>=', Carbon::now())
            ->orderBy('due_date')
            ->first();

        $assessment = $this->assessment($student->id, $classIds);

        $parts = [
            'I can help you with your deadlines, progress, priorities, and how to catch up.',
            'Your current status: '.($assessment['summary'] ?? 'needs more activity to assess.'),
        ];

        if ($next) {
            $parts[] = 'Your next upcoming assignment is "'.$next->title.'" due '.$next->due_date?->format('M d, Y').'.';
        } else {
            $parts[] = 'There are no upcoming assignment deadlines right now — a great time to work through your lessons.';
        }

        $parts[] = 'Try asking me: "What\'s due this week?", "How am I doing?", "What should I work on first?", or "How do I catch up?"';

        return implode(' ', $parts);
    }

    protected function deadlines(User $student, Collection $classIds): string
    {
        $assignments = Assignment::whereIn('class_id', $classIds)
            ->published()
            ->where('due_date', '>=', Carbon::now()->subDay())
            ->orderBy('due_date')
            ->take(5)
            ->get();

        if ($assignments->isEmpty()) {
            return 'You have no upcoming assignment deadlines right now.';
        }

        $lines = $assignments->map(function ($a) {
            $soon = $a->due_date && $a->due_date->between(Carbon::now(), Carbon::now()->addDays(3));

            return '• '.$a->title.' — due '.$a->due_date?->format('M d, Y').($soon ? ' (soon!)' : '');
        });

        return 'Here are your upcoming assignment deadlines:'."\n".$lines->implode("\n");
    }

    protected function progress(User $student, Collection $classIds): string
    {
        $assessment = $this->assessment($student->id, $classIds);
        $lines = ['Your learning status: '.($assessment['summary'] ?? 'Not enough activity yet.')];

        foreach ($assessment['signals'] ?? [] as $signal) {
            if (isset($signal['label']) && isset($signal['value'])) {
                $lines[] = '• '.$signal['label'].': '.$signal['value'];
            }
        }

        $lines[] = 'Keep following your learning plan and check upcoming work regularly.';

        return implode("\n", $lines);
    }

    protected function prioritize(User $student, Collection $classIds): string
    {
        $overdue = Assignment::whereIn('class_id', $classIds)
            ->published()
            ->where('due_date', '<', Carbon::now())
            ->orderBy('due_date')
            ->take(3)
            ->get();

        $next = Assignment::whereIn('class_id', $classIds)
            ->published()
            ->where('due_date', '>=', Carbon::now())
            ->orderBy('due_date')
            ->take(3)
            ->get();

        $parts = [];

        if ($overdue->isNotEmpty()) {
            $parts[] = 'Start with overdue work first:';
            $parts[] = $overdue->map(fn ($a) => '• '.$a->title.' (was due '.$a->due_date?->format('M d, Y').')')->implode("\n");
        }

        if ($next->isNotEmpty()) {
            $parts[] = 'Then, keep these upcoming deadlines in mind:';
            $parts[] = $next->map(fn ($a) => '• '.$a->title.' — due '.$a->due_date?->format('M d, Y'))->implode("\n");
        }

        if ($parts === []) {
            $parts[] = 'You have no assignment deadlines right now — use the time to review lessons and complete your learning plan items.';
        }

        $assessment = $this->assessment($student->id, $classIds);
        $recs = collect($assessment['recommendations'] ?? [])->take(2)
            ->map(fn ($r) => '• '.$r['title'].' — '.$r['detail'])
            ->implode("\n");

        if ($recs !== '') {
            $parts[] = 'Suggested focus for you:'."\n".$recs;
        }

        return implode("\n\n", $parts);
    }

    protected function recover(User $student, Collection $classIds): string
    {
        $assessment = $this->assessment($student->id, $classIds);
        $parts = ['You can still turn this around. Start small and be consistent.'];

        $recs = collect($assessment['recommendations'] ?? [])->take(3)
            ->map(fn ($r) => '• '.$r['title'].' — '.$r['detail'])
            ->implode("\n");

        if ($recs !== '') {
            $parts[] = 'Based on your data, focus on:'."\n".$recs;
        }

        $overdue = Assignment::whereIn('class_id', $classIds)
            ->published()
            ->where('due_date', '<', Carbon::now())
            ->orderBy('due_date')
            ->take(2)
            ->get();

        if ($overdue->isNotEmpty()) {
            $parts[] = 'Clear your overdue items first: '.$overdue->map(fn ($a) => $a->title)->implode(', ').'.';
        }

        $parts[] = 'Tip: a short, regular study session (15–30 minutes a day) rebuilds momentum faster than one long cram session.';

        return implode("\n\n", $parts);
    }

    protected function assessment(int $studentId, Collection $classIds): array
    {
        $overallProgress = CourseProgress::where('student_id', $studentId)
            ->whereIn('class_id', $classIds)
            ->pluck('progress_percent')
            ->avg() ?? 0;

        $streak = 0;
        $date = Carbon::today();
        for ($i = 0; $i < 30; $i++) {
            if (\App\Models\LessonProgress::where('student_id', $studentId)->whereDate('updated_at', $date->copy()->subDays($i))->exists()) {
                $streak++;
            } elseif ($i > 0) {
                break;
            }
        }

        return $this->performanceAssessment->assess($studentId, $classIds, (float) $overallProgress, $streak);
    }

    protected function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (mb_strpos($text, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
