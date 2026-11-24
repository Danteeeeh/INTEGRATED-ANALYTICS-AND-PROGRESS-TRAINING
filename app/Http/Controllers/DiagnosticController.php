<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnosticController extends Controller
{
    public function index()
    {
        // Keep full connection details available while developing locally.
        // Redact everywhere else so production, staging, and unexpected
        // APP_ENV values cannot expose connection details or raw errors.
        $redactSensitive = ! in_array(config('app.env'), ['local', 'testing'], true);

        $diagnostics = [
            'database_connection' => [
                'status' => 'unknown',
                'error' => null,
            ],
            'tables' => [],
            'users' => ['total' => null, 'active' => null],
            'environment' => [
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
                'app_url' => config('app.url'),
                'db_connection' => config('database.default'),
                'db_host' => $redactSensitive
                    ? '[redacted]'
                    : config('database.connections.' . config('database.default') . '.host'),
                'db_database' => $redactSensitive
                    ? '[redacted]'
                    : config('database.connections.' . config('database.default') . '.database'),
                'session_driver' => config('session.driver'),
                'cache_default' => config('cache.default'),
            ],
        ];

        try {
            DB::connection()->getPdo();
            $diagnostics['database_connection'] = [
                'status' => 'success',
                'database' => $redactSensitive ? '[redacted]' : DB::connection()->getDatabaseName(),
                'connection' => DB::connection()->getName(),
            ];
        } catch (\Throwable $e) {
            $diagnostics['database_connection'] = [
                'status' => 'failed',
                'error' => $this->errorMessage($e, $redactSensitive),
            ];
            $diagnostics['tables'] = [
                'users' => null,
                'roles' => null,
                'permissions' => null,
                'role_permissions' => null,
                'note' => 'Skipped — DB unreachable',
            ];
            $diagnostics['users'] = [
                'total' => null,
                'active' => null,
                'note' => 'Skipped — DB unreachable',
            ];

            return $this->diagnosticResponse($diagnostics);
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
                'error' => $this->errorMessage($e, $redactSensitive),
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
                'error' => $this->errorMessage($e, $redactSensitive),
            ];
        }

        return $this->diagnosticResponse($diagnostics);
    }

    private function errorMessage(\Throwable $e, bool $redactSensitive): string
    {
        return $redactSensitive ? 'Diagnostic failed.' : $e->getMessage();
    }

    private function diagnosticResponse(array $diagnostics)
    {
        return response()->json($diagnostics, 200, [], JSON_PRETTY_PRINT)
            ->header('Cache-Control', 'no-store, private');
    }
}
