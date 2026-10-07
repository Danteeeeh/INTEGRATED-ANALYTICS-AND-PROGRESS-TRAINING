<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Seeder;

class PhaseTwoSeeder extends Seeder
{
    public function run(): void
    {
        // Create Academic Periods (using firstOrCreate to avoid duplicates)
        $preliminary = AcademicPeriod::firstOrCreate(
            ['code' => 'PRELIM'],
            [
                'name' => 'Preliminary',
                'code' => 'PRELIM',
                'start_date' => '2024-09-01',
                'end_date' => '2024-12-20',
                'is_current' => true,
                'is_enrollment_open' => true,
                'description' => 'Preliminary grading period.',
            ]
        );

        $finals = AcademicPeriod::firstOrCreate(
            ['code' => 'FINALS'],
            [
                'name' => 'Finals',
                'code' => 'FINALS',
                'start_date' => '2025-05-16',
                'end_date' => '2025-08-15',
                'is_current' => false,
                'is_enrollment_open' => false,
                'description' => 'Final grading period.',
            ]
        );

        $midterm = AcademicPeriod::firstOrCreate(
            ['code' => 'MIDTERM'],
            [
                'name' => 'Midterm',
                'code' => 'MIDTERM',
                'start_date' => '2025-01-15',
                'end_date' => '2025-05-15',
                'is_current' => false,
                'is_enrollment_open' => false,
                'description' => 'Midterm grading period.',
            ]
        );

        // Create Courses
        $courses = [
            [
                'code' => 'CS101',
                'title' => 'Introduction to Computer Science',
                'description' => 'Fundamental concepts of programming and computer science',
                'objectives' => 'Learn programming fundamentals and problem-solving',
                'syllabus' => 'Variables, data types, control structures, functions',
                'duration_weeks' => 12,
                'academic_period_id' => $preliminary->id,
                'status' => 'published',
            ],
            [
                'code' => 'MATH101',
                'title' => 'Calculus I',
                'description' => 'Introduction to differential and integral calculus',
                'objectives' => 'Understand limits, derivatives, and integrals',
                'syllabus' => 'Limits, differentiation, integration, applications',
                'duration_weeks' => 16,
                'academic_period_id' => $preliminary->id,
                'status' => 'published',
            ],
            [
                'code' => 'ENG101',
                'title' => 'English Composition',
                'description' => 'College-level writing and rhetoric',
                'objectives' => 'Develop academic writing skills',
                'syllabus' => 'Essay writing, research methods, grammar',
                'duration_weeks' => 12,
                'academic_period_id' => $preliminary->id,
                'status' => 'published',
            ],
            [
                'code' => 'PHYS101',
                'title' => 'Physics I',
                'description' => 'Mechanics and thermodynamics',
                'objectives' => 'Understand fundamental physics principles',
                'syllabus' => 'Kinematics, dynamics, energy, thermodynamics',
                'duration_weeks' => 16,
                'academic_period_id' => $preliminary->id,
                'status' => 'published',
            ],
            [
                'code' => 'BIO101',
                'title' => 'Introduction to Biology',
                'description' => 'Fundamental concepts in biological sciences',
                'objectives' => 'Understand basic biological processes',
                'syllabus' => 'Cell biology, genetics, evolution, ecology',
                'duration_weeks' => 12,
                'academic_period_id' => $preliminary->id,
                'status' => 'published',
            ],
        ];

        $createdCourses = [];
        $admin = User::where('email', 'admin@lms.local')->first();

        foreach ($courses as $course) {
            $course['created_by'] = $admin->id;
            $createdCourses[$course['code']] = Course::firstOrCreate(
                ['code' => $course['code']],
                $course
            );
        }

        // Get instructor
        $instructor = User::where('email', 'instructor@lms.local')->first();

        // Create Classes
        $classes = [
            [
                'code' => 'CS101-01',
                'course_id' => $createdCourses['CS101']->id,
                'academic_period_id' => $preliminary->id,
                'instructor_id' => $instructor->id,
                'schedule' => 'Mon/Wed 10:00-11:30',
                'room' => 'Room 101, Building A',
                'capacity' => 30,
                'status' => 'active',
            ],
            [
                'code' => 'CS101-02',
                'course_id' => $createdCourses['CS101']->id,
                'academic_period_id' => $preliminary->id,
                'instructor_id' => $instructor->id,
                'schedule' => 'Tue/Thu 14:00-15:30',
                'room' => 'Room 102, Building A',
                'capacity' => 30,
                'status' => 'active',
            ],
            [
                'code' => 'MATH101-01',
                'course_id' => $createdCourses['MATH101']->id,
                'academic_period_id' => $preliminary->id,
                'instructor_id' => $instructor->id,
                'schedule' => 'Mon/Wed/Fri 09:00-10:00',
                'room' => 'Room 201, Building B',
                'capacity' => 25,
                'status' => 'active',
            ],
            [
                'code' => 'ENG101-01',
                'course_id' => $createdCourses['ENG101']->id,
                'academic_period_id' => $preliminary->id,
                'instructor_id' => $instructor->id,
                'schedule' => 'Tue/Thu 11:00-12:30',
                'room' => 'Room 305, Building C',
                'capacity' => 20,
                'status' => 'active',
            ],
        ];

        $createdClasses = [];
        foreach ($classes as $class) {
            $createdClasses[] = ClassModel::firstOrCreate(
                ['code' => $class['code']],
                $class
            );
        }

        // Get student
        $student = User::where('email', 'student@lms.local')->first();

        // Create some enrollments for the student (using firstOrCreate to avoid duplicates)
        Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'class_id' => $createdClasses[0]->id],
            ['status' => 'active']
        );

        Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'class_id' => $createdClasses[2]->id],
            ['status' => 'active']
        );

        // Create modules and lessons for CS101
        $this->createModulesAndLessonsForCS101($createdCourses['CS101'], $admin);

        $this->command->info('Phase 2 data seeded successfully!');
        $this->command->info('Created: 2 academic periods, 5 courses, 4 classes, 2 enrollments, modules and lessons for CS101');
    }

    protected function createModulesAndLessonsForCS101(Course $course, User $admin): void
    {
        $modules = [
            [
                'title' => 'Week 1: Introduction to Programming',
                'description' => 'Overview of programming concepts and computer science fundamentals',
                'objectives' => 'Understand what programming is and why it matters',
                'position' => 1,
                'lessons' => [
                    ['title' => 'What is Programming?', 'description' => 'Introduction to programming concepts'],
                    ['title' => 'Computer Science Basics', 'description' => 'Fundamental CS concepts'],
                    ['title' => 'Setting Up Your Environment', 'description' => 'Development environment setup'],
                ],
            ],
            [
                'title' => 'Week 2: Variables and Data Types',
                'description' => 'Understanding variables, data types, and memory',
                'objectives' => 'Learn to declare and use variables with different data types',
                'position' => 2,
                'lessons' => [
                    ['title' => 'Variables', 'description' => 'Declaring and using variables'],
                    ['title' => 'Data Types', 'description' => 'Integer, float, string, boolean'],
                    ['title' => 'Type Conversion', 'description' => 'Converting between data types'],
                ],
            ],
            [
                'title' => 'Week 3: Control Structures',
                'description' => 'Conditional statements and loops',
                'objectives' => 'Master if/else statements and loops',
                'position' => 3,
                'lessons' => [
                    ['title' => 'If/Else Statements', 'description' => 'Conditional logic'],
                    ['title' => 'For Loops', 'description' => 'Iterating with for loops'],
                    ['title' => 'While Loops', 'description' => 'While loop patterns'],
                ],
            ],
            [
                'title' => 'Week 4: Functions',
                'description' => 'Creating and using functions',
                'objectives' => 'Understand function parameters, return values, and scope',
                'position' => 4,
                'lessons' => [
                    ['title' => 'Function Basics', 'description' => 'Defining and calling functions'],
                    ['title' => 'Parameters and Arguments', 'description' => 'Passing data to functions'],
                    ['title' => 'Return Values', 'description' => 'Getting results from functions'],
                ],
            ],
            [
                'title' => 'Week 5: Arrays',
                'description' => 'Working with arrays and collections',
                'objectives' => 'Store and manipulate data in arrays',
                'position' => 5,
                'lessons' => [
                    ['title' => 'Array Basics', 'description' => 'Creating and accessing arrays'],
                    ['title' => 'Array Methods', 'description' => 'Common array operations'],
                    ['title' => 'Multidimensional Arrays', 'description' => 'Arrays of arrays'],
                ],
            ],
            [
                'title' => 'Week 6: Strings',
                'description' => 'String manipulation and text processing',
                'objectives' => 'Work with strings and text data',
                'position' => 6,
                'lessons' => [
                    ['title' => 'String Basics', 'description' => 'Creating and accessing strings'],
                    ['title' => 'String Methods', 'description' => 'Common string operations'],
                    ['title' => 'String Formatting', 'description' => 'Formatting output'],
                ],
            ],
            [
                'title' => 'Week 7: File I/O',
                'description' => 'Reading and writing files',
                'objectives' => 'Handle file operations in programs',
                'position' => 7,
                'lessons' => [
                    ['title' => 'Reading Files', 'description' => 'Opening and reading file contents'],
                    ['title' => 'Writing Files', 'description' => 'Creating and writing to files'],
                    ['title' => 'File Handling Best Practices', 'description' => 'Error handling and file management'],
                ],
            ],
            [
                'title' => 'Week 8: Error Handling',
                'description' => 'Debugging and exception handling',
                'objectives' => 'Write robust code with proper error handling',
                'position' => 8,
                'lessons' => [
                    ['title' => 'Debugging Basics', 'description' => 'Finding and fixing bugs'],
                    ['title' => 'Try/Catch Blocks', 'description' => 'Exception handling'],
                    ['title' => 'Common Errors', 'description' => 'Syntax, runtime, and logical errors'],
                ],
            ],
            [
                'title' => 'Week 9: Object-Oriented Programming',
                'description' => 'Introduction to OOP concepts',
                'objectives' => 'Understand classes, objects, and inheritance',
                'position' => 9,
                'lessons' => [
                    ['title' => 'Classes and Objects', 'description' => 'OOP fundamentals'],
                    ['title' => 'Methods and Properties', 'description' => 'Class members'],
                    ['title' => 'Inheritance', 'description' => 'Extending classes'],
                ],
            ],
            [
                'title' => 'Week 10: Data Structures',
                'description' => 'Advanced data structures',
                'objectives' => 'Learn about stacks, queues, and linked lists',
                'position' => 10,
                'lessons' => [
                    ['title' => 'Stacks', 'description' => 'LIFO data structure'],
                    ['title' => 'Queues', 'description' => 'FIFO data structure'],
                    ['title' => 'Linked Lists', 'description' => 'Dynamic data structures'],
                ],
            ],
            [
                'title' => 'Week 11: Algorithms',
                'description' => 'Introduction to algorithms',
                'objectives' => 'Understand basic algorithms and complexity',
                'position' => 11,
                'lessons' => [
                    ['title' => 'Sorting Algorithms', 'description' => 'Bubble, selection, insertion sort'],
                    ['title' => 'Searching Algorithms', 'description' => 'Linear and binary search'],
                    ['title' => 'Algorithm Complexity', 'description' => 'Big O notation'],
                ],
            ],
            [
                'title' => 'Week 12: Final Project',
                'description' => 'Capstone project and review',
                'objectives' => 'Apply all learned concepts in a final project',
                'position' => 12,
                'lessons' => [
                    ['title' => 'Project Planning', 'description' => 'Designing your project'],
                    ['title' => 'Implementation', 'description' => 'Building the project'],
                    ['title' => 'Review and Assessment', 'description' => 'Final review and submission'],
                ],
            ],
        ];

        foreach ($modules as $moduleData) {
            $module = Module::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'title' => $moduleData['title'],
                ],
                [
                    'description' => $moduleData['description'],
                    'objectives' => $moduleData['objectives'],
                    'position' => $moduleData['position'],
                    'is_required' => true,
                    'status' => 'published',
                    'created_by' => $admin->id,
                ]
            );

            foreach ($moduleData['lessons'] as $index => $lessonData) {
                Lesson::updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'title' => $lessonData['title'],
                    ],
                    [
                        'description' => $lessonData['description'],
                        'content' => "Full lesson content for {$lessonData['title']}. Covers key concepts, examples, and practical exercises.",
                        'objectives' => "Understand {$lessonData['title']} and apply it in practice.",
                        'duration_minutes' => 30,
                        'position' => $index + 1,
                        'lesson_type' => 'text',
                        'is_required' => true,
                        'status' => 'published',
                        'created_by' => $admin->id,
                    ]
                );
            }
        }
    }
}
