<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\StudentPerformanceAssessmentService;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

class StudentPerformanceAssessmentTest extends TestCase
{
    use CreatesLmsUsers;

    public function test_new_student_gets_insufficient_data_assessment(): void
    {
        $student = $this->makeUser('student');
        $assessment = app(StudentPerformanceAssessmentService::class)->assess($student->id, collect(), 0, 0);

        $this->assertSame('insufficient_data', $assessment['status']);
        $this->assertSame('BUILDING A BASELINE', $assessment['label']);
        $this->assertNotEmpty($assessment['recommendations']);
    }

    public function test_low_signals_classify_student_as_at_risk(): void
    {
        $student = $this->makeUser('student');
        $instructor = $this->makeUser('instructor');
        $course = Course::factory()->create(['status' => 'published', 'created_by' => $instructor->id]);
        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'status' => 'active',
        ]);
        Enrollment::create(['student_id' => $student->id, 'class_id' => $class->id, 'status' => 'active']);

        $quiz = Quiz::create([
            'class_id' => $class->id,
            'title' => 'Low score quiz',
            'slug' => 'low-score-'.uniqid(),
            'status' => 'published',
            'created_by' => $instructor->id,
        ]);
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'score_percent' => 40,
            'status' => QuizAttempt::STATUS_GRADED,
            'graded_at' => now(),
        ]);

        $assignment = Assignment::create([
            'class_id' => $class->id,
            'title' => 'Missing assignment',
            'slug' => 'missing-'.uniqid(),
            'points' => 100,
            'status' => Assignment::STATUS_PUBLISHED,
            'due_date' => now()->subDays(2),
            'created_by' => $instructor->id,
        ]);

        $assessment = app(StudentPerformanceAssessmentService::class)->assess($student->id, collect([$class->id]), 20, 0);

        $this->assertSame('at_risk', $assessment['status']);
        $this->assertGreaterThanOrEqual(40, $assessment['risk_score']);
        $this->assertNotEmpty($assessment['reasons']);
        $this->assertNotEmpty($assessment['recommendations']);
        $this->assertNotNull($assignment->id);
    }

    public function test_strong_signals_classify_student_as_on_track(): void
    {
        $student = $this->makeUser('student');
        $instructor = $this->makeUser('instructor');
        $course = Course::factory()->create(['status' => 'published', 'created_by' => $instructor->id]);
        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'status' => 'active',
        ]);
        Enrollment::create(['student_id' => $student->id, 'class_id' => $class->id, 'status' => 'active']);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Completed module',
            'status' => Module::STATUS_PUBLISHED,
            'position' => 1,
            'created_by' => $instructor->id,
        ]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Completed lesson',
            'lesson_type' => 'text',
            'status' => Lesson::STATUS_PUBLISHED,
            'position' => 1,
            'created_by' => $instructor->id,
        ]);
        LessonProgress::create([
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'status' => LessonProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'last_accessed_at' => now(),
            'completed_at' => now(),
        ]);

        $quiz = Quiz::create([
            'class_id' => $class->id,
            'title' => 'Strong score quiz',
            'slug' => 'strong-score-'.uniqid(),
            'status' => 'published',
            'created_by' => $instructor->id,
        ]);
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'score_percent' => 90,
            'status' => QuizAttempt::STATUS_GRADED,
            'graded_at' => now(),
        ]);

        $assignment = Assignment::create([
            'class_id' => $class->id,
            'title' => 'Completed assignment',
            'slug' => 'completed-'.uniqid(),
            'points' => 100,
            'status' => Assignment::STATUS_PUBLISHED,
            'due_date' => now()->addDays(2),
            'created_by' => $instructor->id,
        ]);
        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'submission_text' => 'Completed work',
            'submitted_at' => now(),
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
        ]);

        $assessment = app(StudentPerformanceAssessmentService::class)->assess($student->id, collect([$class->id]), 90, 5);

        $this->assertSame('on_track', $assessment['status']);
        $this->assertLessThan(40, $assessment['risk_score']);
        $this->assertNotEmpty($assessment['signals']);
    }
}
