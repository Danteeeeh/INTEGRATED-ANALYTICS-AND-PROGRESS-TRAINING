<?php

use App\Http\Controllers\Admin\AcademicPeriodController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\AssignmentController as AdminAssignmentController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BadgeController as AdminBadgeController;
use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\CertificateController as AdminCertificateController;
use App\Http\Controllers\Admin\ClassController as AdminClassController;
use App\Http\Controllers\Admin\CompetencyController as AdminCompetencyController;
use App\Http\Controllers\Admin\CourseCategoryController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\DiscussionController as AdminDiscussionController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Admin\GradebookController as AdminGradebookController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\ModuleController as AdminModuleController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\QuestionBankController as AdminQuestionBankController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RubricController as AdminRubricController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VirtualClassController as AdminVirtualClassController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Registrar\ClassController as RegistrarClassController;
use App\Http\Controllers\Registrar\CourseController as RegistrarCourseController;
use App\Http\Controllers\Registrar\DashboardController as RegistrarDashboard;
use App\Http\Controllers\Registrar\EnrollmentController as RegistrarEnrollmentController;
use App\Http\Controllers\Registrar\ReportController as RegistrarReportController;
use App\Http\Controllers\Registrar\StudentController as RegistrarStudentController;

Route::get('/files/{mediaFile}/serve', [FileController::class, 'serve'])->name('files.serve');
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Instructor\AnnouncementController as InstructorAnnouncementController;
use App\Http\Controllers\Instructor\AssignmentController as InstructorAssignmentController;
use App\Http\Controllers\Instructor\AttendanceController as InstructorAttendanceController;
use App\Http\Controllers\Instructor\CalendarController as InstructorCalendarController;
use App\Http\Controllers\Instructor\ClassController as InstructorClassController;
use App\Http\Controllers\Instructor\CourseController as InstructorCourseController;
use App\Http\Controllers\Instructor\DashboardController as InstructorDashboard;
use App\Http\Controllers\Instructor\DiscussionController as InstructorDiscussionController;
use App\Http\Controllers\Instructor\EnrollmentController as InstructorEnrollmentController;
use App\Http\Controllers\Instructor\GradebookController as InstructorGradebookController;
use App\Http\Controllers\Instructor\LessonController as InstructorLessonController;
use App\Http\Controllers\Instructor\ModuleController as InstructorModuleController;
use App\Http\Controllers\Instructor\QuizController as InstructorQuizController;
use App\Http\Controllers\Instructor\RubricController as InstructorRubricController;
use App\Http\Controllers\Instructor\VirtualClassController as InstructorVirtualClassController;
use App\Http\Controllers\Student\AnnouncementController as StudentAnnouncementController;
use App\Http\Controllers\Student\AssignmentController as StudentAssignmentController;
use App\Http\Controllers\Student\ClassController as StudentClassController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\DashboardController as StudentDashboard;
use App\Http\Controllers\Student\DiscussionController as StudentDiscussionController;
use App\Http\Controllers\Student\EnrollmentController as StudentEnrollmentController;
use App\Http\Controllers\Student\GradebookController as StudentGradebookController;
use App\Http\Controllers\Student\LessonController as StudentLessonController;
use App\Http\Controllers\Student\ModuleController as StudentModuleController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use App\Http\Controllers\Student\VirtualClassController as StudentVirtualClassController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/certificate/verify/{code}', [CertificateController::class, 'verify'])->name('certificate.verify');

