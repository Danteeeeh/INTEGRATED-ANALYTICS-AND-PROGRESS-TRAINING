<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'course_categories' => ['view', 'create', 'update', 'delete'],
            'courses' => ['view', 'create', 'update', 'delete', 'publish', 'archive', 'duplicate', 'export'],
            'classes' => ['view', 'create', 'update', 'delete', 'archive', 'roster', 'export'],
            'enrollments' => ['view', 'create', 'update', 'delete', 'approve', 'reject', 'drop', 'transfer', 'bulk', 'export'],
            'academic_periods' => ['view', 'create', 'update', 'delete'],
            'users' => ['view', 'create', 'update', 'delete', 'import', 'export', 'reset_password', 'deactivate', 'reactivate'],
            'students' => ['view', 'create', 'update', 'delete', 'import', 'export'],
            'instructors' => ['view', 'create', 'update', 'delete', 'import', 'export'],
            'modules' => ['view', 'create', 'update', 'delete', 'reorder', 'publish'],
            'lessons' => ['view', 'create', 'update', 'delete', 'reorder', 'publish'],
            'materials' => ['view', 'create', 'update', 'delete', 'download', 'upload'],
            'media' => ['view', 'create', 'update', 'delete', 'download'],
            'assignments' => ['view', 'create', 'update', 'delete', 'grade', 'submit', 'resubmit', 'publish', 'close'],
            'assignment_submissions' => ['view', 'create', 'update', 'delete', 'grade', 'history'],
            'rubrics' => ['view', 'create', 'update', 'delete', 'attach', 'grade'],
            'quizzes' => ['view', 'create', 'update', 'delete', 'grade', 'attempt', 'publish', 'close'],
            'question_banks' => ['view', 'create', 'update', 'delete', 'import', 'export'],
            'questions' => ['view', 'create', 'update', 'delete', 'reuse'],
            'quiz_attempts' => ['view', 'create', 'delete', 'grade', 'review'],
            'discussions' => ['view', 'create', 'update', 'delete', 'pin', 'lock', 'moderate', 'subscribe'],
            'discussion_posts' => ['view', 'create', 'update', 'delete', 'reply', 'report'],
            'announcements' => ['view', 'create', 'update', 'delete', 'pin', 'schedule', 'publish'],
            'calendar' => ['view', 'create', 'update', 'delete'],
            'virtual_classes' => ['view', 'create', 'update', 'delete', 'join', 'start'],
            'attendance' => ['view', 'create', 'update', 'delete', 'mark', 'export', 'reports'],
            'grade_categories' => ['view', 'create', 'update', 'delete'],
            'grade_items' => ['view', 'create', 'update', 'delete'],
            'grades' => ['view', 'create', 'update', 'delete', 'override', 'release', 'history', 'export'],
            'grade_history' => ['view'],
            'feedback' => ['view', 'create', 'update', 'delete'],
            'progress' => ['view', 'update'],
            'course_completion' => ['view', 'update', 'requirements'],
            'competency_frameworks' => ['view', 'create', 'update', 'delete'],
            'competencies' => ['view', 'create', 'update', 'delete', 'map', 'evidence'],
            'learning_plans' => ['view', 'create', 'update', 'delete'],
            'badges' => ['view', 'create', 'update', 'delete', 'award', 'revoke'],
            'certificates' => ['view', 'create', 'update', 'delete', 'issue', 'download', 'verify'],
            'messages' => ['view', 'create', 'delete', 'send'],
            'notifications' => ['view', 'update', 'preferences'],
            'search' => ['view'],
            'reports' => ['view', 'export'],
            'analytics' => ['view', 'export'],
            'activity_logs' => ['view', 'export'],
            'audit_logs' => ['view', 'export'],
            'integrations' => ['view', 'update'],
            'settings' => ['view', 'update'],
            'permissions' => ['view', 'update'],
            'schedule' => ['view', 'create', 'update', 'delete'],
            'documents' => ['view', 'create', 'update', 'delete', 'download'],
        ];

        $ids = [];
        foreach ($permissions as $group => $actions) {
            foreach ($actions as $action) {
                $name = "{$group}.{$action}";
                $permission = Permission::updateOrCreate(
                    ['name' => $name],
                    [
                        'group' => $group,
                        'description' => ucfirst($action).' '.str_replace('_', ' ', $group),
                    ]
                );
                $ids[$name] = $permission->id;
            }
        }

        $admin = Role::where('slug', Role::ADMIN)->first();
        $instructor = Role::where('slug', Role::INSTRUCTOR)->first();
        $student = Role::where('slug', Role::STUDENT)->first();
        $registrar = Role::where('slug', Role::REGISTRAR)->first();

        // Admin: full institutional control (all permissions)
        $admin->permissions()->sync(array_values($ids));

        // Instructor: manage content within assigned courses/classes
        $instructorAllowed = [
            'courses.view', 'courses.update',
            'classes.view', 'classes.update', 'classes.roster',
            'enrollments.view',
            'academic_periods.view',
            'modules.view', 'modules.create', 'modules.update', 'modules.delete', 'modules.reorder', 'modules.publish',
            'lessons.view', 'lessons.create', 'lessons.update', 'lessons.delete', 'lessons.reorder', 'lessons.publish',
            'materials.view', 'materials.create', 'materials.update', 'materials.delete', 'materials.download', 'materials.upload',
            'media.view', 'media.create', 'media.update', 'media.delete', 'media.download',
            'assignments.view', 'assignments.create', 'assignments.update', 'assignments.delete', 'assignments.grade', 'assignments.publish', 'assignments.close',
            'assignment_submissions.view', 'assignment_submissions.grade', 'assignment_submissions.history',
            'rubrics.view', 'rubrics.create', 'rubrics.update', 'rubrics.delete', 'rubrics.attach', 'rubrics.grade',
            'quizzes.view', 'quizzes.create', 'quizzes.update', 'quizzes.delete', 'quizzes.grade', 'quizzes.publish', 'quizzes.close',
            'question_banks.view', 'question_banks.create', 'question_banks.update', 'question_banks.delete',
            'questions.view', 'questions.create', 'questions.update', 'questions.delete', 'questions.reuse',
            'quiz_attempts.view', 'quiz_attempts.grade', 'quiz_attempts.review',
            'discussions.view', 'discussions.create', 'discussions.update', 'discussions.delete', 'discussions.pin', 'discussions.lock', 'discussions.moderate',
            'discussion_posts.view', 'discussion_posts.reply', 'discussion_posts.report',
            'announcements.view', 'announcements.create', 'announcements.update', 'announcements.delete', 'announcements.publish',
            'calendar.view', 'calendar.create', 'calendar.update', 'calendar.delete',
            'virtual_classes.view', 'virtual_classes.create', 'virtual_classes.update', 'virtual_classes.delete', 'virtual_classes.start',
            'attendance.view', 'attendance.create', 'attendance.update', 'attendance.mark', 'attendance.export',
            'grade_categories.view', 'grade_categories.create', 'grade_categories.update',
            'grade_items.view', 'grade_items.create', 'grade_items.update',
            'grades.view', 'grades.create', 'grades.update', 'grades.release', 'grades.history',
            'grade_history.view',
            'feedback.view', 'feedback.create', 'feedback.update',
            'progress.view', 'progress.update',
            'course_completion.view', 'course_completion.update',
            'competencies.view', 'competencies.evidence',
            'badges.view', 'badges.award',
            'certificates.view', 'certificates.issue',
            'messages.view', 'messages.create', 'messages.send',
            'notifications.view', 'notifications.update', 'notifications.preferences',
            'search.view',
            'reports.view', 'reports.export',
            'analytics.view', 'analytics.export',
            'documents.view',
            'course_categories.view',
        ];
        $instructor->permissions()->sync($this->resolveIds($ids, $instructorAllowed));

        // Student: read-only access to own learning data + submit own work
        $studentAllowed = [
            'courses.view',
            'classes.view',
            'modules.view',
            'lessons.view',
            'materials.view', 'materials.download',
            'media.view', 'media.download',
            'assignments.view', 'assignments.submit', 'assignments.resubmit',
            'assignment_submissions.view', 'assignment_submissions.create',
            'rubrics.view',
            'quizzes.view', 'quizzes.attempt',
            'quiz_attempts.view', 'quiz_attempts.create',
            'discussions.view', 'discussions.create', 'discussions.subscribe',
            'discussion_posts.view', 'discussion_posts.create', 'discussion_posts.reply', 'discussion_posts.report',
            'announcements.view',
            'calendar.view',
            'virtual_classes.view', 'virtual_classes.join',
            'attendance.view',
            'grades.view',
            'feedback.view',
            'progress.view',
            'course_completion.view',
            'competencies.view',
            'learning_plans.view',
            'badges.view',
            'certificates.view', 'certificates.download', 'certificates.verify',
            'messages.view', 'messages.create', 'messages.send',
            'notifications.view', 'notifications.update', 'notifications.preferences',
            'search.view',
            'documents.view', 'documents.download',
            'enrollments.view',
        ];
        $student->permissions()->sync($this->resolveIds($ids, $studentAllowed));

        $registrarAllowed = [
            'students.view', 'students.create', 'students.update', 'students.import', 'students.export',
            'enrollments.view', 'enrollments.create', 'enrollments.update', 'enrollments.delete',
            'enrollments.drop', 'enrollments.bulk', 'enrollments.export',
            'classes.view',
            'courses.view',
            'academic_periods.view',
            'progress.view',
            'course_completion.view',
            'grades.view',
            'reports.view', 'reports.export',
            'search.view',
            'notifications.view',
        ];
        $registrar?->permissions()->sync($this->resolveIds($ids, $registrarAllowed));
    }

    protected function resolveIds(array $ids, array $names): array
    {
        $resolved = [];
        foreach ($names as $name) {
            if (isset($ids[$name])) {
                $resolved[] = $ids[$name];
            }
        }

        return $resolved;
    }
}
