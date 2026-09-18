<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

/**
 * Live probe of every user-related route across all four roles.
 * Reports the real HTTP status for each, so "does it work?" is answered
 * with evidence instead of inference.
 */
class UserFunctionAuditTest extends TestCase
{
    use CreatesLmsUsers;

    public function test_probe_all_user_function_routes(): void
    {
        $admin = $this->makeUser(Role::ADMIN);
        $instructor = $this->makeUser(Role::INSTRUCTOR);
        $student = $this->makeUser(Role::STUDENT);
        $registrar = $this->makeUser(Role::REGISTRAR);

        $target = User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->value('id'),
            'status' => 'active',
        ]);

        $probes = [
            // --- public / auth ---
            ['GET', '/login', null],
            ['GET', '/register', null],
            ['GET', '/', null],

            // --- profile (all authenticated users) ---
            ['GET', '/profile', 'admin'],
            ['GET', '/profile', 'instructor'],
            ['GET', '/profile', 'student'],
            ['GET', '/profile', 'registrar'],

            // --- admin user management ---
            ['GET', '/admin/users', 'admin'],
            ['GET', '/admin/users/create', 'admin'],
            ['GET', '/admin/users/'.$target->id, 'admin'],
            ['GET', '/admin/users/'.$target->id.'/edit', 'admin'],
            ['GET', '/admin/users/export', 'admin'],
            ['POST', '/admin/users/import', 'admin'],
            ['POST', '/admin/users/'.$target->id.'/deactivate', 'admin'],
            ['POST', '/admin/users/'.$target->id.'/reactivate', 'admin'],
            ['POST', '/admin/users/'.$target->id.'/reset-password', 'admin'],

            // --- admin student management ---
            ['GET', '/admin/students', 'admin'],
            ['GET', '/admin/students/create', 'admin'],
            ['GET', '/admin/students/'.$target->id, 'admin'],
            ['GET', '/admin/students/'.$target->id.'/edit', 'admin'],
            ['GET', '/admin/students/export', 'admin'],
            ['POST', '/admin/students/import', 'admin'],

            // --- admin instructor management ---
            ['GET', '/admin/instructors', 'admin'],
            ['GET', '/admin/instructors/create', 'admin'],
            ['GET', '/admin/instructors/'.$instructor->id, 'admin'],
            ['GET', '/admin/instructors/'.$instructor->id.'/edit', 'admin'],
            ['GET', '/admin/instructors/export', 'admin'],
            ['POST', '/admin/instructors/import', 'admin'],

            // --- admin other pages that appear in the sidebar ---
            ['GET', '/admin/dashboard', 'admin'],
            ['GET', '/admin/search?q=test', 'admin'],
            ['GET', '/admin/settings', 'admin'],
            ['GET', '/admin/audit-logs', 'admin'],
            ['GET', '/admin/announcements', 'admin'],
            ['GET', '/admin/attendance', 'admin'],
            ['GET', '/admin/badges', 'admin'],
            ['GET', '/admin/calendar', 'admin'],
            ['GET', '/admin/certificates', 'admin'],
            ['GET', '/admin/competencies', 'admin'],
            ['GET', '/admin/course_categories', 'admin'],
            ['GET', '/admin/courses', 'admin'],
            ['GET', '/admin/classes', 'admin'],
            ['GET', '/admin/enrollments', 'admin'],
            ['GET', '/admin/assignments', 'admin'],
            ['GET', '/admin/rubrics', 'admin'],
            ['GET', '/admin/quizzes', 'admin'],
            ['GET', '/admin/question_banks', 'admin'],
            ['GET', '/admin/questions', 'admin'],
            ['GET', '/admin/discussions', 'admin'],
            ['GET', '/admin/virtual_classes', 'admin'],
            ['GET', '/admin/gradebook', 'admin'],
            ['GET', '/admin/reports', 'admin'],
            ['GET', '/admin/academic-periods', 'admin'],
            ['GET', '/admin/notifications', 'admin'],
            ['GET', '/admin/notifications/preferences', 'admin'],

            // --- registrar ---
            ['GET', '/registrar/dashboard', 'registrar'],
            ['GET', '/registrar/students', 'registrar'],
            ['GET', '/registrar/students/create', 'registrar'],
            ['GET', '/registrar/students/'.$target->id, 'registrar'],
            ['GET', '/registrar/students/'.$target->id.'/edit', 'registrar'],
            ['GET', '/registrar/enrollments', 'registrar'],
            ['GET', '/registrar/enrollments/create', 'registrar'],
            ['GET', '/registrar/classes', 'registrar'],
            ['GET', '/registrar/courses', 'registrar'],
            ['GET', '/registrar/reports', 'registrar'],

            // --- instructor ---
            ['GET', '/instructor/dashboard', 'instructor'],
            ['GET', '/instructor/courses', 'instructor'],
            ['GET', '/instructor/classes', 'instructor'],
            ['GET', '/instructor/enrollments', 'instructor'],
            ['GET', '/instructor/enrollments/create', 'instructor'],

            // --- student ---
            ['GET', '/student/dashboard', 'student'],
            ['GET', '/student/courses', 'student'],
            ['GET', '/student/classes', 'student'],
            ['GET', '/student/enrollments', 'student'],
            ['GET', '/student/dashboard/analytics', 'student'],
        ];

        $actors = [
            'admin' => $admin,
            'instructor' => $instructor,
            'student' => $student,
            'registrar' => $registrar,
        ];

        $rows = [];
        $failures = [];

        foreach ($probes as [$method, $uri, $as]) {
            if ($as === null) {
                $response = $this->call($method, $uri);
            } else {
                $response = $this->actingAs($actors[$as])->call($method, $uri);
            }

            $status = $response->getStatusCode();
            $line = sprintf('%-6s %-45s as=%-11s => %d', $method, $uri, $as ?? 'guest', $status);

            if ($status >= 400) {
                $body = $response->getContent();
                // Laravel renders the exception message inside the error page.
                if (preg_match('/<title>(.*?)<\/title>/s', $body, $match)) {
                    $line .= '  || '.trim(html_entity_decode(strip_tags($match[1])));
                }

                if (preg_match('/(View \[[^\]]+\] not found|Call to undefined method [^<"\']{0,80}|Class "[^"]+" not found|Target class [^<]+ does not exist)/', $body, $match)) {
                    $line .= '  >> '.$match[1];
                }

                $failures[] = $line;
            }

            $rows[] = $line;
        }

        $this->assertNotEmpty($rows);
        $this->assertSame([], $failures, "User-function audit found error responses:\n".implode("\n", $failures));
    }
}
