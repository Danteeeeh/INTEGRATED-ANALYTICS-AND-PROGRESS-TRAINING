<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserService
{
    public function getAllUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with(['role', 'preferences']);

        if (filled($filters['role_id'] ?? null)) {
            $query->where('role_id', $filters['role_id']);
        }

        if (filled($filters['role_slug'] ?? null)) {
            $query->whereHas('role', function ($q) use ($filters) {
                $q->where('slug', $filters['role_slug']);
            });
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['search'] ?? null)) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%");
            });
        }

        $sort = filled($filters['sort'] ?? null) ? $filters['sort'] : 'newest';
        match ($sort) {
            'name' => $query->orderBy('last_name')->orderBy('first_name'),
            'last_login' => $query->orderByDesc('last_login_at'),
            'oldest' => $query->orderBy('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        return $query->paginate($perPage);
    }

    public function getUserById(int $id): User
    {
        return User::with(['role', 'preferences', 'enrollments.class.course'])->findOrFail($id);
    }

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'identifier' => $data['identifier'] ?? null,
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'profile_photo_path' => $data['profile_photo_path'] ?? null,
                'role_id' => $data['role_id'],
                'status' => $data['status'] ?? 'active',
            ]);

            return $user;
        });
    }

    public function updateUser(int $id, array $data): User
    {
        $user = User::findOrFail($id);

        $updateData = [
            'first_name' => $data['first_name'] ?? $user->first_name,
            'last_name' => $data['last_name'] ?? $user->last_name,
            'email' => $data['email'] ?? $user->email,
            'identifier' => $data['identifier'] ?? $user->identifier,
            'phone' => $data['phone'] ?? $user->phone,
            'address' => $data['address'] ?? $user->address,
            'profile_photo_path' => $data['profile_photo_path'] ?? $user->profile_photo_path,
            'role_id' => $data['role_id'] ?? $user->role_id,
            'status' => $data['status'] ?? $user->status,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        $user->update($updateData);

        return $user->fresh();
    }

    public function deleteUser(int $id): bool
    {
        $user = User::findOrFail($id);

        return $user->delete();
    }

    public function deactivateUser(int $id): User
    {
        $user = User::findOrFail($id);

        if ($user->isAdmin()) {
            $otherActiveAdmins = User::query()
                ->where('id', '!=', $user->id)
                ->where('status', 'active')
                ->whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN))
                ->exists();

            if (! $otherActiveAdmins) {
                throw new \RuntimeException('Cannot deactivate the last active administrator.');
            }
        }

        $user->update(['status' => 'inactive']);

        return $user->fresh();
    }

    public function reactivateUser(int $id): User
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);

        return $user->fresh();
    }

    public function resetPassword(int $id, string $newPassword): User
    {
        $user = User::findOrFail($id);
        $user->update([
            'password' => $newPassword,
        ]);

        return $user->fresh();
    }

    public function assignRole(int $userId, int $roleId): User
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);

        $user->update(['role_id' => $roleId]);

        return $user->fresh();
    }

    public function getStudents(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->getAllUsers(array_merge($filters, ['role_slug' => 'student']), $perPage);
    }

    public function getInstructors(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->getAllUsers(array_merge($filters, ['role_slug' => 'instructor']), $perPage);
    }

    public function getAdmins(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->getAllUsers(array_merge($filters, ['role_slug' => 'admin']), $perPage);
    }

    public function approveUser(int $id): User
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);

        return $user->fresh();
    }

    /**
     * @return array{created: int, skipped: int, errors: array<int, string>}
     */
    public function importFromCsv(string $absolutePath, ?string $requiredRoleSlug = null): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];

        $handle = fopen($absolutePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to read the import file.');
        }

        try {
            $header = fgetcsv($handle);
            if (! is_array($header)) {
                throw new \RuntimeException('The import file is empty.');
            }

            $header = array_map(fn ($column) => Str::of((string) $column)->trim()->lower()->replace(' ', '_')->toString(), $header);

            $rowNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->csvRowIsEmpty($row)) {
                    continue;
                }

                $record = [];
                foreach ($header as $index => $column) {
                    $record[$column] = isset($row[$index]) ? trim((string) $row[$index]) : '';
                }

                try {
                    $this->importRow($record, $requiredRoleSlug);
                    $created++;
                } catch (\Throwable $e) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: ".$e->getMessage();
                }
            }
        } finally {
            fclose($handle);
        }

        return compact('created', 'skipped', 'errors');
    }

    protected function importRow(array $record, ?string $requiredRoleSlug = null): User
    {
        $email = $record['email'] ?? '';
        $first = $record['first_name'] ?? '';
        $last = $record['last_name'] ?? '';
        $roleSlug = $record['role'] ?? $record['role_slug'] ?? Role::STUDENT;

        if ($email === '' || $first === '' || $last === '') {
            throw new \InvalidArgumentException('first_name, last_name, and email are required.');
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            throw new \InvalidArgumentException('Email already exists.');
        }

        $role = Role::where('slug', $roleSlug)->first();
        if (! $role) {
            throw new \InvalidArgumentException("Unknown role '{$roleSlug}'.");
        }

        if ($requiredRoleSlug !== null && $role->slug !== $requiredRoleSlug) {
            throw new \InvalidArgumentException("Only '{$requiredRoleSlug}' accounts can be imported here.");
        }

        if ($role->slug === Role::ADMIN) {
            throw new \InvalidArgumentException('Admin accounts cannot be created via CSV import.');
        }

        $password = ($record['password'] ?? '') !== ''
            ? $record['password']
            : Str::password(12);

        $status = $record['status'] ?? 'active';

        return $this->createUser([
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'identifier' => ($record['identifier'] ?? '') !== '' ? $record['identifier'] : null,
            'phone' => ($record['phone'] ?? '') !== '' ? $record['phone'] : null,
            'password' => $password,
            'role_id' => $role->id,
            'status' => in_array($status, ['active', 'inactive', 'suspended', 'pending'], true) ? $status : 'active',
        ]);
    }

    protected function csvRowIsEmpty(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    public function searchUsers(string $query, int $limit = 20): array
    {
        return User::with('role')
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('identifier', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function updateUserProfile(int $userId, array $data): User
    {
        $user = User::findOrFail($userId);

        $user->update([
            'first_name' => $data['first_name'] ?? $user->first_name,
            'last_name' => $data['last_name'] ?? $user->last_name,
            'phone' => $data['phone'] ?? $user->phone,
            'address' => $data['address'] ?? $user->address,
            'profile_photo_path' => $data['profile_photo_path'] ?? $user->profile_photo_path,
        ]);

        return $user->fresh();
    }

    public function updateLastLogin(int $userId): void
    {
        User::where('id', $userId)->update(['last_login_at' => now()]);
    }

    public function getUserStats(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'inactive_users' => User::where('status', 'inactive')->count(),
            'total_students' => User::whereHas('role', fn ($q) => $q->where('slug', 'student'))->count(),
            'total_instructors' => User::whereHas('role', fn ($q) => $q->where('slug', 'instructor'))->count(),
            'total_admins' => User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->count(),
            'pending_registrations' => User::where('status', 'pending')->count(),
            'recent_users' => User::with('role')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];
    }
}
