<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\UserRole;
use App\Repositories\Contracts\UserManagementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class UserManagementRepository implements UserManagementRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['organization', 'userRoles.role'])
            ->withCount(['createdDocuments', 'approvals']);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        return $query->latest('created_at')->paginate($perPage, ['*'], $pageName)->withQueryString();
    }

    public function findById(string $id): ?User
    {
        return User::query()->with(['organization', 'userRoles.role', 'userRoles.organization'])->find($id);
    }

    public function create(array $data): User
    {
        return User::query()->create($data);
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function hasActivity(User $user): bool
    {
        return $user->createdDocuments()->exists() || $user->approvals()->exists();
    }

    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }

    public function deleteUserRoles(User $user): void
    {
        UserRole::query()->where('user_id', $user->id)->delete();
    }

    public function addRoleToUser(User $user, string $roleId, ?string $organizationId = null): void
    {
        UserRole::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'role_id' => $roleId,
            'organization_id' => $organizationId,
            'assigned_at' => now(),
        ]);
    }
}
