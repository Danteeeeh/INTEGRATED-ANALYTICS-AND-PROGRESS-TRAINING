<?php

namespace Database\Seeders;

use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Competency;
use App\Models\CompetencyFramework;
use App\Models\Course;
use App\Models\CourseCompetency;
use App\Models\Enrollment;
use App\Models\Feedback;
use App\Models\Grade;
use App\Models\Rubric;
use App\Models\RubricAssessment;
use App\Models\RubricCriterion;
use App\Models\RubricLevel;
use App\Models\StudentCompetency;
use App\Models\User;
use Illuminate\Database\Seeder;

class BulkFiftyTwoSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::where('email', 'instructor@lms.local')->first()
            ?? User::where('role_id', 38)->firstOrFail();
        $admin = User::where('email', 'admin@lms.local')->first()
            ?? User::where('role_id', 37)->first();
        $classes = ClassModel::all();

        $this->command?->info('feedback...');
        foreach (Grade::whereNotNull('feedback')->get()->take(200) as $grade) {
            Feedback::firstOrCreate(
                ['gradable_type' => Grade::class, 'gradable_id' => $grade->id, 'student_id' => $grade->student_id],
                [
                    'author_id' => $instructor->id,
                    'body' => 'Your score of '.$grade->score_percent.'% shows good progress. Review the topics you missed.',
                    'is_private' => true,
                    'rating' => rand(3, 5),
                    'feedback_type' => ['grade', 'performance', 'encouragement'][rand(0, 2)],
                    'tags' => json_encode(['demo', 'grade']),
                    'read_at' => rand(0, 1) ? now()->subDays(rand(1, 5)) : null,
                ]
            );
        }

        $this->command?->info('rubrics...');
        $rubricDefs = [
            ['title' => 'Essay Rubric', 'criteria' => [
                ['criterion' => 'Content & Understanding', 'max' => 40, 'levels' => [40, 30, 20, 10]],
                ['criterion' => 'Organization', 'max' => 20, 'levels' => [20, 15, 10, 5]],
                ['criterion' => 'Grammar & Style', 'max' => 20, 'levels' => [20, 15, 10, 5]],
                ['criterion' => 'Critical Thinking', 'max' => 20, 'levels' => [20, 15, 10, 5]],
            ]],
            ['title' => 'Presentation Rubric', 'criteria' => [
                ['criterion' => 'Clarity of Delivery', 'max' => 30, 'levels' => [30, 20, 10, 5]],
                ['criterion' => 'Visual Aids', 'max' => 20, 'levels' => [20, 15, 10, 5]],
                ['criterion' => 'Content Accuracy', 'max' => 30, 'levels' => [30, 20, 10, 5]],
                ['criterion' => 'Engagement', 'max' => 20, 'levels' => [20, 15, 10, 5]],
            ]],
            ['title' => 'Code Quality Rubric', 'criteria' => [
                ['criterion' => 'Correctness', 'max' => 40, 'levels' => [40, 30, 20, 10]],
                ['criterion' => 'Readability', 'max' => 20, 'levels' => [20, 15, 10, 5]],
                ['criterion' => 'Efficiency', 'max' => 20, 'levels' => [20, 15, 10, 5]],
                ['criterion' => 'Documentation', 'max' => 20, 'levels' => [20, 15, 10, 5]],
            ]],
        ];
        $levelNames = ['Excellent', 'Proficient', 'Developing', 'Beginning'];
        foreach ($classes->take(5) as $class) {
            foreach ($rubricDefs as $rd) {
                $rubric = Rubric::firstOrCreate(
                    ['title' => $rd['title'].' - '.$class->code],
                    [
                        'description' => $rd['title'].' for '.$class->code.'.',
                        'course_id' => $class->course_id,
                        'class_id' => $class->id,
                        'created_by' => $instructor->id,
                        'is_shared' => true,
                        'status' => 'active',
                    ]
                );
                foreach ($rd['criteria'] as $i => $cd) {
                    $criterion = RubricCriterion::firstOrCreate(
                        ['rubric_id' => $rubric->id, 'criterion' => $cd['criterion']],
                        ['description' => 'Assesses '.$cd['criterion'].'.', 'position' => $i + 1, 'max_points' => $cd['max']]
                    );
                    foreach ($cd['levels'] as $j => $pts) {
                        RubricLevel::firstOrCreate(
                            ['rubric_criterion_id' => $criterion->id, 'name' => $levelNames[$j]],
                            ['description' => $levelNames[$j].' in '.$cd['criterion'].'.', 'points' => $pts, 'position' => $j + 1]
                        );
                    }
                }
            }
        }

        $this->command?->info('rubric assessments...');
        $submissions = AssignmentSubmission::where('status', 'graded')->get();
        $rubric = Rubric::first();
        if ($rubric && $submissions->count()) {
            foreach ($submissions->take(20) as $sub) {
                foreach ($rubric->criteria()->get() as $criterion) {
                    $level = $criterion->levels()->get()->random();
                    RubricAssessment::firstOrCreate(
                        ['assignment_submission_id' => $sub->id, 'rubric_id' => $rubric->id, 'rubric_criterion_id' => $criterion->id],
                        ['rubric_level_id' => $level->id, 'points_awarded' => $level->points, 'feedback' => 'Rated '.$level->name.'.', 'assessed_by' => $instructor->id]
                    );
                }
            }
        }

        $this->command?->info('competencies...');
        $frameworks = [
            ['code' => 'BSIT-CORE', 'name' => 'BSIT Core Competencies', 'desc' => 'Foundational competencies for the BSIT program.',
                'comps' => [
                    ['C-101', 'Programming Fundamentals'], ['C-102', 'Data Management'], ['C-103', 'Networking Basics'],
                    ['C-104', 'Web Development'], ['C-105', 'Systems Analysis'], ['C-106', 'Information Security'],
                ]],
            ['code' => 'PROF-SKILLS', 'name' => 'Professional Skills', 'desc' => 'Workplace-ready professional competencies.',
                'comps' => [
                    ['P-201', 'Communication'], ['P-202', 'Teamwork'], ['P-203', 'Ethics in Computing'],
                ]],
        ];
        $courseCodes = ['IT-101', 'IT-102', 'IT-103', 'IT-104', 'IT-105', 'MATH-101', 'GE-101'];
        foreach ($frameworks as $fw) {
            $framework = CompetencyFramework::firstOrCreate(
                ['code' => $fw['code']],
                ['name' => $fw['name'], 'description' => $fw['desc'], 'institution' => 'University', 'created_by' => $admin?->id ?? $instructor->id, 'status' => 'active']
            );
            foreach ($fw['comps'] as $i => $c) {
                $comp = Competency::firstOrCreate(
                    ['framework_id' => $framework->id, 'code' => $c[0]],
                    ['parent_id' => null, 'name' => $c[1], 'description' => $c[1].' competency.', 'position' => $i + 1]
                );
                $course = Course::where('code', $courseCodes[$i % count($courseCodes)])->first() ?? Course::first();
                if ($course) {
                    CourseCompetency::firstOrCreate(
                        ['competency_id' => $comp->id, 'course_id' => $course->id],
                        ['module_id' => null, 'lesson_id' => null, 'weight' => 1.0]
                    );
                }
            }
        }

        $this->command?->info('student competencies...');
        $students = User::where('role_id', 39)->get();
        $comps = Competency::all();
        if ($comps->count()) {
            foreach ($students as $stu) {
                $enrolls = Enrollment::where('student_id', $stu->id)->pluck('class_id')->all();
                $classId = ! empty($enrolls) ? $enrolls[array_rand($enrolls)] : null;
                foreach ($comps->take(4) as $comp) {
                    $level = ['Beginning', 'Developing', 'Developing', 'Proficient', 'Proficient', 'Mastery'][rand(0, 5)];
                    StudentCompetency::firstOrCreate(
                        ['student_id' => $stu->id, 'competency_id' => $comp->id],
                        ['class_id' => $classId, 'current_level' => $level, 'required_level' => 'Proficient', 'evidence_count' => rand(0, 5), 'mastered_at' => $level === 'Mastery' ? now()->subDays(rand(1, 20)) : null]
                    );
                }
            }
        }

        $this->command?->info('BulkFiftyTwo: done.');
    }
}
