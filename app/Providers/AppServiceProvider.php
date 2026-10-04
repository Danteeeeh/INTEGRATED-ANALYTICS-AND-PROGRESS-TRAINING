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
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeHistory;
use App\Models\LearningPlan;
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
use App\Policies\CoursePolicy;
use App\Policies\DiscussionPolicy;
use App\Policies\DiscussionPostPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\GradeHistoryPolicy;
use App\Policies\GradePolicy;
use App\Policies\LearningPlanPolicy;
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
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $sessionDriver = $this->app['config']->get('session.driver', 'file');
        $cacheDefault = $this->app['config']->get('cache.default', 'file');

        $dbDrivers = ['database', 'dynamodb', 'memcached', 'redis'];
        $sessionDbBacked = in_array($sessionDriver, $dbDrivers, true);
        $cacheDbBacked = in_array($cacheDefault, $dbDrivers, true);

        if (! $sessionDbBacked && ! $cacheDbBacked) {
            return;
        }

        $dbReachable = $this->isDatabaseReachable();

        if ($sessionDbBacked && ! $dbReachable) {
            $fallback = env('SESSION_FALLBACK_DRIVER', 'file');
            $this->app['config']->set('session.driver', $fallback);
        }

        if ($cacheDbBacked && ! $dbReachable) {
            $fallback = env('CACHE_FALLBACK_STORE', 'file');
            $this->app['config']->set('cache.default', $fallback);
        }
    }

    private function isDatabaseReachable(): bool
    {
        $defaultConn = env('DB_CONNECTION', 'mysql');
        $config = $this->app['config']->get("database.connections.$defaultConn");
        if (! is_array($config)) {
            return false;
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 3306);
        $timeoutS = (int) ini_get('default_socket_timeout') ?: 3;
        $timeoutUs = min($timeoutS, 2) * 1000000;

        if ($host === '' || $host === null) {
            return false;
        }

        if (str_contains($host, '/')) {
            $errno = 0;
            $errstr = '';
            $fp = @stream_socket_client(
                'unix://'.$host,
                $errno,
                $errstr,
                2
            );
            if (is_resource($fp)) {
                fclose($fp);
                return true;
            }

            return false;
        }

        $fp = @fsockopen($host, $port, $errno, $errstr, 0);
        if (! is_resource($fp)) {
            return false;
        }

        stream_set_timeout($fp, 0, $timeoutUs);
        $meta = stream_get_meta_data($fp);
        fclose($fp);

        return empty($meta['timed_out']);
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(ClassModel::class, ClassPolicy::class);
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);
        Gate::policy(AcademicPeriod::class, AcademicPeriodPolicy::class);
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
        Gate::policy(Grade::class, GradePolicy::class);
        Gate::policy(GradeHistory::class, GradeHistoryPolicy::class);
        Gate::policy(MediaFile::class, MediaFilePolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(Badge::class, BadgePolicy::class);
        Gate::policy(Certificate::class, CertificatePolicy::class);
        Gate::policy(Competency::class, CompetencyPolicy::class);
        Gate::policy(LearningPlan::class, LearningPlanPolicy::class);
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

        // Share unread notifications with every view (admin/instructor/student shell)
        view()->composer('*', function ($view) {
            $user = auth()->user();
            if (! $user) {
                $view->with('sharedUnreadNotifications', collect());
                $view->with('sharedUnreadCount', 0);
                return;
            }
            $unread = Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
            $view->with('sharedUnreadNotifications', $unread);
            $view->with('sharedUnreadCount', Notification::where('user_id', $user->id)->whereNull('read_at')->count());
        });
    }
}
