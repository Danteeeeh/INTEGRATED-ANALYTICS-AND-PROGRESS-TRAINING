<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\CalendarEvent;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Quiz;
use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Database\Seeder;

/**
 * Seeds notifications for every user so the bell is populated across the
 * whole system. Safe to re-run: keyed on user+title+created_at.
 */
class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::where('email', 'instructor@lms.local')->first()
            ?? User::where('role_id', 38)->first();
        $admin = User::where('email', 'admin@lms.local')->first()
            ?? User::where('role_id', 37)->first();

        $studentRoleId = \DB::table('roles')->where('slug', 'student')->value('id');
        $students = User::where('role_id', $studentRoleId)->pluck('id')->all();
        $allUsers = User::pluck('id')->all();

        $templates = [
            ['type' => 'grade', 'title' => 'New grade released', 'body' => 'A new grade has been posted for your recent assessment. Check your gradebook to see your score.', 'link' => 'student.gradebook'],
            ['type' => 'quiz', 'title' => 'Quiz published', 'body' => 'A new quiz is now available. You have a limited window to complete it — start it before the deadline.', 'link' => 'student.quizzes'],
            ['type' => 'assignment', 'title' => 'Assignment due soon', 'body' => 'An assignment is due soon. Make sure to submit your work before the deadline.', 'link' => 'student.assignments'],
            ['type' => 'announcement', 'title' => 'New announcement', 'body' => 'There is a new announcement from your instructor. Read it for important updates.', 'link' => 'student.announcements'],
            ['type' => 'virtual_class', 'title' => 'Virtual class scheduled', 'body' => 'A live virtual class has been scheduled. Join on time to participate.', 'link' => 'student.virtual_classes'],
            ['type' => 'attendance', 'title' => 'Attendance recorded', 'body' => 'Your attendance for the latest session has been recorded.', 'link' => 'student.attendance'],
            ['type' => 'enrollment', 'title' => 'Enrollment confirmed', 'body' => 'You are now enrolled in a new class. Welcome aboard!', 'link' => 'student.enrollments'],
            ['type' => 'performance', 'title' => 'Performance update', 'body' => 'Your learning signals have been refreshed. Check your dashboard for the latest insights.', 'link' => 'student.progress'],
        ];

        $created = 0;
        foreach ($students as $sid) {
            foreach ($templates as $i => $tpl) {
                // ~half are unread (recent), rest read
                $read = ($i % 2 === 0);
                $daysAgo = $i + 1;
                $createdAt = now()->subDays($daysAgo)->subHours(rand(1, 10));
                $readAt = $read ? $createdAt->copy()->addMinutes(rand(10, 300)) : null;

                Notification::firstOrCreate(
                    ['user_id' => $sid, 'title' => $tpl['title'], 'created_at' => $createdAt],
                    [
                        'type' => $tpl['type'],
                        'body' => $tpl['body'],
                        'notifiable_type' => \App\Models\User::class,
                        'notifiable_id' => $sid,
                        'data' => ['icon' => $this->iconFor($tpl['type'])],
                        'read_at' => $readAt,
                        'link_url' => null,
                        'channel' => 'in_app',
                        'status' => 'sent',
                        'is_read' => $read,
                    ]
                );
                $created++;
            }
        }

        // Instructor + admin notifications
        foreach ([$instructor, $admin] as $staff) {
            if (! $staff) continue;
            foreach ([
                ['type' => 'info', 'title' => 'Weekly summary ready', 'body' => 'Your weekly class summary is ready. Review student progress and attendance.', 'link' => null],
                ['type' => 'assignment', 'title' => 'New submission received', 'body' => 'A student submitted an assignment. Grade it from the gradebook.', 'link' => null],
                ['type' => 'performance', 'title' => 'System analytics updated', 'body' => 'The analytics dashboard has been refreshed with the latest data.', 'link' => null],
            ] as $i => $tpl) {
                $createdAt = now()->subDays($i + 1);
                Notification::firstOrCreate(
                    ['user_id' => $staff->id, 'title' => $tpl['title'], 'created_at' => $createdAt],
                    [
                        'type' => $tpl['type'],
                        'body' => $tpl['body'],
                        'notifiable_type' => \App\Models\User::class,
                        'notifiable_id' => $staff->id,
                        'data' => ['icon' => $this->iconFor($tpl['type'])],
                        'read_at' => null,
                        'link_url' => $tpl['link'],
                        'channel' => 'in_app',
                        'status' => 'sent',
                        'is_read' => false,
                    ]
                );
                $created++;
            }
        }

        $this->command?->info('NotificationSeeder: '.$created.' notifications created ('.Notification::count().' total).');
    }

    protected function iconFor(string $type): string
    {
        return match ($type) {
            'grade' => 'fa-graduation-cap',
            'quiz' => 'fa-question-circle',
            'assignment' => 'fa-file-lines',
            'announcement' => 'fa-bullhorn',
            'virtual_class' => 'fa-video',
            'attendance' => 'fa-calendar-check',
            'enrollment' => 'fa-user-plus',
            'performance' => 'fa-chart-line',
            'achievement' => 'fa-trophy',
            default => 'fa-circle-info',
        };
    }
}
