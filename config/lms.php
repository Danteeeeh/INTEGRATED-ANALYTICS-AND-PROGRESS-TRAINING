<?php

return [
    'login_max_attempts' => (int) env('LMS_LOGIN_MAX_ATTEMPTS', 5),
    'login_lockout_minutes' => (int) env('LMS_LOGIN_LOCKOUT_MINUTES', 15),
    'session_inactivity_timeout' => (int) env('LMS_SESSION_INACTIVITY_MINUTES', 30),
    'password_min_length' => (int) env('LMS_PASSWORD_MIN_LENGTH', 8),
    'api_rate_limit' => (int) env('LMS_API_RATE_LIMIT', 60),
    'export_chunk_size' => 500,
    // Minimum class grade (percent) considered a passing mark. Used by the
    // gradebook and Performance Analytics so both agree on who is at risk.
    'passing_grade' => (int) env('LMS_PASSING_GRADE', 60),
    'registration_default_status' => env('LMS_REGISTRATION_STATUS', 'pending'),
    'dev_seed_password' => env('LMS_DEV_SEED_PASSWORD', 'Password123!'),

    /**
     * Upload ceilings, in kilobytes, because that is what Laravel's `max:`
     * validation rule counts in.
     *
     * These used to be hardcoded per controller and had drifted apart — a
     * profile photo allowed 2MB while a lesson material allowed 200MB — so a
     * file could be accepted by one screen and refused by another with no
     * obvious reason.
     *
     * IMPORTANT: these are an application-level ceiling only. Two things sit
     * below it and reject the request earlier, so raising these alone will not
     * make bigger uploads work:
     *
     *   php.ini   upload_max_filesize / post_max_size
     *   nginx     client_max_body_size
     *
     * A request that exceeds either is refused before Laravel runs, and nginx
     * answers with a bare "413 Request Entity Too Large" that no amount of
     * application code can catch or explain. Run `php artisan lms:check-uploads`
     * to compare all three.
     */
    'uploads' => [
        'profile_photo' => (int) env('LMS_UPLOAD_PROFILE_PHOTO_KB', 2048),
        'assignment_submission' => (int) env('LMS_UPLOAD_ASSIGNMENT_KB', 10240),
        'question_import' => (int) env('LMS_UPLOAD_IMPORT_KB', 10240),
        'learning_material' => (int) env('LMS_UPLOAD_LEARNING_MATERIAL_KB', 102400),
        'module_attachment' => (int) env('LMS_UPLOAD_MODULE_ATTACHMENT_KB', 204800),
        'lesson_material' => (int) env('LMS_UPLOAD_LESSON_MATERIAL_KB', 204800),
        'generic' => (int) env('LMS_UPLOAD_GENERIC_KB', 10240),
    ],

    'login_otp' => [
        'enabled' => (bool) env('LMS_LOGIN_OTP_ENABLED', true),
        'code_length' => 6,
        'expires_minutes' => (int) env('LMS_LOGIN_OTP_EXPIRES_MINUTES', 10),
        'max_attempts' => (int) env('LMS_LOGIN_OTP_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => (int) env('LMS_LOGIN_OTP_RESEND_COOLDOWN_SECONDS', 60),
        'pending_session_minutes' => (int) env('LMS_LOGIN_OTP_SESSION_MINUTES', 15),
        'exempt_emails' => [
            'admin@lms.local',
            'instructor@lms.local',
            'student@lms.local',
        ],
    ],
];
