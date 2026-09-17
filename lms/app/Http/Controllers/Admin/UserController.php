<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function __construct(
        private UserService $users,
        private AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = $this->users->getAllUsers($request->only(['search', 'status', 'role_slug', 'role_id', 'sort']));
        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->users->createUser($request->validated());

        $this->audit->log($request->user(), 'user.created', User::class, $user->id, null, [
            'email' => $user->email,
            'role_id' => $user->role_id,
        ], $request);

        return redirect()->route('admin.users.index')->with('status', 'User created successfully.');
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user = $this->users->getUserById($user->id);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $old = $user->only(['email', 'role_id', 'status']);
        $updated = $this->users->updateUser($user->id, $request->validated());

        $this->audit->log($request->user(), 'user.updated', User::class, $user->id, $old, $updated->only(['email', 'role_id', 'status']), $request);

        return redirect()->route('admin.users.index')->with('status', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->users->deactivateUser($user->id);
        $this->audit->log(request()->user(), 'user.deactivated', User::class, $user->id, ['status' => $user->status], ['status' => 'inactive'], request());

        return redirect()->route('admin.users.index')->with('status', 'User deactivated.');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->users->deactivateUser($user->id);
        $this->audit->log(request()->user(), 'user.deactivated', User::class, $user->id, ['status' => 'active'], ['status' => 'inactive'], request());

        return back()->with('status', 'User deactivated.');
    }

    public function reactivate(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->users->reactivateUser($user->id);
        $this->audit->log(request()->user(), 'user.reactivated', User::class, $user->id, ['status' => $user->status], ['status' => 'active'], request());

        return back()->with('status', 'User reactivated.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->users->resetPassword($user->id, $validated['password']);
        $this->audit->log($request->user(), 'user.password_reset', User::class, $user->id, null, null, $request);

        return back()->with('status', 'Password reset successfully.');
    }

    public function import(Request $request): RedirectResponse
    {
        if (! $request->user()->can('import', User::class)) {
            abort(403);
        }
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $result = $this->users->importFromCsv($request->file('file')->getRealPath());
        $this->audit->log($request->user(), 'user.imported', User::class, null, null, $result, $request);

        $message = "Imported {$result['created']} user(s).";
        if ($result['skipped']) {
            $message .= " Skipped {$result['skipped']} duplicate or invalid row(s).";
        }

        return back()->with('status', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $filename = 'users-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Email', 'Identifier', 'Role', 'Status', 'Last Login']);

            User::with('role')
                ->when($request->filled('role_slug'), fn ($q) => $q->whereHas('role', fn ($r) => $r->where('slug', $request->string('role_slug'))))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim((string) $request->input('search'));
                    $q->where(function ($searchQuery) use ($search) {
                        $searchQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('identifier', 'like', "%{$search}%");
                    });
                })
                ->orderBy('id')
                ->chunk(config('lms.export_chunk_size'), function ($chunk) use ($handle) {
                    foreach ($chunk as $user) {
                        fputcsv($handle, [
                            $user->id,
                            $user->full_name,
                            $user->email,
                            $user->identifier,
                            $user->role?->slug,
                            $user->status,
                            optional($user->last_login_at)?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
