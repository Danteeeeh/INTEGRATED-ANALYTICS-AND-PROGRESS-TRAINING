<?php

namespace App\Services;

use App\Mail\NotificationEmail;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function createNotification(array $data): Notification
    {
        return DB::transaction(function () use ($data) {
            $notification = Notification::create([
                'user_id' => $data['user_id'],
                'title' => $data['title'],
                'body' => $data['message'],
                'type' => $data['type'] ?? 'info',
                'link_url' => $data['link'] ?? null,
                'data' => $data['data'] ?? null,
                'read_at' => null,
                'status' => 'pending',
            ]);

            // Check if user wants email notifications for this type
            $this->sendEmailNotificationIfEnabled($notification);

            return $notification;
        });
    }

    public function createBulkNotification(array $userIds, array $data): void
    {
        DB::transaction(function () use ($userIds, $data) {
            $notifications = [];

            foreach ($userIds as $userId) {
                $notifications[] = [
                    'user_id' => $userId,
                    'title' => $data['title'],
                    'body' => $data['message'],
                    'type' => $data['type'] ?? 'info',
                    'link_url' => $data['link'] ?? null,
                    'data' => $data['data'] ?? null,
                    'read_at' => null,
                    'status' => 'pending',
                    'created_at' => now(),
                ];
            }

            Notification::insert($notifications);

            // Send email notifications to users who have enabled them
            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if ($user && $this->shouldSendEmailNotification($user, $data['type'] ?? 'info')) {
                    $this->sendEmailNotification($user, $data);
                }
            }
        });
    }

    public function markAsRead(Notification $notification): Notification
    {
        $notification->update([
            'read_at' => now(),
            'status' => 'read',
        ]);

        return $notification;
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'status' => 'read',
            ]);
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function getUserNotifications(int $userId, int $limit = 20)
    {
        return Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function sendAssignmentDueNotification(array $userIds, string $assignmentTitle, string $dueDate, string $link): void
    {
        $this->createBulkNotification($userIds, [
            'title' => 'Assignment Due Soon',
            'message' => "Assignment '{$assignmentTitle}' is due on {$dueDate}",
            'type' => 'assignment',
            'link' => $link,
            'data' => [
                'assignment_title' => $assignmentTitle,
                'due_date' => $dueDate,
            ],
        ]);
    }

    public function sendQuizScheduledNotification(array $userIds, string $quizTitle, string $scheduledDate, string $link): void
    {
        $this->createBulkNotification($userIds, [
            'title' => 'Quiz Scheduled',
            'message' => "Quiz '{$quizTitle}' is scheduled for {$scheduledDate}",
            'type' => 'quiz',
            'link' => $link,
            'data' => [
                'quiz_title' => $quizTitle,
                'scheduled_date' => $scheduledDate,
            ],
        ]);
    }

    public function sendGradePostedNotification(int $userId, string $gradeType, string $gradeValue, string $link): void
    {
        $this->createNotification([
            'user_id' => $userId,
            'title' => 'New Grade Posted',
            'message' => "Your grade for {$gradeType} has been posted: {$gradeValue}",
            'type' => 'grade',
            'link' => $link,
            'data' => [
                'grade_type' => $gradeType,
                'grade_value' => $gradeValue,
            ],
        ]);
    }

    public function sendAnnouncementNotification(array $userIds, string $announcementTitle, string $link): void
    {
        $this->createBulkNotification($userIds, [
            'title' => 'New Announcement',
            'message' => $announcementTitle,
            'type' => 'announcement',
            'link' => $link,
            'data' => [
                'announcement_title' => $announcementTitle,
            ],
        ]);
    }

    public function sendEnrollmentConfirmationNotification(int $userId, string $courseName, string $className): void
    {
        $this->createNotification([
            'user_id' => $userId,
            'title' => 'Enrollment Confirmed',
            'message' => "You have been successfully enrolled in {$courseName} - {$className}",
            'type' => 'enrollment',
            'link' => route('student.dashboard'),
            'data' => [
                'course_name' => $courseName,
                'class_name' => $className,
            ],
        ]);
    }

    public function sendCourseCompletionNotification(int $userId, string $courseName, string $certificateLink): void
    {
        $this->createNotification([
            'user_id' => $userId,
            'title' => 'Course Completed!',
            'message' => "Congratulations! You have completed {$courseName}",
            'type' => 'achievement',
            'link' => $certificateLink,
            'data' => [
                'course_name' => $courseName,
                'certificate_link' => $certificateLink,
            ],
        ]);
    }

    public function sendBadgeAwardedNotification(int $userId, string $badgeName, string $badgeDescription): void
    {
        $this->createNotification([
            'user_id' => $userId,
            'title' => 'Badge Awarded!',
            'message' => "You have been awarded the '{$badgeName}' badge: {$badgeDescription}",
            'type' => 'achievement',
            'link' => route('student.dashboard'),
            'data' => [
                'badge_name' => $badgeName,
                'badge_description' => $badgeDescription,
            ],
        ]);
    }

    public function sendVirtualClassReminderNotification(array $userIds, string $className, string $meetingTime, string $meetingLink): void
    {
        $this->createBulkNotification($userIds, [
            'title' => 'Virtual Class Reminder',
            'message' => "Virtual class '{$className}' starts at {$meetingTime}",
            'type' => 'virtual_class',
            'link' => $meetingLink,
            'data' => [
                'class_name' => $className,
                'meeting_time' => $meetingTime,
                'meeting_link' => $meetingLink,
            ],
        ]);
    }

    protected function shouldSendEmailNotification(User $user, string $notificationType): bool
    {
        $preference = UserPreference::where('user_id', $user->id)
            ->where('key', "email_notifications_{$notificationType}")
            ->first();

        // Default to false if preference not set
        return $preference ? $preference->value === 'true' : false;
    }

    protected function sendEmailNotificationIfEnabled(Notification $notification): void
    {
        $user = User::find($notification->user_id);
        if (! $user) {
            return;
        }

        if ($this->shouldSendEmailNotification($user, $notification->type)) {
            $this->sendEmailNotification($user, [
                'title' => $notification->title,
                'message' => $notification->body,
                'type' => $notification->type,
                'link' => $notification->link_url,
            ]);
        }
    }

    protected function sendEmailNotification(User $user, array $data): void
    {
        try {
            Mail::to($user->email)->send(new NotificationEmail($data));
        } catch (\Exception $e) {
            // Log error but don't fail the notification
            \Log::error('Failed to send email notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updateUserNotificationPreferences(int $userId, array $preferences): void
    {
        foreach ($preferences as $key => $value) {
            UserPreference::updateOrCreate(
                [
                    'user_id' => $userId,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                ]
            );
        }
    }

    public function getUserNotificationPreferences(int $userId): array
    {
        $preferences = UserPreference::where('user_id', $userId)->get();
        $defaultPreferences = [
            'email_notifications_info' => true,
            'email_notifications_assignment' => true,
            'email_notifications_quiz' => true,
            'email_notifications_grade' => true,
            'email_notifications_announcement' => true,
            'email_notifications_achievement' => true,
            'email_notifications_virtual_class' => true,
        ];

        foreach ($preferences as $preference) {
            $defaultPreferences[$preference->key] = $preference->value === 'true';
        }

        return $defaultPreferences;
    }

    public function deleteNotification(Notification $notification): bool
    {
        return $notification->delete();
    }

    public function deleteOldNotifications(int $days = 30): int
    {
        $cutoffDate = now()->subDays($days);

        return Notification::where('created_at', '<', $cutoffDate)
            ->whereNotNull('read_at')
            ->delete();
    }
}
