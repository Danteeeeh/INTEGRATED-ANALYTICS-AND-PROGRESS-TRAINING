<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PhaseTwoSeeder extends Seeder
{
    public function run(): void
    {
        // Create Academic Periods
        $fall2024 = AcademicPeriod::create([
            'name' => 'Fall Semester 2024',
            'code' => 'FALL-2024',
            'start_date' => '2024-09-01',
            'end_date' => '2024-12-20',
            'is_current' => true,
            'is_enrollment_open' => true,
            'description' => 'Fall 2024 academic semester',
        ]);

        $spring2025 = AcademicPeriod::create([
            'name' => 'Spring Semester 2025',
            'code' => 'SPRING-2025',
            'start_date' => '2025-01-15',
            'end_date' => '2025-05-15',
            'is_current' => false,
            'is_enrollment_open' => false,
            'description' => 'Spring 2025 academic semester',
        ]);

        // Create Courses
        $courses = [
            [
                'code' => 'CS101',
                'title' => 'Introduction to Computer Science',
                'description' => 'Fundamental concepts of programming and computer science',
                'objectives' => 'Learn programming fundamentals and problem-solving',
                'syllabus' => 'Variables, data types, control structures, functions',
                'duration_weeks' => 12,
                'academic_period_id' => $fall2024->id,
                'status' => 'published',
            ],
            [
                'code' => 'MATH101',
                'title' => 'Calculus I',
                'description' => 'Introduction to differential and integral calculus',
                'objectives' => 'Understand limits, derivatives, and integrals',
                'syllabus' => 'Limits, differentiation, integration, applications',
                'duration_weeks' => 16,
                'academic_period_id' => $fall2024->id,
                'status' => 'published',
            ],
            [
                'code' => 'ENG101',
                'title' => 'English Composition',
                'description' => 'College-level writing and rhetoric',
                'objectives' => 'Develop academic writing skills',
                'syllabus' => 'Essay writing, research methods, grammar',
                'duration_weeks' => 12,
                'academic_period_id' => $fall2024->id,
                'status' => 'published',
            ],
            [
                'code' => 'PHYS101',
                'title' => 'Physics I',
                'description' => 'Mechanics and thermodynamics',
                'objectives' => 'Understand fundamental physics principles',
                'syllabus' => 'Kinematics, dynamics, energy, thermodynamics',
                'duration_weeks' => 16,
                'academic_period_id' => $fall2024->id,
                'status' => 'published',
            ],
            [
                'code' => 'BIO101',
                'title' => 'Introduction to Biology',
                'description' => 'Fundamental concepts in biological sciences',
                'objectives' => 'Understand basic biological processes',
                'syllabus' => 'Cell biology, genetics, evolution, ecology',
                'duration_weeks' => 12,
                'academic_period_id' => $fall2024->id,
                'status' => 'published',
            ],
        ];

        $createdCourses = [];
        $admin = User::where('email', 'admin@lms.local')->first();

        foreach ($courses as $course) {
            $course['created_by'] = $admin->id;
            $createdCourses[$course['code']] = Course::create($course);
        }

        // Get instructor
        $instructor = User::where('email', 'instructor@lms.local')->first();

        // Create Classes
        $classes = [
            [
                'code' => 'CS101-01',
                'course_id' => $createdCourses['CS101']->id,
                'academic_period_id' => $fall2024->id,
                'instructor_id' => $instructor->id,
                'schedule' => 'Mon/Wed 10:00-11:30',
                'room' => 'Room 101, Building A',
                'capacity' => 30,
                'status' => 'active',
            ],
            [
                'code' => 'CS101-02',
                'course_id' => $createdCourses['CS101']->id,
                'academic_period_id' => $fall2024->id,
                'instructor_id' => $instructor->id,
                'schedule' => 'Tue/Thu 14:00-15:30',
                'room' => 'Room 102, Building A',
                'capacity' => 30,
                'status' => 'active',
            ],
            [
                'code' => 'MATH101-01',
                'course_id' => $createdCourses['MATH101']->id,
                'academic_period_id' => $fall2024->id,
                'instructor_id' => $instructor->id,
                'schedule' => 'Mon/Wed/Fri 09:00-10:00',
                'room' => 'Room 201, Building B',
                'capacity' => 25,
                'status' => 'active',
            ],
            [
                'code' => 'ENG101-01',
                'course_id' => $createdCourses['ENG101']->id,
                'academic_period_id' => $fall2024->id,
                'instructor_id' => $instructor->id,
                'schedule' => 'Tue/Thu 11:00-12:30',
                'room' => 'Room 305, Building C',
                'capacity' => 20,
                'status' => 'active',
            ],
        ];

        $createdClasses = [];
        foreach ($classes as $class) {
            $createdClasses[] = ClassModel::create($class);
        }

        // Get student
        $student = User::where('email', 'student@lms.local')->first();

        // Create some enrollments for the student
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $createdClasses[0]->id, // CS101-01
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $createdClasses[2]->id, // MATH101-01
            'status' => 'active',
        ]);

        $this->command->info('Phase 2 data seeded successfully!');
        $this->command->info('Created: 2 academic periods, 5 courses, 4 classes, 2 enrollments');
    }
}
