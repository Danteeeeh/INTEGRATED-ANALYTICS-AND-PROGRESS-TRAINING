<?php

return [
    'login_max_attempts' => (int) env('LMS_LOGIN_MAX_ATTEMPTS', 5),
    'login_lockout_minutes' => (int) env('LMS_LOGIN_LOCKOUT_MINUTES', 15),
    'session_inactivity_timeout' => (int) env('LMS_SESSION_INACTIVITY_MINUTES', 30),
    'password_min_length' => (int) env('LMS_PASSWORD_MIN_LENGTH', 8),
    'api_rate_limit' => (int) env('LMS_API_RATE_LIMIT', 60),
    'export_chunk_size' => 500,
    'registration_default_status' => env('LMS_REGISTRATION_STATUS', 'pending'),
    'dev_seed_password' => env('LMS_DEV_SEED_PASSWORD', 'Password123!'),
];
