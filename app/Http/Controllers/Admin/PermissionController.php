<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        $roles = Role::with('permissions')->orderBy('id')->get();

        return view('admin.permissions.index', compact('roles'));
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        $selected = $role->permissions->pluck('id')->all();

        return view('admin.permissions.edit', compact('role', 'permissions', 'selected'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        // The admin role always keeps full access — never allow a lock-out.
        if ($role->slug === Role::ADMIN) {
            return back()->with('error', 'Admin role permissions are fixed and cannot be edited.');
        }

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.permissions.index')
            ->with('status', "Permissions updated for {$role->name}.");
    }
}
