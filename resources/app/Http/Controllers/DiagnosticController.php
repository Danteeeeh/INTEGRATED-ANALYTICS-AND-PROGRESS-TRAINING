<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Role;

class DiagnosticController extends Controller
{
    public function index(Request $request)
    {
        $secret = $request->query('secret');
        if (config('app.env') === 'production' && $secret !== config('app.key')) {
            return response('Unauthorized - Add ?secret=<APP_KEY> to URL', 401);
        }

        $diagnostics = [
            'database_connection' => [
                'status' => 'unknown',
                'error' => null,
            ],
            'tables' => [],
            'users' => ['total' => null, 'active' => null],
            'user_list' => [],
            'seeded_accounts' => [],
            'environment' => [
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
                'app_url' => config('app.url'),
                'db_connection' => config('database.default'),
                'db_host' => config('database.connections.' . config('database.default') . '.host'),
                'db_database' => config('database.connections.' . config('database.default') . '.database'),
                'session_driver' => config('session.driver'),
                'cache_default' => config('cache.default'),
            ],
        ];

        try {
            DB::connection()->getPdo();
            $diagnostics['database_connection'] = [
                'status' => 'success',
                'database' => DB::connection()->getDatabaseName(),
                'connection' => DB::connection()->getName(),
            ];
        } catch (\Throwable $e) {
            $diagnostics['database_connection'] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
            $diagnostics['tables'] = [
                'users' => null,
                'roles' => null,
                'permissions' => null,
                'role_permissions' => null,
                'note' => 'Skipped — DB unreachable',
            ];
            $diagnostics['seeded_accounts'] = [
                'note' => 'Skipped — DB unreachable',
            ];
            $diagnostics['users'] = [
                'total' => null,
                'active' => null,
                'note' => 'Skipped — DB unreachable',
            ];
            $diagnostics['user_list'] = [];

            return response()->json($diagnostics, 200, [], JSON_PRETTY_PRINT);
        }

        try {
            $diagnostics['tables'] = [
                'users' => Schema::hasTable('users'),
                'roles' => Schema::hasTable('roles'),
                'permissions' => Schema::hasTable('permissions'),
                'role_permissions' => Schema::hasTable('role_permissions'),
            ];
        } catch (\Throwable $e) {
            $diagnostics['tables'] = [
                'error' => $e->getMessage(),
            ];
        }

        try {
            $diagnostics['users'] = [
                'total' => User::count(),
                'active' => User::where('status', 'active')->count(),
            ];
        } catch (\Throwable $e) {
            $diagnostics['users'] = [
                'total' => null,
                'active' => null,
                'error' => $e->getMessage(),
            ];
        }

        try {
            $diagnostics['user_list'] = User::with('role')->withoutRegistrar()->limit(20)->get()->map(function ($user) {
                return [
                    'email' => $user->email,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'role' => $user->role?->slug,
                    'status' => $user->status,
                    'password_hash_length' => strlen($user->password),
                ];
            });
        } catch (\Throwable $e) {
            $diagnostics['user_list'] = ['error' => $e->getMessage()];
        }

        $seededEmails = [
            'admin@lms.local',
            'instructor@lms.local',
            'student@lms.local',
            'johncedrickdayandante6@gmail.com',
        ];

        try {
            foreach ($seededEmails as $email) {
                $user = User::where('email', $email)->first();
                $diagnostics['seeded_accounts'][$email] = [
                    'exists' => $user !== null,
                    'password_valid' => $user ? \Illuminate\Support\Facades\Hash::check('Password123!', $user->password) : false,
                ];
            }
        } catch (\Throwable $e) {
            $diagnostics['seeded_accounts']['error'] = $e->getMessage();
        }

        return response()->json($diagnostics, 200, [], JSON_PRETTY_PRINT);
    }
}
