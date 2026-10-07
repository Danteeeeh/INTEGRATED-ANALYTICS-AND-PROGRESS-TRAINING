<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Enrollment;
use App\Models\Feedback;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\LearningPlan;
use App\Models\LearningPlanItem;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionChoice;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAnswerChoice;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds a complete BSIT-flavoured demo dataset aligned with the capstone
 * document (AI-based LMS with integrated analytics and progress training).
 *
 * Safe to run repeatedly: all records are created via firstOrCreate /
 * updateOrCreate keyed on stable unique codes/slugs, and progress/grade
 * records are upserted on class+student pairs.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@lms.local')->firstOrFail();
        $instructor = User::where('email', 'instructor@lms.local')->firstOrFail();
        $student = User::where('email', 'student@lms.local')->firstOrFail();

        // ── BSIT-aligned courses (upsert by code) ──────────────────────────
        $courses = $this->createCourses($admin);

        // ── Classes for the new BSIT courses + enroll the demo student ─────
        $this->createClassesAndEnrollments($courses, $instructor, $student);

        // ── Modules + lessons per course ───────────────────────────────────
        $moduleMap = $this->createModulesAndLessons($courses, $admin);

        // ── Question banks + questions with choices ────────────────────────
        $bankMap = $this->createQuestionBanks($courses, $instructor);

        // ── Quizzes per class + attempts ───────────────────────────────────
        $this->createQuizzes($courses, $moduleMap, $bankMap, $instructor, $student);

        // ── Assignments + submissions ──────────────────────────────────────
        $this->createAssignments($courses, $moduleMap, $instructor, $student);

        // ── Gradebook (Prelim/Midterm/Finals-style items) + grades ────────
        $this->createGradebook($courses, $instructor, $student);

        // ── Progress records ───────────────────────────────────────────────
        $this->createProgress($courses, $moduleMap, $student);

        // ── Feedback entries ───────────────────────────────────────────────
        $this->createFeedback($instructor, $student);

        // ── Learning plans (one active + one completed) ────────────────────
        $this->createLearningPlans($student, $admin);
    }

    protected function createCourses(User $admin): array
    {
        $current = AcademicPeriod::where('is_current', true)->first()
            ?? AcademicPeriod::firstOrFail();

        $rows = [
            ['code' => 'GE-101', 'title' => 'Understanding the Self', 'credits' => 3, 'duration' => 16],
            ['code' => 'IT-101', 'title' => 'Introduction to Computing', 'credits' => 3, 'duration' => 16],
            ['code' => 'IT-102', 'title' => 'Computer Programming 1', 'credits' => 3, 'duration' => 16],
            ['code' => 'IT-103', 'title' => 'Information Management', 'credits' => 3, 'duration' => 16],
            ['code' => 'IT-104', 'title' => 'Networking 1', 'credits' => 3, 'duration' => 16],
            ['code' => 'IT-105', 'title' => 'Web Systems and Technologies', 'credits' => 3, 'duration' => 16],
            ['code' => 'MATH-101', 'title' => 'Mathematics in the Modern World', 'credits' => 3, 'duration' => 16],
        ];

        $map = [];
        foreach ($rows as $row) {
            $course = Course::updateOrCreate(
                ['code' => $row['code']],
                [
                    'title' => $row['title'],
                    'description' => "{$row['title']} — BSIT foundation subject.",
                    'objectives' => "Master the core concepts and skills of {$row['title']}.",
                    'syllabus' => "Weekly modules covering {$row['title']} fundamentals.",
                    'duration_weeks' => $row['duration'],
                    'credits' => $row['credits'],
                    'academic_period_id' => $current->id,
                    'status' => 'published',
                    'created_by' => $admin->id,
                ]
            );
            $map[$row['code']] = $course;
        }

        $this->command?->info('DemoData: '.count($map).' courses ready.');

        return $map;
    }

    protected function createClassesAndEnrollments(array $courses, User $instructor, User $student): void
    {
        $current = AcademicPeriod::where('is_current', true)->first()
            ?? AcademicPeriod::firstOrFail();

        $roomNames = ['Room 101, Building A', 'Room 102, Building A', 'Room 201, Building B', 'Room 301, Building C'];
        $schedules = ['Mon/Wed 10:00-11:30', 'Tue/Thu 14:00-15:30', 'Mon/Wed/Fri 09:00-10:00', 'Tue/Thu 11:00-12:30'];

        $i = 0;
        foreach ($courses as $code => $course) {
            $existing = ClassModel::where('course_id', $course->id)->exists();
            if ($existing) {
                continue;
            }

            $class = ClassModel::create([
                'code' => $code.'-01',
                'course_id' => $course->id,
                'academic_period_id' => $current->id,
                'instructor_id' => $instructor->id,
                'schedule' => $schedules[$i % count($schedules)],
                'room' => $roomNames[$i % count($roomNames)],
                'capacity' => 40,
                'status' => 'active',
            ]);

            Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'class_id' => $class->id],
                ['status' => 'active', 'enrolled_at' => now()->subDays(14)]
            );

            $i++;
        }

        $this->command?->info('DemoData: classes + enrollments created.');
    }

    protected function createModulesAndLessons(array $courses, User $admin): array
    {
        $map = [];
        $moduleNames = ['Foundations', 'Core Concepts', 'Application', 'Integration', 'Assessment Prep'];

        foreach ($courses as $code => $course) {
            foreach ($moduleNames as $i => $name) {
                $module = Module::updateOrCreate(
                    ['course_id' => $course->id, 'title' => "{$name} Module"],
                    [
                        'description' => "{$name} module for {$course->title}.",
                        'objectives' => "Learn and apply {$name} concepts.",
                        'position' => $i + 1,
                        'is_required' => true,
                        'status' => 'published',
                        'created_by' => $admin->id,
                    ]
                );

                $lessonNames = [
                    "Introduction to {$name}",
                    "{$name} — Key Ideas",
                    "{$name} — Applied Practice",
                ];

                foreach ($lessonNames as $j => $lessonName) {
                    Lesson::updateOrCreate(
                        ['module_id' => $module->id, 'title' => $lessonName],
                        [
                            'description' => 'Lesson '.($j + 1).' of '.$name.' — '.$course->title.'.',
                            'content' => "Full lesson content for {$lessonName}. Covers definitions, examples, and guided practice aligned with the AI-assisted progress training model.",
                            'objectives' => "Understand {$lessonName} and be able to apply it.",
                            'duration_minutes' => 30 + ($j * 10),
                            'position' => $j + 1,
                            'lesson_type' => 'text',
                            'is_required' => true,
                            'status' => 'published',
                            'created_by' => $admin->id,
                        ]
                    );
                }

                $map[$code][] = $module;
            }
        }

        $this->command?->info('DemoData: modules + lessons created.');

        return $map;
    }

    protected function createQuestionBanks(array $courses, User $instructor): array
    {
        $map = [];

        // Ensure question categories exist
        $categories = $this->ensureQuestionCategories($instructor);

        foreach ($courses as $code => $course) {
            $bank = QuestionBank::updateOrCreate(
                ['title' => "{$course->title} Question Bank"],
                [
                    'code' => Str::upper($code).'-BANK',
                    'description' => "Sample question bank for {$course->title}.",
                    'category' => 'course',
                    'course_id' => $course->id,
                    'class_id' => null,
                    'created_by' => $instructor->id,
                    'is_shared' => true,
                    'status' => 'active',
                ]
            );

            $questions = [
                // Multiple Choice Questions
                [
                    'q' => "Which of the following best describes the core topic of {$course->title}?",
                    'type' => 'multiple_choice',
                    'difficulty' => 'easy',
                    'category' => 'General',
                    'choices' => [
                        ['t' => "The central concepts of {$course->title}", 'c' => true],
                        ['t' => 'An unrelated discipline', 'c' => false],
                        ['t' => 'Only historical facts', 'c' => false],
                        ['t' => 'Only laboratory work', 'c' => false],
                    ],
                ],
                [
                    'q' => "In {$course->title}, applying the learned concepts to real tasks demonstrates:",
                    'type' => 'multiple_choice',
                    'difficulty' => 'medium',
                    'category' => 'Application',
                    'choices' => [
                        ['t' => 'Higher-order thinking', 'c' => true],
                        ['t' => 'Rote memorization', 'c' => false],
                        ['t' => 'Random guessing', 'c' => false],
                        ['t' => 'No learning value', 'c' => false],
                    ],
                ],
                [
                    'q' => "Which methodology is most appropriate for analyzing data in {$course->title}?",
                    'type' => 'multiple_choice',
                    'difficulty' => 'hard',
                    'category' => 'Analysis',
                    'choices' => [
                        ['t' => 'Statistical analysis with hypothesis testing', 'c' => true],
                        ['t' => 'Anecdotal evidence collection', 'c' => false],
                        ['t' => 'Pure speculation', 'c' => false],
                        ['t' => 'Ignoring outliers entirely', 'c' => false],
                    ],
                ],
                [
                    'q' => "A key ethical consideration in {$course->title} research is:",
                    'type' => 'multiple_choice',
                    'difficulty' => 'medium',
                    'category' => 'Ethics',
                    'choices' => [
                        ['t' => 'Informed consent and data privacy', 'c' => true],
                        ['t' => 'Publishing all raw data publicly', 'c' => false],
                        ['t' => 'Using data without attribution', 'c' => false],
                        ['t' => 'Fabricating favorable results', 'c' => false],
                    ],
                ],
                // True/False Questions
                [
                    'q' => "{$course->title} is relevant to the BSIT program.",
                    'type' => 'true_false',
                    'difficulty' => 'easy',
                    'category' => 'General',
                    'choices' => [
                        ['t' => 'True', 'c' => true],
                        ['t' => 'False', 'c' => false],
                    ],
                ],
                [
                    'q' => "All problems in {$course->title} can be solved with a single formula.",
                    'type' => 'true_false',
                    'difficulty' => 'easy',
                    'category' => 'Concepts',
                    'choices' => [
                        ['t' => 'True', 'c' => false],
                        ['t' => 'False', 'c' => true],
                    ],
                ],
                [
                    'q' => "Critical thinking is not required in {$course->title} - only memorization matters.",
                    'type' => 'true_false',
                    'difficulty' => 'easy',
                    'category' => 'Pedagogy',
                    'choices' => [
                        ['t' => 'True', 'c' => false],
                        ['t' => 'False', 'c' => true],
                    ],
                ],
                // Identification Questions
                [
                    'q' => "The process of breaking down a complex system into smaller, manageable components is called:",
                    'type' => 'identification',
                    'difficulty' => 'medium',
                    'category' => 'Concepts',
                    'is_case_sensitive' => false,
                    'choices' => [
                        ['t' => 'Decomposition', 'c' => true],
                        ['t' => 'decomposition', 'c' => true],
                        ['t' => 'Abstraction', 'c' => false],
                        ['t' => 'Generalization', 'c' => false],
                    ],
                ],
                [
                    'q' => "In programming, a named storage location in memory that holds a value is called a:",
                    'type' => 'identification',
                    'difficulty' => 'easy',
                    'category' => 'Programming',
                    'is_case_sensitive' => false,
                    'choices' => [
                        ['t' => 'Variable', 'c' => true],
                        ['t' => 'variable', 'c' => true],
                        ['t' => 'Constant', 'c' => false],
                        ['t' => 'Function', 'c' => false],
                    ],
                ],
                [
                    'q' => "The principle that states 'every object or entity should have a single, unambiguous purpose' is known as:",
                    'type' => 'identification',
                    'difficulty' => 'hard',
                    'category' => 'Software Design',
                    'is_case_sensitive' => false,
                    'choices' => [
                        ['t' => 'Single Responsibility Principle', 'c' => true],
                        ['t' => 'single responsibility principle', 'c' => true],
                        ['t' => 'SRP', 'c' => true],
                        ['t' => 'SOLID Principle', 'c' => false],
                    ],
                ],
                // Short Answer Questions
                [
                    'q' => "List two advantages of using version control systems in software development.",
                    'type' => 'short_answer',
                    'difficulty' => 'medium',
                    'category' => 'Software Engineering',
                    'is_case_sensitive' => false,
                    'choices' => [
                        ['t' => 'History tracking', 'c' => true],
                        ['t' => 'Collaboration', 'c' => true],
                        ['t' => 'Branching and merging', 'c' => true],
                        ['t' => 'Rollback capability', 'c' => true],
                        ['t' => 'Audit trail', 'c' => true],
                    ],
                ],
                [
                    'q' => "What does SQL stand for?",
                    'type' => 'short_answer',
                    'difficulty' => 'easy',
                    'category' => 'Database',
                    'is_case_sensitive' => false,
                    'choices' => [
                        ['t' => 'Structured Query Language', 'c' => true],
                        ['t' => 'structured query language', 'c' => true],
                        ['t' => 'SQL', 'c' => true],
                    ],
                ],
                // Essay Questions
                [
                    'q' => "Discuss the impact of artificial intelligence on the future of {$course->title}. Provide at least three concrete examples of how AI can transform traditional approaches in this field.",
                    'type' => 'essay',
                    'difficulty' => 'hard',
                    'category' => 'Future Trends',
                    'choices' => [],
                ],
                [
                    'q' => "Compare and contrast the waterfall and agile methodologies in the context of {$course->title} project development. When would you choose one over the other?",
                    'type' => 'essay',
                    'difficulty' => 'medium',
                    'category' => 'Methodologies',
                    'choices' => [],
                ],
                // Multiple Answer Questions
                [
                    'q' => "Which of the following are principles of clean code? (Select all that apply)",
                    'type' => 'multiple_answer',
                    'difficulty' => 'medium',
                    'category' => 'Programming',
                    'choices' => [
                        ['t' => 'Meaningful names for variables and functions', 'c' => true],
                        ['t' => 'Small, focused functions', 'c' => true],
                        ['t' => 'Duplicated code is acceptable for speed', 'c' => false],
                        ['t' => 'Comments should explain WHAT the code does', 'c' => false],
                        ['t' => 'Error handling should be explicit', 'c' => true],
                    ],
                ],
                [
                    'q' => "Which of the following are part of the SOLID principles? (Select all that apply)",
                    'type' => 'multiple_answer',
                    'difficulty' => 'hard',
                    'category' => 'Software Design',
                    'choices' => [
                        ['t' => 'Single Responsibility Principle', 'c' => true],
                        ['t' => 'Open/Closed Principle', 'c' => true],
                        ['t' => 'Liskov Substitution Principle', 'c' => true],
                        ['t' => 'Interface Segregation Principle', 'c' => true],
                        ['t' => 'Dependency Inversion Principle', 'c' => true],
                        ['t' => 'Don\'t Repeat Yourself', 'c' => false],
                    ],
                ],
            ];

            foreach ($questions as $i => $spec) {
                $categoryId = null;
                if (isset($spec['category']) && isset($categories[$spec['category']])) {
                    $categoryId = $categories[$spec['category']]->id;
                }

                $question = Question::updateOrCreate(
                    [
                        'question_bank_id' => $bank->id,
                        'question_text' => $spec['q'],
                    ],
                    [
                        'question_type' => $spec['type'],
                        'explanation' => $spec['type'] === 'essay' 
                            ? 'Grade based on depth of analysis, clarity, and relevance to course concepts.'
                            : 'The correct answer reflects the core learning outcome.',
                        'difficulty' => $spec['difficulty'],
                        'default_points' => match($spec['difficulty']) {
                            'easy' => 1,
                            'medium' => 2,
                            'hard' => 3,
                            default => 1,
                        },
                        'tags' => ['demo', 'bsit', Str::lower($code), Str::lower($spec['category'] ?? 'general')],
                        'created_by' => $instructor->id,
                        'status' => 'active',
                        'category_id' => $categoryId,
                        'is_case_sensitive' => $spec['is_case_sensitive'] ?? false,
                    ]
                );

                foreach ($spec['choices'] as $j => $choice) {
                    QuestionChoice::updateOrCreate(
                        ['question_id' => $question->id, 'position' => $j + 1],
                        [
                            'choice_text' => $choice['t'],
                            'points' => $choice['c'] ? 1 : 0,
                            'is_correct' => $choice['c'],
                            'position' => $j + 1,
                            'feedback' => $choice['c'] ? 'Correct!' : 'Review this topic.',
                        ]
                    );
                }
            }

            $map[$code] = $bank;
        }

        $this->command?->info('DemoData: question banks + questions created.');

        return $map;
    }

    protected function ensureQuestionCategories(User $instructor): array
    {
        $categoryNames = [
            'General' => 'General knowledge questions',
            'Application' => 'Real-world application scenarios',
            'Analysis' => 'Critical analysis and problem solving',
            'Ethics' => 'Ethical considerations in technology',
            'Concepts' => 'Fundamental concepts and definitions',
            'Pedagogy' => 'Teaching and learning methodologies',
            'Programming' => 'Programming concepts and practices',
            'Software Design' => 'Software architecture and design patterns',
            'Software Engineering' => 'Software engineering best practices',
            'Database' => 'Database concepts and SQL',
            'Future Trends' => 'Emerging technologies and future directions',
            'Methodologies' => 'Development methodologies and processes',
        ];

        $categories = [];
        foreach ($categoryNames as $name => $description) {
            $categories[$name] = \App\Models\QuestionCategory::updateOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'course_id' => null,
                    'created_by' => $instructor->id,
                ]
            );
        }

        return $categories;
    }

    protected function createQuizzes(array $courses, array $moduleMap, array $bankMap, User $instructor, User $student): void
    {
        foreach ($courses as $code => $course) {
            $class = ClassModel::where('course_id', $course->id)->first();
            if (! $class) {
                continue;
            }
            $module = $moduleMap[$code][0] ?? null;
            $bank = $bankMap[$code] ?? null;
            if (! $bank) {
                continue;
            }

            $quiz = Quiz::updateOrCreate(
                ['class_id' => $class->id, 'title' => "{$course->title} — Module Quiz"],
                [
                    'slug' => Str::slug("{$code}-module-quiz"),
                    'description' => "Short quiz assessing the first module of {$course->title}.",
                    'instructions' => 'Answer each question to the best of your ability. You may review before submitting.',
                    'module_id' => $module?->id,
                    'lesson_id' => null,
                    'time_limit_minutes' => 15,
                    'attempt_limit' => 2,
                    'passing_score_percent' => 60,
                    'shuffle_questions' => false,
                    'shuffle_choices' => false,
                    'allow_navigation' => true,
                    'auto_save_seconds' => 30,
                    'auto_submit_on_timeout' => true,
                    'result_visibility' => 'always',
                    'review_allowed' => true,
                    'show_correct_answers' => true,
                    'availability_from' => now()->subDays(10),
                    'availability_until' => now()->addDays(20),
                    'status' => 'published',
                    'created_by' => $instructor->id,
                ]
            );

            // Link the bank's questions to the quiz with a deterministic attempt.
            $questions = $bank->questions()->limit(3)->get();
            foreach ($questions as $i => $question) {
                \DB::table('quiz_questions')->updateOrInsert(
                    ['quiz_id' => $quiz->id, 'question_id' => $question->id],
                    ['position' => $i + 1, 'points' => 1, 'is_required' => true, 'created_at' => now(), 'updated_at' => now()]
                );
            }

            if ($questions->isEmpty()) {
                continue;
            }

            // Create one completed attempt per quiz with deterministic scores.
            $enrolled = Enrollment::where('class_id', $class->id)
                ->where('student_id', $student->id)
                ->exists();

            if (! $enrolled) {
                Enrollment::firstOrCreate(
                    ['student_id' => $student->id, 'class_id' => $class->id],
                    ['status' => 'active', 'enrolled_at' => now()->subDays(14)]
                );
            }

            $attempt = QuizAttempt::firstOrCreate(
                ['quiz_id' => $quiz->id, 'student_id' => $student->id, 'attempt_number' => 1],
                [
                    'started_at' => now()->subDays(5)->addHours(rand(0, 8)),
                    'ended_at' => now()->subDays(5)->addHours(rand(9, 12)),
                    'submitted_at' => now()->subDays(5)->addHours(rand(9, 12)),
                    'time_spent_seconds' => rand(300, 700),
                    'score' => $questions->count(),
                    'score_percent' => 100,
                    'is_passed' => true,
                    'status' => 'graded',
                    'graded_by' => $instructor->id,
                    'graded_at' => now()->subDays(4),
                ]
            );

            foreach ($questions as $i => $question) {
                $correctChoice = $question->choices()->where('is_correct', true)->first();
                $answer = QuizAnswer::firstOrCreate(
                    ['quiz_attempt_id' => $attempt->id, 'question_id' => $question->id],
                    [
                        'answer_text' => $question->question_type === 'true_false' ? ($correctChoice?->choice_text ?? 'True') : null,
                        'points_awarded' => 1,
                        'is_correct' => true,
                        'graded_by' => $instructor->id,
                        'graded_at' => now()->subDays(4),
                    ]
                );

                if ($correctChoice) {
                    QuizAnswerChoice::firstOrCreate(
                        ['quiz_answer_id' => $answer->id, 'question_choice_id' => $correctChoice->id],
                        ['is_selected' => true]
                    );
                }
            }
        }

        $this->command?->info('DemoData: quizzes + attempts created.');
    }

    protected function createAssignments(array $courses, array $moduleMap, User $instructor, User $student): void
    {
        foreach ($courses as $code => $course) {
            $class = ClassModel::where('course_id', $course->id)->first();
            if (! $class) {
                continue;
            }
            $module = $moduleMap[$code][1] ?? null;

            $assignment = Assignment::updateOrCreate(
                ['class_id' => $class->id, 'title' => "{$course->title} — Reflection Paper"],
                [
                    'slug' => Str::slug("{$code}-reflection-paper"),
                    'instructions' => "Write a one-page reflection on how {$course->title} applies to your daily life and your BSIT program.",
                    'points' => 100,
                    'submission_type' => 'file',
                    'due_date' => now()->addDays(3),
                    'allow_late' => true,
                    'late_submission_deduction_percent' => 10,
                    'max_attempts' => 2,
                    'allow_resubmission' => true,
                    'resubmission_deadline' => now()->addDays(7),
                    'availability_from' => now()->subDays(7),
                    'availability_until' => now()->addDays(7),
                    'module_id' => $module?->id,
                    'lesson_id' => null,
                    'status' => 'published',
                    'created_by' => $instructor->id,
                ]
            );

            $enrolled = Enrollment::where('class_id', $class->id)->where('student_id', $student->id)->exists();
            if (! $enrolled) {
                continue;
            }

            AssignmentSubmission::firstOrCreate(
                ['assignment_id' => $assignment->id, 'student_id' => $student->id, 'attempt_number' => 1],
                [
                    'submission_text' => "Reflection on {$course->title}: this subject helped me understand core IT concepts and how they connect to real-world tasks.",
                    'submitted_at' => now()->subDays(1),
                    'is_late' => false,
                    'status' => 'graded',
                    'graded_by' => $instructor->id,
                    'graded_at' => now()->subHours(rand(2, 20)),
                ]
            );
        }

        $this->command?->info('DemoData: assignments + submissions created.');
    }

    protected function createGradebook(array $courses, User $instructor, User $student): void
    {
        $itemTypes = [
            'Prelim Exam' => ['type' => 'exam', 'factor' => 0.3, 'percent' => 82],
            'Midterm Exam' => ['type' => 'exam', 'factor' => 0.3, 'percent' => 74],
            'Final Exam' => ['type' => 'exam', 'factor' => 0.4, 'percent' => 80],
            'Assignment Average' => ['type' => 'assignment', 'factor' => 0.2, 'percent' => 88],
            'Quiz Average' => ['type' => 'quiz', 'factor' => 0.2, 'percent' => 76],
            'Class Participation' => ['type' => 'participation', 'factor' => 0.1, 'percent' => 90],
        ];

        foreach ($courses as $code => $course) {
            $class = ClassModel::where('course_id', $course->id)->first();
            if (! $class) {
                continue;
            }
            $enrolled = Enrollment::where('class_id', $class->id)->where('student_id', $student->id)->exists();
            if (! $enrolled) {
                continue;
            }

            foreach ($itemTypes as $title => $spec) {
                $item = GradeItem::firstOrCreate(
                    ['class_id' => $class->id, 'title' => $title],
                    [
                        'description' => "{$title} for {$course->title}.",
                        'max_points' => 100,
                        'factor' => $spec['factor'],
                        'item_type' => $spec['type'],
                        'due_date' => now()->subDays(rand(3, 20)),
                        'position' => 0,
                        'is_released' => true,
                        'released_at' => now()->subDay(),
                    ]
                );

                Grade::firstOrCreate(
                    ['grade_item_id' => $item->id, 'student_id' => $student->id],
                    [
                        'points' => $spec['percent'],
                        'score_percent' => $spec['percent'],
                        'letter_grade' => $this->letterGrade($spec['percent']),
                        'graded_by' => $instructor->id,
                        'graded_at' => now()->subDays(rand(1, 6)),
                        'feedback' => 'Keep up the good work. Review the areas where you scored below target.',
                    ]
                );
            }
        }

        $this->command?->info('DemoData: gradebook created.');
    }

    protected function letterGrade(float $percent): string
    {
        return match (true) {
            $percent >= 90 => 'A',
            $percent >= 85 => 'B+',
            $percent >= 80 => 'B',
            $percent >= 75 => 'C+',
            $percent >= 70 => 'C',
            $percent >= 60 => 'D',
            default => 'F',
        };
    }

    protected function createProgress(array $courses, array $moduleMap, User $student): void
    {
        foreach ($courses as $code => $course) {
            $class = ClassModel::where('course_id', $course->id)->first();
            if (! $class) {
                continue;
            }
            $enrolled = Enrollment::where('class_id', $class->id)->where('student_id', $student->id)->exists();
            if (! $enrolled) {
                continue;
            }

            $modules = $moduleMap[$code] ?? [];
            $totalLessons = 0;
            $completedLessons = 0;

            foreach ($modules as $i => $module) {
                $lessons = $module->lessons()->count();
                $totalLessons += $lessons;

                foreach ($module->lessons()->get() as $lesson) {
                    $isDone = $i < 2 || ($i === 2 && $lesson->position === 1);
                    LessonProgress::updateOrCreate(
                        ['lesson_id' => $lesson->id, 'student_id' => $student->id],
                        [
                            'started_at' => now()->subDays(10),
                            'completed_at' => $isDone ? now()->subDays(8) : null,
                            'last_accessed_at' => $isDone ? now()->subDays(1) : now()->subHours(5),
                            'progress_percent' => $isDone ? 100 : 0,
                            'status' => $isDone ? 'completed' : 'not_started',
                        ]
                    );
                }

                $lessonsDone = $i < 2 ? $lessons : ($i === 2 ? 1 : 0);
                $completedLessons += $lessonsDone;
                $modulePct = $lessons > 0 ? round(($lessonsDone / $lessons) * 100, 2) : 0;

                ModuleProgress::updateOrCreate(
                    ['module_id' => $module->id, 'student_id' => $student->id],
                    [
                        'started_at' => now()->subDays(10),
                        'completed_at' => $i < 2 ? now()->subDays(8) : null,
                        'lessons_completed' => $lessonsDone,
                        'total_lessons' => $lessons,
                        'progress_percent' => $modulePct,
                        'status' => $i < 2 ? 'completed' : ($i === 2 ? 'in_progress' : 'not_started'),
                    ]
                );
            }

            $overallPct = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0;

            CourseProgress::updateOrCreate(
                ['class_id' => $class->id, 'student_id' => $student->id],
                [
                    'started_at' => now()->subDays(14),
                    'completed_at' => null,
                    'modules_completed' => min(2, count($modules)),
                    'total_modules' => count($modules),
                    'lessons_completed' => $completedLessons,
                    'total_lessons' => $totalLessons,
                    'progress_percent' => $overallPct,
                    'final_grade' => null,
                    'status' => 'in_progress',
                ]
            );
        }

        $this->command?->info('DemoData: progress records created.');
    }

    protected function createFeedback(User $instructor, User $student): void
    {
        $grade = Grade::where('student_id', $student->id)->first();
        if ($grade) {
            Feedback::firstOrCreate(
                ['gradable_type' => Grade::class, 'gradable_id' => $grade->id, 'student_id' => $student->id],
                [
                    'author_id' => $instructor->id,
                    'body' => 'You are making steady progress. Focus on reviewing quiz topics where you scored below 75% to strengthen your foundation.',
                    'is_private' => true,
                ]
            );
        }
    }

    protected function createLearningPlans(User $student, User $admin): void
    {
        // An active plan derived from the assessment signals.
        $activePlan = LearningPlan::firstOrCreate(
            ['student_id' => $student->id, 'title' => 'Personalized learning plan', 'status' => 'active'],
            [
                'description' => 'A few signals suggest that focused support could help you regain momentum.',
                'target_date' => now()->addDays(14)->toDateString(),
                'created_by' => $admin->id,
            ]
        );

        foreach ([
            ['Review quiz topics', 'Revisit the related lessons and try the next available practice activity.', 'pending'],
            ['Complete overdue work', 'Start with the oldest missing assignment to recover your learning momentum.', 'in_progress'],
            ['Keep a steady pace', 'A short, regular study session can move this course toward completion.', 'pending'],
        ] as [$title, $desc, $status]) {
            LearningPlanItem::firstOrCreate(
                ['learning_plan_id' => $activePlan->id, 'title' => $title],
                [
                    'description' => $desc,
                    'target_date' => now()->addDays(7)->toDateString(),
                    'progress_percent' => $status === 'completed' ? 100 : ($status === 'in_progress' ? 50 : 0),
                    'status' => $status,
                ]
            );
        }

        // A completed historical plan so the student can see progress over time.
        $completedPlan = LearningPlan::firstOrCreate(
            ['student_id' => $student->id, 'title' => 'First-semester catch-up plan', 'status' => 'completed'],
            [
                'description' => 'Completed plan from the first semester — all items finished.',
                'target_date' => now()->subDays(30)->toDateString(),
                'created_by' => $admin->id,
            ]
        );

        foreach ([
            ['Finish Lesson 1–3 of all core modules', 'Complete the introductory lessons of every enrolled course.', 'completed'],
            ['Submit all pending assignments', 'Submit outstanding assignments before the semester cutoff.', 'completed'],
        ] as [$title, $desc, $status]) {
            LearningPlanItem::firstOrCreate(
                ['learning_plan_id' => $completedPlan->id, 'title' => $title],
                [
                    'description' => $desc,
                    'target_date' => now()->subDays(20)->toDateString(),
                    'progress_percent' => 100,
                    'status' => 'completed',
                ]
            );
        }

        $this->command?->info('DemoData: learning plans created.');
    }
}