Route::get('/files/{mediaFile}', [FileController::class, 'download'])->name('files.download')->middleware('auth');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1'); // 5 attempts per minute
    Route::get('/register', [LoginController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [LoginController::class, 'register'])->middleware('throttle:10,1'); // 10 attempts per minute
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::put('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware('role:'.Role::ADMIN)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
        Route::post('/dashboard/clear-cache', [AdminDashboard::class, 'clearCache'])->name('dashboard.clear-cache');
        Route::get('/dashboard/real-time-stats', [AdminDashboard::class, 'getRealTimeStats'])->name('dashboard.real-time-stats');
        Route::get('/dashboard/analytics-json', [AdminDashboard::class, 'getAnalyticsJson'])->name('dashboard.analytics-json');
        Route::get('/analytics', [AdminDashboard::class, 'analytics'])->name('analytics');

        Route::get('search', [SearchController::class, 'index'])->name('search');

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::put('/', [SettingController::class, 'update'])->name('update');
        });

        Route::get('users/export', [UserController::class, 'export'])->name('users.export');
        Route::post('users/import', [UserController::class, 'import'])->name('users.import');
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('users', UserController::class);

        Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
        Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
        Route::resource('students', StudentController::class);

        Route::get('instructors/export', [InstructorController::class, 'export'])->name('instructors.export');
        Route::post('instructors/import', [InstructorController::class, 'import'])->name('instructors.import');
        Route::resource('instructors', InstructorController::class);

        Route::prefix('academic-periods')->name('academic_periods.')->group(function () {
            Route::get('/', [AcademicPeriodController::class, 'index'])->name('index');
            Route::get('/create', [AcademicPeriodController::class, 'create'])->name('create');
            Route::post('/', [AcademicPeriodController::class, 'store'])->name('store');
            Route::get('/{academicPeriod}', [AcademicPeriodController::class, 'show'])->name('show');
            Route::get('/{academicPeriod}/edit', [AcademicPeriodController::class, 'edit'])->name('edit');
            Route::put('/{academicPeriod}', [AcademicPeriodController::class, 'update'])->name('update');
            Route::delete('/{academicPeriod}', [AcademicPeriodController::class, 'destroy'])->name('destroy');
            Route::post('/{academicPeriod}/set-current', [AcademicPeriodController::class, 'setCurrent'])->name('set-current');
        });

        Route::resource('course_categories', CourseCategoryController::class)->parameters(['course_categories' => 'courseCategory']);

        Route::resource('courses', AdminCourseController::class);
        Route::post('courses/{course}/publish', [AdminCourseController::class, 'publish'])->name('courses.publish');
        Route::post('courses/{course}/unpublish', [AdminCourseController::class, 'unpublish'])->name('courses.unpublish');
        Route::post('courses/{course}/archive', [AdminCourseController::class, 'archive'])->name('courses.archive');
        Route::post('courses/{course}/duplicate', [AdminCourseController::class, 'duplicate'])->name('courses.duplicate');

        Route::prefix('courses/{course}')->name('courses.')->group(function () {
            Route::resource('modules', AdminModuleController::class)->except(['index', 'create', 'store']);
            Route::get('modules', [AdminModuleController::class, 'index'])->name('modules.index');
            Route::get('modules/create', [AdminModuleController::class, 'create'])->name('modules.create');
            Route::post('modules', [AdminModuleController::class, 'store'])->name('modules.store');
            Route::post('modules/reorder', [AdminModuleController::class, 'reorder'])->name('modules.reorder');

            Route::prefix('modules/{module}')->name('modules.')->group(function () {
                Route::resource('lessons', AdminLessonController::class)->except(['index', 'create', 'store']);
                Route::get('lessons', [AdminLessonController::class, 'index'])->name('lessons.index');
                Route::get('lessons/create', [AdminLessonController::class, 'create'])->name('lessons.create');
                Route::post('lessons', [AdminLessonController::class, 'store'])->name('lessons.store');
                Route::post('lessons/reorder', [AdminLessonController::class, 'reorder'])->name('lessons.reorder');
            });
        });

        Route::resource('classes', AdminClassController::class)->parameters(['classes' => 'class']);
        Route::post('classes/{class}/archive', [AdminClassController::class, 'archive'])->name('classes.archive');

        Route::resource('enrollments', AdminEnrollmentController::class);
        Route::post('enrollments/bulk', [AdminEnrollmentController::class, 'bulkStore'])->name('enrollments.bulk');
        Route::post('enrollments/{enrollment}/approve', [AdminEnrollmentController::class, 'approve'])->name('enrollments.approve');
        Route::post('enrollments/{enrollment}/reject', [AdminEnrollmentController::class, 'reject'])->name('enrollments.reject');
        Route::post('enrollments/{enrollment}/drop', [AdminEnrollmentController::class, 'drop'])->name('enrollments.drop');
        Route::post('enrollments/{enrollment}/transfer', [AdminEnrollmentController::class, 'transfer'])->name('enrollments.transfer');

        Route::resource('assignments', AdminAssignmentController::class);
        Route::post('assignments/{assignment}/publish', [AdminAssignmentController::class, 'publish'])->name('assignments.publish');
        Route::post('assignments/{assignment}/close', [AdminAssignmentController::class, 'close'])->name('assignments.close');

        Route::resource('rubrics', AdminRubricController::class);
        Route::post('rubrics/{rubric}/attach', [AdminRubricController::class, 'attach'])->name('rubrics.attach');

        Route::resource('quizzes', AdminQuizController::class);
        Route::post('quizzes/{quiz}/publish', [AdminQuizController::class, 'publish'])->name('quizzes.publish');
        Route::post('quizzes/{quiz}/close', [AdminQuizController::class, 'close'])->name('quizzes.close');

        Route::resource('question_banks', AdminQuestionBankController::class);
        Route::resource('questions', AdminQuestionController::class);

        Route::resource('discussions', AdminDiscussionController::class);
        Route::post('discussions/{discussion}/pin', [AdminDiscussionController::class, 'pin'])->name('discussions.pin');
        Route::post('discussions/{discussion}/lock', [AdminDiscussionController::class, 'lock'])->name('discussions.lock');

        Route::resource('announcements', AdminAnnouncementController::class);
        Route::post('announcements/{announcement}/pin', [AdminAnnouncementController::class, 'pin'])->name('announcements.pin');
        Route::post('announcements/{announcement}/publish', [AdminAnnouncementController::class, 'publish'])->name('announcements.publish');

        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/', [AdminCalendarController::class, 'index'])->name('index');
            Route::get('/create', [AdminCalendarController::class, 'create'])->name('create');
            Route::post('/', [AdminCalendarController::class, 'store'])->name('store');
            Route::get('/{event}', [AdminCalendarController::class, 'show'])->name('show');
            Route::get('/{event}/edit', [AdminCalendarController::class, 'edit'])->name('edit');
            Route::put('/{event}', [AdminCalendarController::class, 'update'])->name('update');
            Route::delete('/{event}', [AdminCalendarController::class, 'destroy'])->name('destroy');
        });

        Route::resource('virtual_classes', AdminVirtualClassController::class)->parameters(['virtual_classes' => 'virtualClass']);

        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [AdminAttendanceController::class, 'index'])->name('index');
            Route::get('/create', [AdminAttendanceController::class, 'create'])->name('create');
            Route::post('/', [AdminAttendanceController::class, 'store'])->name('store');
            Route::get('/{attendanceRecord}', [AdminAttendanceController::class, 'show'])->name('show');
            Route::get('/{attendanceRecord}/edit', [AdminAttendanceController::class, 'edit'])->name('edit');
            Route::put('/{attendanceRecord}', [AdminAttendanceController::class, 'update'])->name('update');
            Route::delete('/{attendanceRecord}', [AdminAttendanceController::class, 'destroy'])->name('destroy');
            Route::get('/export', [AdminAttendanceController::class, 'export'])->name('export');
        });

        Route::prefix('gradebook')->name('gradebook.')->group(function () {
            Route::get('/', [AdminGradebookController::class, 'index'])->name('index');
            Route::get('/classes/{class}', [AdminGradebookController::class, 'classView'])->name('class');
            Route::post('/grades/release', [AdminGradebookController::class, 'releaseGrades'])->name('grades.release');
            Route::get('/grades/history', [AdminGradebookController::class, 'gradeHistory'])->name('grades.history');
        });

        Route::prefix('competencies')->name('competencies.')->group(function () {
            Route::get('/', [AdminCompetencyController::class, 'index'])->name('index');
            Route::get('/create', [AdminCompetencyController::class, 'create'])->name('create');
            Route::post('/', [AdminCompetencyController::class, 'store'])->name('store');
            Route::get('/{competency}', [AdminCompetencyController::class, 'show'])->name('show');
            Route::get('/{competency}/edit', [AdminCompetencyController::class, 'edit'])->name('edit');
            Route::put('/{competency}', [AdminCompetencyController::class, 'update'])->name('update');
            Route::delete('/{competency}', [AdminCompetencyController::class, 'destroy'])->name('destroy');
        });

        Route::resource('badges', AdminBadgeController::class);
        Route::post('badges/{badge}/award', [AdminBadgeController::class, 'award'])->name('badges.award');

        Route::resource('certificates', AdminCertificateController::class);
        Route::post('certificates/{certificate}/issue', [AdminCertificateController::class, 'issue'])->name('certificates.issue');
        Route::get('certificates/{certificate}/download', [AdminCertificateController::class, 'download'])->name('certificates.download');

        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
            Route::post('/mark-read', [AdminNotificationController::class, 'markRead'])->name('mark-read');
            Route::post('/mark-all-read', [AdminNotificationController::class, 'markAllRead'])->name('mark-all-read');
            Route::get('/preferences', [AdminNotificationController::class, 'preferences'])->name('preferences');
            Route::put('/preferences', [AdminNotificationController::class, 'updatePreferences'])->name('preferences.update');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/enrollment', [ReportController::class, 'enrollment'])->name('enrollment');
            Route::get('/course-completion', [ReportController::class, 'courseCompletion'])->name('course-completion');
            Route::get('/student-performance', [ReportController::class, 'studentPerformance'])->name('student-performance');
            Route::get('/instructor-performance', [ReportController::class, 'instructorPerformance'])->name('instructor-performance');
            Route::get('/attendance', [ReportController::class, 'attendance'])->name('attendance');
            Route::get('/grade-distribution', [ReportController::class, 'gradeDistribution'])->name('grade-distribution');
            Route::any('/{type?}/export', [ReportController::class, 'export'])->name('export');
        });

        // Feedback routes
        Route::prefix('feedback')->name('feedback.')->group(function () {
            Route::post('/', [FeedbackController::class, 'store'])->name('store');
            Route::delete('/{feedback}', [FeedbackController::class, 'destroy'])->name('destroy');
            Route::post('/{feedback}/mark-read', [FeedbackController::class, 'markRead'])->name('mark-read');
        });

        Route::prefix('audit-logs')->name('audit_logs.')->group(function () {
            Route::get('/', [AuditLogController::class, 'index'])->name('index');
            Route::get('/{auditLog}', [AuditLogController::class, 'show'])->name('show');
            Route::get('/export', [AuditLogController::class, 'export'])->name('export');
        });
    });

    Route::middleware('role:'.Role::INSTRUCTOR)->prefix('instructor')->name('instructor.')->group(function () {
        Route::get('/dashboard', InstructorDashboard::class)->name('dashboard');
        Route::post('/dashboard/clear-cache', [InstructorDashboard::class, 'clearCache'])->name('dashboard.clear-cache');
        Route::get('/dashboard/export', [InstructorDashboard::class, 'exportData'])->name('dashboard.export');
        Route::get('/dashboard/real-time-stats', [InstructorDashboard::class, 'getRealTimeStats'])->name('dashboard.real-time-stats');
        Route::get('/dashboard/search', [InstructorDashboard::class, 'search'])->name('dashboard.search');
        Route::get('/dashboard/analytics', [InstructorDashboard::class, 'getAnalytics'])->name('dashboard.analytics');

        Route::prefix('courses')->name('courses.')->group(function () {
            Route::get('/', [InstructorCourseController::class, 'index'])->name('index');
            Route::get('/{course}', [InstructorCourseController::class, 'show'])->name('show');
            Route::get('/{course}/edit', [InstructorCourseController::class, 'edit'])->name('edit');
            Route::put('/{course}', [InstructorCourseController::class, 'update'])->name('update');

            Route::prefix('{course}')->group(function () {
                Route::prefix('modules')->name('modules.')->group(function () {
                    Route::get('/', [InstructorModuleController::class, 'index'])->name('index');
                    Route::get('/create', [InstructorModuleController::class, 'create'])->name('create');
                    Route::post('/', [InstructorModuleController::class, 'store'])->name('store');
                    Route::get('/{module}', [InstructorModuleController::class, 'show'])->name('show');
                    Route::get('/{module}/edit', [InstructorModuleController::class, 'edit'])->name('edit');
                    Route::put('/{module}', [InstructorModuleController::class, 'update'])->name('update');
                    Route::delete('/{module}', [InstructorModuleController::class, 'destroy'])->name('destroy');
                    Route::post('/reorder', [InstructorModuleController::class, 'reorder'])->name('reorder');
                    Route::post('/{module}/publish', [InstructorModuleController::class, 'publish'])->name('publish');
                    Route::post('/{module}/unpublish', [InstructorModuleController::class, 'unpublish'])->name('unpublish');

                    Route::prefix('{module}/lessons')->name('lessons.')->group(function () {
                        Route::get('/', [InstructorLessonController::class, 'index'])->name('index');
                        Route::get('/create', [InstructorLessonController::class, 'create'])->name('create');
                        Route::post('/', [InstructorLessonController::class, 'store'])->name('store');
                        Route::get('/{lesson}', [InstructorLessonController::class, 'show'])->name('show');
                        Route::get('/{lesson}/edit', [InstructorLessonController::class, 'edit'])->name('edit');
                        Route::put('/{lesson}', [InstructorLessonController::class, 'update'])->name('update');
                        Route::delete('/{lesson}', [InstructorLessonController::class, 'destroy'])->name('destroy');
                        Route::post('/reorder', [InstructorLessonController::class, 'reorder'])->name('reorder');

                        // Lesson Materials Management
                        Route::get('/{lesson}/materials', [InstructorLessonController::class, 'materials'])->name('materials');
                        Route::post('/{lesson}/materials', [InstructorLessonController::class, 'addMaterial'])->name('materials.add');
                        Route::put('/{lesson}/materials/{material}', [InstructorLessonController::class, 'updateMaterial'])->name('materials.update');
                        Route::delete('/{lesson}/materials/{material}', [InstructorLessonController::class, 'deleteMaterial'])->name('materials.delete');
                        Route::post('/{lesson}/materials/set-all-required', [InstructorLessonController::class, 'setAllMaterialsRequired'])->name('materials.set-all-required');
                        Route::post('/{lesson}/materials/set-all-optional', [InstructorLessonController::class, 'setAllMaterialsOptional'])->name('materials.set-all-optional');
                    });
                });

                Route::prefix('assignments')->name('assignments.')->group(function () {
                    Route::get('/', [InstructorAssignmentController::class, 'index'])->name('index');
                    Route::get('/create', [InstructorAssignmentController::class, 'create'])->name('create');
                    Route::post('/', [InstructorAssignmentController::class, 'store'])->name('store');
                    Route::get('/{assignment}', [InstructorAssignmentController::class, 'show'])->name('show');
                    Route::get('/{assignment}/edit', [InstructorAssignmentController::class, 'edit'])->name('edit');
                    Route::put('/{assignment}', [InstructorAssignmentController::class, 'update'])->name('update');
                    Route::delete('/{assignment}', [InstructorAssignmentController::class, 'destroy'])->name('destroy');
                    Route::post('/{assignment}/publish', [InstructorAssignmentController::class, 'publish'])->name('publish');
                    Route::post('/{assignment}/close', [InstructorAssignmentController::class, 'close'])->name('close');
                    Route::get('/{assignment}/submissions', [InstructorAssignmentController::class, 'submissions'])->name('submissions');
                    Route::get('/{assignment}/submissions/{submission}', [InstructorAssignmentController::class, 'showSubmission'])->name('submissions.show');
                    Route::post('/{assignment}/submissions/{submission}/grade', [InstructorAssignmentController::class, 'gradeSubmission'])->name('submissions.grade');
                });

                Route::prefix('quizzes')->name('quizzes.')->group(function () {
                    Route::get('/', [InstructorQuizController::class, 'index'])->name('index');
                    Route::get('/create', [InstructorQuizController::class, 'create'])->name('create');
                    Route::post('/', [InstructorQuizController::class, 'store'])->name('store');
                    Route::get('/{quiz}', [InstructorQuizController::class, 'show'])->name('show');
                    Route::get('/{quiz}/edit', [InstructorQuizController::class, 'edit'])->name('edit');
                    Route::put('/{quiz}', [InstructorQuizController::class, 'update'])->name('update');
                    Route::delete('/{quiz}', [InstructorQuizController::class, 'destroy'])->name('destroy');
                    Route::post('/{quiz}/publish', [InstructorQuizController::class, 'publish'])->name('publish');
                    Route::post('/{quiz}/close', [InstructorQuizController::class, 'close'])->name('close');
                    Route::get('/{quiz}/attempts', [InstructorQuizController::class, 'attempts'])->name('attempts');
                    Route::get('/{quiz}/attempts/{attempt}', [InstructorQuizController::class, 'showAttempt'])->name('attempts.show');
                    Route::post('/{quiz}/attempts/{attempt}/grade', [InstructorQuizController::class, 'gradeAttempt'])->name('attempts.grade');
                });

                Route::prefix('rubrics')->name('rubrics.')->group(function () {
                    Route::get('/', [InstructorRubricController::class, 'index'])->name('index');
                    Route::get('/create', [InstructorRubricController::class, 'create'])->name('create');
                    Route::post('/', [InstructorRubricController::class, 'store'])->name('store');
                    Route::get('/{rubric}', [InstructorRubricController::class, 'show'])->name('show');
                    Route::get('/{rubric}/edit', [InstructorRubricController::class, 'edit'])->name('edit');
                    Route::put('/{rubric}', [InstructorRubricController::class, 'update'])->name('update');
                    Route::delete('/{rubric}', [InstructorRubricController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('discussions')->name('discussions.')->group(function () {
                    Route::get('/', [InstructorDiscussionController::class, 'index'])->name('index');
                    Route::get('/create', [InstructorDiscussionController::class, 'create'])->name('create');
                    Route::post('/', [InstructorDiscussionController::class, 'store'])->name('store');
                    Route::get('/{discussion}', [InstructorDiscussionController::class, 'show'])->name('show');
                    Route::post('/{discussion}/pin', [InstructorDiscussionController::class, 'pin'])->name('pin');
                    Route::post('/{discussion}/lock', [InstructorDiscussionController::class, 'lock'])->name('lock');
                });

                Route::prefix('announcements')->name('announcements.')->group(function () {
                    Route::get('/', [InstructorAnnouncementController::class, 'index'])->name('index');
                    Route::get('/create', [InstructorAnnouncementController::class, 'create'])->name('create');
                    Route::post('/', [InstructorAnnouncementController::class, 'store'])->name('store');
                    Route::get('/{announcement}', [InstructorAnnouncementController::class, 'show'])->name('show');
                    Route::get('/{announcement}/edit', [InstructorAnnouncementController::class, 'edit'])->name('edit');
                    Route::put('/{announcement}', [InstructorAnnouncementController::class, 'update'])->name('update');
                    Route::delete('/{announcement}', [InstructorAnnouncementController::class, 'destroy'])->name('destroy');
                    Route::post('/{announcement}/pin', [InstructorAnnouncementController::class, 'pin'])->name('pin');
                });
            });
        });

        Route::prefix('classes')->name('classes.')->group(function () {
            Route::get('/', [InstructorClassController::class, 'index'])->name('index');
            Route::get('/{class}', [InstructorClassController::class, 'show'])->name('show');
            Route::get('/{class}/roster', [InstructorClassController::class, 'roster'])->name('roster');

            Route::prefix('{class}/attendance')->name('attendance.')->group(function () {
                Route::get('/', [InstructorAttendanceController::class, 'index'])->name('index');
                Route::get('/create', [InstructorAttendanceController::class, 'create'])->name('create');
                Route::post('/', [InstructorAttendanceController::class, 'store'])->name('store');
                Route::get('/{attendance}', [InstructorAttendanceController::class, 'show'])->name('show');
                Route::get('/{attendance}/edit', [InstructorAttendanceController::class, 'edit'])->name('edit');
                Route::put('/{attendance}', [InstructorAttendanceController::class, 'update'])->name('update');
            });

            Route::prefix('{class}/gradebook')->name('gradebook.')->group(function () {
                Route::get('/', [InstructorGradebookController::class, 'index'])->name('index');
                Route::post('/grades', [InstructorGradebookController::class, 'storeGrade'])->name('grades.store');
                Route::post('/grades/bulk', [InstructorGradebookController::class, 'storeBulkGrades'])->name('grades.bulk');
                Route::put('/grades/{grade}', [InstructorGradebookController::class, 'updateGrade'])->name('grades.update');
                Route::post('/grades/release', [InstructorGradebookController::class, 'releaseGrades'])->name('grades.release');
                Route::get('/export', [InstructorGradebookController::class, 'export'])->name('export');
                Route::get('/students/{student}', [InstructorGradebookController::class, 'studentGrades'])->name('student');
            });

            Route::prefix('{class}/calendar')->name('calendar.')->group(function () {
                Route::get('/', [InstructorCalendarController::class, 'index'])->name('index');
                Route::get('/create', [InstructorCalendarController::class, 'create'])->name('create');
                Route::post('/', [InstructorCalendarController::class, 'store'])->name('store');
            });

            Route::prefix('{class}/virtual_classes')->name('virtual_classes.')->group(function () {
                Route::get('/', [InstructorVirtualClassController::class, 'index'])->name('index');
                Route::get('/create', [InstructorVirtualClassController::class, 'create'])->name('create');
                Route::post('/', [InstructorVirtualClassController::class, 'store'])->name('store');
                Route::get('/{virtualClass}', [InstructorVirtualClassController::class, 'show'])->name('show');
                Route::post('/{virtualClass}/start', [InstructorVirtualClassController::class, 'start'])->name('start');
            });
        });

        Route::prefix('enrollments')->name('enrollments.')->group(function () {
            Route::get('/', [InstructorEnrollmentController::class, 'index'])->name('index');
            Route::get('/create', [InstructorEnrollmentController::class, 'create'])->name('create');
            Route::post('/', [InstructorEnrollmentController::class, 'store'])->name('store');
            Route::get('/{enrollment}', [InstructorEnrollmentController::class, 'show'])->name('show');
            Route::post('/{enrollment}/complete', [InstructorEnrollmentController::class, 'complete'])->name('complete');
            Route::post('/{enrollment}/drop', [InstructorEnrollmentController::class, 'drop'])->name('drop');
            Route::post('/{enrollment}/activate', [InstructorEnrollmentController::class, 'activate'])->name('activate');
            Route::get('/{enrollment}/edit', [InstructorEnrollmentController::class, 'edit'])->name('edit');
            Route::put('/{enrollment}', [InstructorEnrollmentController::class, 'update'])->name('update');
            Route::delete('/{enrollment}', [InstructorEnrollmentController::class, 'destroy'])->name('destroy');
        });
    });

    Route::middleware('role:'.Role::STUDENT)->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', StudentDashboard::class)->name('dashboard');
        Route::post('/dashboard/clear-cache', [StudentDashboard::class, 'clearCache'])->name('dashboard.clear-cache');
        Route::get('/dashboard/real-time-stats', [StudentDashboard::class, 'getRealTimeStats'])->name('dashboard.real-time-stats');
        Route::get('/dashboard/search', [StudentDashboard::class, 'search'])->name('dashboard.search');
        Route::get('/dashboard/analytics', [StudentDashboard::class, 'getAnalytics'])->name('dashboard.analytics');
        Route::get('courses', [StudentCourseController::class, 'index'])->name('courses.index');
        Route::get('enrollments', [StudentEnrollmentController::class, 'index'])->name('enrollments.index');
        Route::get('enrollments/{enrollment}', [StudentEnrollmentController::class, 'show'])->name('enrollments.show');

        Route::prefix('courses')->name('courses.')->group(function () {
            Route::get('/', [StudentCourseController::class, 'index'])->name('index');
            Route::get('/{course}', [StudentCourseController::class, 'show'])->name('show');

            Route::prefix('{course}')->group(function () {
                Route::prefix('modules')->name('modules.')->group(function () {
                    Route::get('/', [StudentModuleController::class, 'index'])->name('index');
                    Route::get('/{module}', [StudentModuleController::class, 'show'])->name('show');

                    Route::prefix('{module}/lessons')->name('lessons.')->group(function () {
                        Route::get('/{lesson}', [StudentLessonController::class, 'show'])->name('show');
                        Route::post('/{lesson}/complete', [StudentLessonController::class, 'complete'])->name('complete');
                    });
                });

                Route::prefix('assignments')->name('assignments.')->group(function () {
                    Route::get('/', [StudentAssignmentController::class, 'index'])->name('index');
                    Route::get('/{assignment}', [StudentAssignmentController::class, 'show'])->name('show');
                    Route::get('/{assignment}/submit', [StudentAssignmentController::class, 'submitForm'])->name('submit');
                    Route::post('/{assignment}/submit', [StudentAssignmentController::class, 'submit'])->name('submit.store');
                    Route::get('/{assignment}/submissions/{submission}', [StudentAssignmentController::class, 'showSubmission'])->name('submissions.show');
                });

                Route::prefix('quizzes')->name('quizzes.')->group(function () {
                    Route::get('/', [StudentQuizController::class, 'index'])->name('index');
                    Route::get('/{quiz}', [StudentQuizController::class, 'show'])->name('show');
                    Route::get('/{quiz}/attempt', [StudentQuizController::class, 'startAttempt'])->name('attempt.start');
                    Route::post('/{quiz}/attempt', [StudentQuizController::class, 'storeAttempt'])->name('attempt.store');
                    Route::get('/{quiz}/attempts/{attempt}', [StudentQuizController::class, 'showAttempt'])->name('attempts.show');
                });

                Route::prefix('discussions')->name('discussions.')->group(function () {
                    Route::get('/', [StudentDiscussionController::class, 'index'])->name('index');
                    Route::get('/{discussion}', [StudentDiscussionController::class, 'show'])->name('show');
                    Route::post('/{discussion}/subscribe', [StudentDiscussionController::class, 'subscribe'])->name('subscribe');
                    Route::post('/{discussion}/posts', [StudentDiscussionController::class, 'storePost'])->name('posts.store');
                });

                Route::prefix('announcements')->name('announcements.')->group(function () {
                    Route::get('/', [StudentAnnouncementController::class, 'index'])->name('index');
                    Route::get('/{announcement}', [StudentAnnouncementController::class, 'show'])->name('show');
                });
            });
        });

        Route::prefix('classes')->name('classes.')->group(function () {
            Route::get('/', [StudentClassController::class, 'index'])->name('index');
            Route::get('/{class}', [StudentClassController::class, 'show'])->name('show');
            Route::post('/{class}/enroll', [StudentClassController::class, 'enroll'])->name('enroll');
            Route::post('/{class}/drop', [StudentClassController::class, 'drop'])->name('drop');

            Route::prefix('{class}/gradebook')->name('gradebook.')->group(function () {
                Route::get('/', [StudentGradebookController::class, 'index'])->name('index');
            });

            Route::prefix('{class}/virtual_classes')->name('virtual_classes.')->group(function () {
                Route::get('/', [StudentVirtualClassController::class, 'index'])->name('index');
                Route::get('/{virtualClass}', [StudentVirtualClassController::class, 'show'])->name('show');
                Route::post('/{virtualClass}/join', [StudentVirtualClassController::class, 'join'])->name('join');
            });
        });
    });

    Route::middleware('role:'.Role::REGISTRAR)->prefix('registrar')->name('registrar.')->group(function () {
        Route::get('/dashboard', RegistrarDashboard::class)->name('dashboard');
        Route::post('/dashboard/clear-cache', [RegistrarDashboard::class, 'clearCache'])->name('dashboard.clear-cache');
        Route::get('/dashboard/real-time-stats', [RegistrarDashboard::class, 'getRealTimeStats'])->name('dashboard.real-time-stats');
        Route::get('/dashboard/search', [RegistrarDashboard::class, 'search'])->name('dashboard.search');
        Route::get('/dashboard/analytics', [RegistrarDashboard::class, 'getAnalytics'])->name('dashboard.analytics');
        Route::resource('students', RegistrarStudentController::class)->except(['destroy']);
        Route::get('enrollments', [RegistrarEnrollmentController::class, 'index'])->name('enrollments.index');
        Route::get('enrollments/create', [RegistrarEnrollmentController::class, 'create'])->name('enrollments.create');
        Route::post('enrollments', [RegistrarEnrollmentController::class, 'store'])->name('enrollments.store');
        Route::delete('enrollments/{enrollment}', [RegistrarEnrollmentController::class, 'destroy'])->name('enrollments.destroy');
        Route::get('classes', [RegistrarClassController::class, 'index'])->name('classes.index');
        Route::get('classes/{class}', [RegistrarClassController::class, 'show'])->name('classes.show');
        Route::get('courses', [RegistrarCourseController::class, 'index'])->name('courses.index');
        Route::get('courses/{course}', [RegistrarCourseController::class, 'show'])->name('courses.show');
        Route::get('reports', [RegistrarReportController::class, 'index'])->name('reports.index');
    });
});
