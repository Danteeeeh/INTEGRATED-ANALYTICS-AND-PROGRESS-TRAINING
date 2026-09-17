<?php

namespace App\Providers;

use App\Models\AcademicPeriod;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\Badge;
use App\Models\CalendarEvent;
use App\Models\Certificate;
use App\Models\ClassModel;
use App\Models\Competency;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeCategory;
use App\Models\GradeHistory;
use App\Models\Lesson;
use App\Models\MediaFile;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Rubric;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\VirtualClass;
use App\Policies\AcademicPeriodPolicy;
use App\Policies\AnnouncementPolicy;
use App\Policies\AssignmentPolicy;
use App\Policies\AssignmentSubmissionPolicy;
use App\Policies\AttendanceRecordPolicy;
use App\Policies\BadgePolicy;
use App\Policies\CalendarEventPolicy;
use App\Policies\CertificatePolicy;
use App\Policies\ClassPolicy;
use App\Policies\CompetencyPolicy;
use App\Policies\CourseCategoryPolicy;
use App\Policies\CoursePolicy;
use App\Policies\DiscussionPolicy;
use App\Policies\DiscussionPostPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\GradeCategoryPolicy;
use App\Policies\GradeHistoryPolicy;
use App\Policies\GradePolicy;
use App\Policies\LessonPolicy;
use App\Policies\MediaFilePolicy;
use App\Policies\ModulePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\QuestionBankPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\QuizAttemptPolicy;
use App\Policies\QuizPolicy;
use App\Policies\RubricPolicy;
use App\Policies\UserPolicy;
use App\Policies\UserPreferencePolicy;
use App\Policies\VirtualClassPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(ClassModel::class, ClassPolicy::class);
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);
        Gate::policy(AcademicPeriod::class, AcademicPeriodPolicy::class);
        Gate::policy(CourseCategory::class, CourseCategoryPolicy::class);
        Gate::policy(Module::class, ModulePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);
        Gate::policy(AssignmentSubmission::class, AssignmentSubmissionPolicy::class);
        Gate::policy(Rubric::class, RubricPolicy::class);
        Gate::policy(Quiz::class, QuizPolicy::class);
        Gate::policy(QuizAttempt::class, QuizAttemptPolicy::class);
        Gate::policy(QuestionBank::class, QuestionBankPolicy::class);
        Gate::policy(Question::class, QuestionPolicy::class);
        Gate::policy(Discussion::class, DiscussionPolicy::class);
        Gate::policy(DiscussionPost::class, DiscussionPostPolicy::class);
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(CalendarEvent::class, CalendarEventPolicy::class);
        Gate::policy(VirtualClass::class, VirtualClassPolicy::class);
        Gate::policy(AttendanceRecord::class, AttendanceRecordPolicy::class);
        Gate::policy(GradeCategory::class, GradeCategoryPolicy::class);
        Gate::policy(Grade::class, GradePolicy::class);
        Gate::policy(GradeHistory::class, GradeHistoryPolicy::class);
        Gate::policy(MediaFile::class, MediaFilePolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(Badge::class, BadgePolicy::class);
        Gate::policy(Certificate::class, CertificatePolicy::class);
        Gate::policy(Competency::class, CompetencyPolicy::class);
        Gate::policy(UserPreference::class, UserPreferencePolicy::class);

        Gate::define('viewAuditLogs', function (User $user): bool {
            return $user->hasPermission('audit_logs.view');
        });

        Gate::define('exportAuditLogs', function (User $user): bool {
            return $user->hasPermission('audit_logs.export');
        });

        Gate::define('permission', function (User $user, string $permission): bool {
            return $user->hasPermission($permission);
        });

        RateLimiter::for('login', function (Request $request) {
            $max = (int) config('lms.login_max_attempts', 5);

            return Limit::perMinute($max)->by($request->ip().'|'.strtolower((string) $request->input('email')));
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute((int) config('lms.api_rate_limit', 60))
                ->by($request->user()?->id ?: $request->ip());
        });

        // Custom Blade directive for file size formatting
        Blade::directive('formatFileSize', function ($bytes) {
            return "<?php echo \\App\\Helpers\\FileHelper::formatFileSize($bytes); ?>";
        });
    }
}
