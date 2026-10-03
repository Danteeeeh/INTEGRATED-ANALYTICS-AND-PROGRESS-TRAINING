<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\AnnouncementView;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionChoice;
use App\Models\Quiz;
use App\Models\Rubric;
use App\Models\RubricAssessment;
use App\Models\RubricCriterion;
use App\Models\RubricLevel;
use App\Models\User;
use Illuminate\Database\Seeder;

class FiftyTopUpTwoSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::where('email', 'instructor@lms.local')->first()
            ?? User::where('role_id', 38)->firstOrFail();
        $admin = User::where('email', 'admin@lms.local')->first()
            ?? User::where('role_id', 37)->first();
        $studentRoleId = \DB::table('roles')->where('slug', 'student')->value('id');
        $students = User::where('role_id', $studentRoleId)->pluck('id')->all();
        $courseIds = \App\Models\Course::pluck('id')->all();
        $moduleOf = \App\Models\Module::pluck('course_id','id')->all();

        // Modules: ensure 50
        $i = 1;
        while (\App\Models\Module::count() < 50) {
            $cid = $courseIds[$i % count($courseIds)];
            \App\Models\Module::firstOrCreate(
                ['course_id' => $cid, 'title' => 'Module '.$i.' '.$cid],
                ['description' => 'Module '.$i, 'position' => ($i % 10) + 1, 'is_required' => true, 'status' => 'published', 'created_by' => $admin?->id ?? $instructor->id]
            );
            $i++;
        }
        $this->command?->info('modules: '.\App\Models\Module::count());

        // Lessons: extend to 50+
        $i = 1;
        $modIds = \App\Models\Module::pluck('id')->all();
        while (\App\Models\Lesson::count() < 50) {
            $mid = $modIds[$i % count($modIds)];
            Lesson::firstOrCreate(
                ['module_id' => $mid, 'title' => 'Lesson '.$i.' '.$mid],
                ['description' => 'Lesson '.$i, 'content' => 'Full lesson content '.$i, 'duration_minutes' => 30, 'position' => ($i % 8) + 1, 'lesson_type' => 'text', 'is_required' => true, 'status' => 'published', 'created_by' => $admin?->id ?? $instructor->id]
            );
            $i++;
        }
        $this->command?->info('lessons: '.\App\Models\Lesson::count());

        // Questions: ensure 50 per bank coverage (total 50+)
        $i = 1;
        $bankIds = QuestionBank::pluck('id')->all();
        while (Question::count() < 50) {
            $bid = $bankIds[$i % count($bankIds)];
            $q = Question::firstOrCreate(
                ['question_bank_id' => $bid, 'question_text' => 'Sample question '.$i.' (bank '.$bid.')'],
                ['question_type' => 'multiple_choice', 'difficulty' => 'easy', 'default_points' => 1, 'status' => 'active', 'created_by' => $instructor->id]
            );
            foreach (['Option A', 'Option B', 'Option C', 'Option D'] as $j => $opt) {
                QuestionChoice::firstOrCreate(
                    ['question_id' => $q->id, 'position' => $j + 1],
                    ['choice_text' => $opt, 'points' => $j === 0 ? 1 : 0, 'is_correct' => $j === 0, 'feedback' => $j === 0 ? 'Correct!' : '']
                );
            }
            $i++;
        }
        $this->command?->info('questions: '.Question::count().', choices: '.\App\Models\QuestionChoice::count());

        // Quiz questions: link questions to quizzes
        $quizIds = Quiz::pluck('id')->all();
        $questionIds = Question::pluck('id')->all();
        foreach ($quizIds as $qi => $qzId) {
            for ($k = 0; $k < 2; $k++) {
                $qid = $questionIds[($qi + $k) % count($questionIds)];
                \DB::table('quiz_questions')->updateOrInsert(
                    ['quiz_id' => $qzId, 'question_id' => $qid],
                    ['position' => $k + 1, 'points' => 1, 'is_required' => true, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
        $this->command?->info('quiz_questions: '.\DB::table('quiz_questions')->count());

        // Assignments: 50, submissions: 50
        $i = 1;
        while (Assignment::count() < 50) {
            Assignment::firstOrCreate(
                ['slug' => 'assignment-b-'.$i],
                ['class_id' => \App\Models\ClassModel::pluck('id')->all()[($i - 1) % 50], 'title' => 'Assignment '.$i.' (batch 2)', 'instructions' => 'Complete it.', 'points' => 100, 'submission_type' => 'file', 'due_date' => now()->addDays(rand(1, 20)), 'status' => 'published', 'created_by' => $instructor->id]
            );
            $i++;
        }
        $assignIds = Assignment::pluck('id')->all();
        $i = 0;
        foreach ($assignIds as $aid) {
            if (\App\Models\AssignmentSubmission::count() >= 50) break;
            $sid = $students[$i % count($students)];
            AssignmentSubmission::firstOrCreate(
                ['assignment_id' => $aid, 'student_id' => $sid, 'attempt_number' => 1],
                ['submission_text' => 'Submitted answer for assignment.', 'submitted_at' => now()->subDay(), 'is_late' => false, 'status' => 'graded', 'graded_by' => $instructor->id, 'graded_at' => now()->subHours(rand(1, 20))]
            );
            $i++;
        }
        $this->command?->info('assignments: '.Assignment::count().', submissions: '.\App\Models\AssignmentSubmission::count());

        // Rubrics: 50 (title unique via slug-ish title), criteria/levels
        $i = 1;
        while (Rubric::count() < 50) {
            Rubric::firstOrCreate(
                ['title' => 'Rubric '.$i.' (batch 2)'],
                ['description' => 'Rubric '.$i, 'course_id' => $courseIds[$i % count($courseIds)], 'class_id' => null, 'created_by' => $instructor->id, 'is_shared' => true, 'status' => 'active']
            );
            $i++;
        }
        foreach (Rubric::pluck('id')->all() as $rid) {
            $crit = RubricCriterion::firstOrCreate(
                ['rubric_id' => $rid, 'criterion' => 'Overall Quality'],
                ['description' => 'Quality', 'position' => 1, 'max_points' => 100]
            );
            foreach (['Excellent', 'Proficient', 'Developing', 'Beginning'] as $j => $ln) {
                RubricLevel::firstOrCreate(
                    ['rubric_criterion_id' => $crit->id, 'name' => $ln],
                    ['description' => $ln, 'points' => 100 - ($j * 25), 'position' => $j + 1]
                );
            }
        }
        $this->command?->info('rubrics: '.Rubric::count().', criteria: '.\App\Models\RubricCriterion::count().', levels: '.\App\Models\RubricLevel::count());

        // Discussion posts: 50
        $discIds = Discussion::pluck('id')->all();
        $i = 0;
        foreach ($discIds as $did) {
            while (\App\Models\DiscussionPost::count() < 50) {
                $sid = $students[$i % count($students)];
                DiscussionPost::firstOrCreate(
                    ['discussion_id' => $did, 'author_id' => $sid, 'body' => 'Post '.$i.' on discussion '.$did],
                    ['parent_id' => null, 'is_pinned' => false, 'is_approved' => true, 'reported_count' => 0]
                );
                $i++;
            }
        }
        $this->command?->info('discussion_posts: '.\App\Models\DiscussionPost::count());

        // Announcement views: 50
        $annIds = Announcement::pluck('id')->all();
        $i = 0;
        foreach ($annIds as $aid) {
            while (\App\Models\AnnouncementView::count() < 50) {
                $sid = $students[$i % count($students)];
                AnnouncementView::firstOrCreate(
                    ['announcement_id' => $aid, 'user_id' => $sid],
                    ['viewed_at' => now()->subDays(rand(0, 6))]
                );
                $i++;
            }
        }
        $this->command?->info('announcement_views: '.\App\Models\AnnouncementView::count());

        $this->command?->info('TopUp2 done.');
    }
}
