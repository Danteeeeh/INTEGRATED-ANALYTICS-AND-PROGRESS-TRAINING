<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Program;
use App\Models\Section;
use Illuminate\Database\Seeder;

class DepartmentProgramSeeder extends Seeder
{
    public function run(): void
    {
        // Create Departments (mock data from external system)
        $departments = [
            [
                'name' => 'College of Engineering',
                'code' => 'COE',
                'description' => 'Engineering and technology programs',
            ],
            [
                'name' => 'College of Business',
                'code' => 'COB',
                'description' => 'Business and management programs',
            ],
            [
                'name' => 'College of Arts and Sciences',
                'code' => 'CAS',
                'description' => 'Liberal arts and sciences programs',
            ],
            [
                'name' => 'College of Information Technology',
                'code' => 'CIT',
                'description' => 'Information technology and computer science programs',
            ],
            [
                'name' => 'College of Health Sciences',
                'code' => 'CHS',
                'description' => 'Health and medical programs',
            ],
        ];

        $createdDepartments = [];
        foreach ($departments as $dept) {
            $createdDepartments[$dept['code']] = Department::firstOrCreate(
                ['code' => $dept['code']],
                $dept
            );
        }

        // Create Programs under each Department
        $programs = [
            'COE' => [
                ['name' => 'Bachelor of Science in Civil Engineering', 'code' => 'BSCE'],
                ['name' => 'Bachelor of Science in Mechanical Engineering', 'code' => 'BSME'],
                ['name' => 'Bachelor of Science in Electrical Engineering', 'code' => 'BSEE'],
            ],
            'COB' => [
                ['name' => 'Bachelor of Science in Business Administration', 'code' => 'BSBA'],
                ['name' => 'Bachelor of Science in Accountancy', 'code' => 'BSA'],
                ['name' => 'Bachelor of Science in Entrepreneurship', 'code' => 'BSE'],
            ],
            'CAS' => [
                ['name' => 'Bachelor of Arts in Communication', 'code' => 'BAC'],
                ['name' => 'Bachelor of Science in Psychology', 'code' => 'BSP'],
                ['name' => 'Bachelor of Arts in Political Science', 'code' => 'BAPS'],
            ],
            'CIT' => [
                ['name' => 'Bachelor of Science in Computer Science', 'code' => 'BSCS'],
                ['name' => 'Bachelor of Science in Information Technology', 'code' => 'BSIT'],
                ['name' => 'Bachelor of Science in Information Systems', 'code' => 'BSIS'],
            ],
            'CHS' => [
                ['name' => 'Bachelor of Science in Nursing', 'code' => 'BSN'],
                ['name' => 'Bachelor of Science in Medical Technology', 'code' => 'BSMT'],
                ['name' => 'Bachelor of Science in Pharmacy', 'code' => 'BSPH'],
            ],
        ];

        $createdPrograms = [];
        foreach ($programs as $deptCode => $deptPrograms) {
            $department = $createdDepartments[$deptCode];
            foreach ($deptPrograms as $prog) {
                $createdPrograms[$prog['code']] = Program::firstOrCreate(
                    ['code' => $prog['code']],
                    array_merge($prog, ['department_id' => $department->id])
                );
            }
        }

        // Create Sections under each Program
        $sections = [
            'BSCE' => ['Section A', 'Section B', 'Section C'],
            'BSME' => ['Section A', 'Section B'],
            'BSEE' => ['Section A', 'Section B', 'Section C'],
            'BSBA' => ['Section A', 'Section B', 'Section C', 'Section D'],
            'BSA' => ['Section A', 'Section B'],
            'BSE' => ['Section A', 'Section B'],
            'BAC' => ['Section A', 'Section B', 'Section C'],
            'BSP' => ['Section A', 'Section B'],
            'BAPS' => ['Section A', 'Section B'],
            'BSCS' => ['Section A', 'Section B', 'Section C', 'Section D'],
            'BSIT' => ['Section A', 'Section B', 'Section C', 'Section D', 'Section E'],
            'BSIS' => ['Section A', 'Section B'],
            'BSN' => ['Section A', 'Section B', 'Section C'],
            'BSMT' => ['Section A', 'Section B'],
            'BSPH' => ['Section A', 'Section B'],
        ];

        foreach ($sections as $progCode => $progSections) {
            $program = $createdPrograms[$progCode];
            $academicPeriodId = 1; // Assuming first academic period exists

            foreach ($progSections as $sectionName) {
                Section::firstOrCreate(
                    [
                        'program_id' => $program->id,
                        'academic_period_id' => $academicPeriodId,
                        'name' => $sectionName,
                        'code' => $progCode . '-' . substr($sectionName, -1),
                    ],
                    [
                        'max_students' => 40,
                    ]
                );
            }
        }

        $this->command->info('✓ Mock Department, Program, and Section data seeded successfully!');
    }
}
