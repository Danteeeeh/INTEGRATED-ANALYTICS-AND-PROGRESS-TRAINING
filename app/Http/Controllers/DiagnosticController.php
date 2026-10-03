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
        // Simple security check - only allow in development or with the APP_KEY as secret
        $secret = $request->query('secret');
        if (config('app.env') === 'production' && $secret !== config('app.key')) {
            return response('Unauthorized - Add ?secret=<APP_KEY> to URL', 401);
        }

        $diagnostics = [];

        // 1. Database connection
        try {
            DB::connection()->getPdo();
            $diagnostics['database_connection'] = [
                'status' => 'success',
                'database' => DB::connection()->getDatabaseName(),
                'connection' => DB::connection()->getName(),
            ];
        } catch (\Exception $e) {
            $diagnostics['database_connection'] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }

        // 2. Check tables
        $diagnostics['tables'] = [
            'users' => Schema::hasTable('users'),
            'roles' => Schema::hasTable('roles'),
            'permissions' => Schema::hasTable('permissions'),
            'role_permissions' => Schema::hasTable('role_permissions'),
        ];

        // 3. User count
        $diagnostics['users'] = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
        ];

        // 4. List users
        $diagnostics['user_list'] = User::with('role')->get()->map(function ($user) {
            return [
                'email' => $user->email,
                'name' => $user->first_name . ' ' . $user->last_name,
                'role' => $user->role?->slug,
                'status' => $user->status,
                'password_hash_length' => strlen($user->password),
            ];
        });

        // 5. Check seeded accounts
        $seededEmails = [
            'admin@lms.local',
            'instructor@lms.local',
            'student@lms.local',
            'johncedrickdayandante6@gmail.com',
        ];

        $diagnostics['seeded_accounts'] = [];
        foreach ($seededEmails as $email) {
            $user = User::where('email', $email)->first();
            $diagnostics['seeded_accounts'][$email] = [
                'exists' => $user !== null,
                'password_valid' => $user ? \Illuminate\Support\Facades\Hash::check('Password123!', $user->password) : false,
            ];
        }

        // 6. Environment info
        $diagnostics['environment'] = [
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'db_connection' => config('database.default'),
            'db_host' => config('database.connections.' . config('database.default') . '.host'),
            'db_database' => config('database.connections.' . config('database.default') . '.database'),
        ];

        return response()->json($diagnostics, 200, [], JSON_PRETTY_PRINT);
    }
}
