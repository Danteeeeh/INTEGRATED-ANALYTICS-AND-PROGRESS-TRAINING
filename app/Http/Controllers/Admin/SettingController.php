<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $settings = [
            'login_max_attempts' => config('lms.login_max_attempts'),
            'password_min_length' => config('lms.password_min_length'),
            'api_rate_limit' => config('lms.api_rate_limit'),
            'export_chunk_size' => config('lms.export_chunk_size'),
            'registration_default_status' => config('lms.registration_default_status'),
            'dev_seed_password' => config('lms.dev_seed_password'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'login_max_attempts' => 'required|integer|min:1|max:100',
            'password_min_length' => 'required|integer|min:4|max:64',
            'api_rate_limit' => 'required|integer|min:1|max:10000',
            'export_chunk_size' => 'required|integer|min:10|max:10000',
            'registration_default_status' => 'required|in:pending,active',
            'dev_seed_password' => 'nullable|string|min:4',
        ]);

        $old = [
            'login_max_attempts' => config('lms.login_max_attempts'),
            'password_min_length' => config('lms.password_min_length'),
            'api_rate_limit' => config('lms.api_rate_limit'),
            'export_chunk_size' => config('lms.export_chunk_size'),
            'registration_default_status' => config('lms.registration_default_status'),
            'dev_seed_password' => config('lms.dev_seed_password'),
        ];

        $envUpdates = [];
        $envUpdates['LMS_LOGIN_MAX_ATTEMPTS'] = $validated['login_max_attempts'];
        $envUpdates['LMS_PASSWORD_MIN_LENGTH'] = $validated['password_min_length'];
        $envUpdates['LMS_API_RATE_LIMIT'] = $validated['api_rate_limit'];
        $envUpdates['LMS_REGISTRATION_STATUS'] = $validated['registration_default_status'];

        if (isset($validated['dev_seed_password']) && ! empty($validated['dev_seed_password'])) {
            $envUpdates['LMS_DEV_SEED_PASSWORD'] = $validated['dev_seed_password'];
        }

        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            foreach ($envUpdates as $key => $value) {
                $pattern = '/^'.preg_quote($key, '/').'=.*/m';
                if (preg_match($pattern, $envContent)) {
                    $envContent = preg_replace($pattern, $key.'='.$value, $envContent);
                } else {
                    $envContent .= "\n".$key.'='.$value."\n";
                }
            }
            file_put_contents($envPath, $envContent);
        }

        $this->audit->log($request->user(), 'settings.updated', 'settings', null, $old, $validated, $request);

        session()->flash('success', 'Settings updated successfully.');

        return redirect()->route('admin.settings.index');
    }
}
