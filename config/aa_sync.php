<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AA Sync — table & column mapping
    |--------------------------------------------------------------------------
    |
    | The LMS reads from the Academic Affairs (AA) database via the "aa"
    | connection (AA_DB_* env values in .env). Because the AA schema differs
    | per institution, every table and column reference below is configurable.
    | Point these at the actual AA tables/columns before running a sync.
    |
    */

    'tables' => [

        'departments' => [
            'table' => 'departments',
            'columns' => [
                'code' => 'code',
                'name' => 'name',
                'description' => 'description',
            ],
        ],

        'programs' => [
            'table' => 'programs',
            'columns' => [
                'code' => 'code',
                'name' => 'name',
                'department_code' => 'department_code', // resolved to department_id
                'description' => 'description',
            ],
        ],

        'sections' => [
            'table' => 'sections',
            'columns' => [
                'code' => 'code',
                'name' => 'name',
                'program_code' => 'program_code',   // resolved to program_id
                'max_students' => 'max_students',
            ],
        ],

        'students' => [
            'table' => 'students',
            'columns' => [
                'identifier' => 'student_no',
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'email' => 'email',
                'department_code' => 'department_code',
                'program_code' => 'program_code',
                'section_code' => 'section_code',
                'status' => 'status',
            ],
            // AA status value => LMS users.status value.
            'status_map' => [
                'active' => 'active',
                'enrolled' => 'active',
                'inactive' => 'inactive',
                'graduated' => 'inactive',
                'dropped' => 'inactive',
            ],
        ],

        'faculty' => [
            'table' => 'faculty',
            'columns' => [
                'identifier' => 'employee_no',
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'email' => 'email',
            ],
        ],

        'classes' => [
            'table' => 'classes',
            'columns' => [
                'code' => 'class_code',
                'course_code' => 'course_code',       // resolved to course_id
                'instructor_email' => 'instructor_email', // resolved to instructor_id
                'schedule' => 'schedule',
                'room' => 'room',
                'capacity' => 'capacity',
                'status' => 'status',
                'academic_period_code' => 'period_code', // nullable
            ],
        ],

        'enrollments' => [
            'table' => 'enrollments',
            'columns' => [
                'student_no' => 'student_no',   // resolved to student_id
                'class_code' => 'class_code',   // resolved to class_id
                'status' => 'status',
                'enrolled_at' => 'enrolled_at',
            ],
            // AA status value => LMS enrollments.status value.
            'status_map' => [
                'pending' => 'pending',
                'active' => 'active',
                'enrolled' => 'active',
                'completed' => 'completed',
                'passed' => 'completed',
                'failed' => 'completed',
                'dropped' => 'dropped',
                'withdrawn' => 'dropped',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync behaviour
    |--------------------------------------------------------------------------
    */

    // Which entities run, in this order, when the Super Admin clicks Sync.
    'sync_order' => ['departments', 'programs', 'sections', 'students', 'faculty', 'classes', 'enrollments'],

    // Default accounts for new students/faculty created by the sync.
    'default_student_password' => env('AA_SYNC_DEFAULT_PASSWORD', 'Welcome@123'),
    'default_faculty_password' => env('AA_SYNC_FACULTY_PASSWORD', 'Welcome@123'),

    // Force-reset the password of every synced account on each sync.
    // Off by default so accounts created once are not churned every sync.
    'reset_passwords' => false,

    // Never touch these email domains as students during sync.
    'skip_emails' => [],
];
