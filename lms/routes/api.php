<?php

use App\Http\Controllers\Api\V1\AcademicPeriodController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\ClassController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\FileController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1'); // Stricter rate limiting for login

    Route::middleware('auth:sanctum')->middleware('throttle:60,1')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Academic Periods
        Route::middleware('permission:academic_periods.view')->group(function () {
            Route::get('/academic-periods', [AcademicPeriodController::class, 'index']);
            Route::get('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'show']);
        });

        Route::middleware('permission:academic_periods.create')->group(function () {
            Route::post('/academic-periods', [AcademicPeriodController::class, 'store']);
        });

        Route::middleware('permission:academic_periods.update')->group(function () {
            Route::put('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'update']);
        });

        Route::middleware('permission:academic_periods.delete')->group(function () {
            Route::delete('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'destroy']);
        });

        // Courses
        Route::middleware('permission:courses.view')->group(function () {
            Route::get('/courses', [CourseController::class, 'index']);
            Route::get('/courses/{course}', [CourseController::class, 'show']);
        });

        Route::middleware('permission:courses.create')->group(function () {
            Route::post('/courses', [CourseController::class, 'store']);
        });

        Route::middleware('permission:courses.update')->group(function () {
            Route::put('/courses/{course}', [CourseController::class, 'update']);
        });

        Route::middleware('permission:courses.delete')->group(function () {
            Route::delete('/courses/{course}', [CourseController::class, 'destroy']);
        });

        // Classes
        Route::middleware('permission:classes.view')->group(function () {
            Route::get('/classes', [ClassController::class, 'index']);
            Route::get('/classes/{class}', [ClassController::class, 'show']);
        });

        Route::middleware('permission:classes.create')->group(function () {
            Route::post('/classes', [ClassController::class, 'store']);
        });

        Route::middleware('permission:classes.update')->group(function () {
            Route::put('/classes/{class}', [ClassController::class, 'update']);
        });

        Route::middleware('permission:classes.delete')->group(function () {
            Route::delete('/classes/{class}', [ClassController::class, 'destroy']);
        });

        // Enrollments
        Route::middleware('permission:enrollments.view')->group(function () {
            Route::get('/enrollments', [EnrollmentController::class, 'index']);
            Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show']);
        });

        Route::middleware('permission:enrollments.create')->group(function () {
            Route::post('/enrollments', [EnrollmentController::class, 'store']);
        });

        Route::middleware('permission:enrollments.update')->group(function () {
            Route::put('/enrollments/{enrollment}', [EnrollmentController::class, 'update']);
        });

        Route::middleware('permission:enrollments.delete')->group(function () {
            Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);
        });

        // File Management
        Route::middleware('permission:media.upload')->group(function () {
            Route::post('/files/upload', [FileController::class, 'upload']);
            Route::post('/files/upload-multiple', [FileController::class, 'uploadMultiple']);
        });

        Route::middleware('permission:media.delete')->group(function () {
            Route::delete('/files', [FileController::class, 'delete']);
        });

        Route::get('/files/info', [FileController::class, 'info']);

        // Users (Admin only)
        Route::middleware('permission:users.view')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{user}', [UserController::class, 'show']);
        });

        Route::middleware('permission:users.create')->group(function () {
            Route::post('/users', [UserController::class, 'store']);
        });

        Route::middleware('permission:users.update')->group(function () {
            Route::put('/users/{user}', [UserController::class, 'update']);
        });

        Route::middleware('permission:users.delete')->group(function () {
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
        });
    });
});
