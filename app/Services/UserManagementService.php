<?php

namespace App\Services;

use App\Repositories\Contracts\UserManagementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UserManagementService
{
    public function __construct(private readonly UserManagementRepositoryInterface $users)
    {
    }

    public function paginate(array $filters, string $pageName = 'page'): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        return $this->users->paginate($filters, $perPage, $pageName);
    }

    public function findById(string $id)
    {
        return $this->users->findById($id);
    }

    public function create(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $user = $this->users->create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password_hash' => $payload['password'],
                'organization_id' => $payload['organization_id'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
            ]);

            foreach (Arr::wrap($payload['role_ids'] ?? []) as $roleId) {
                $this->users->addRoleToUser($user, $roleId, $payload['role_organization_id'] ?? null);
            }

            return $user;
        });
    }

    public function update(string $id, array $payload)
    {
        return DB::transaction(function () use ($id, $payload) {
            $user = $this->users->findById($id);
            if (! $user) {
                return null;
            }

            $updateData = [
                'name' => $payload['name'],
                'email' => $payload['email'],
                'organization_id' => $payload['organization_id'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? false),
            ];

            if (! empty($payload['password'])) {
                $updateData['password_hash'] = $payload['password'];
            }

            $this->users->update($user, $updateData);
            $this->users->deleteUserRoles($user);

            foreach (Arr::wrap($payload['role_ids'] ?? []) as $roleId) {
                $this->users->addRoleToUser($user, $roleId, $payload['role_organization_id'] ?? null);
            }

            return $this->users->findById($user->id);
        });
    }

    public function canDelete(string $id): bool
    {
        $user = $this->users->findById($id);
        if (! $user) {
            return false;
        }

        return ! $this->users->hasActivity($user);
    }

    public function delete(string $id): string
    {
        return DB::transaction(function () use ($id) {
            $user = $this->users->findById($id);
            if (! $user) {
                return 'not_found';
            }

            if ($this->users->hasActivity($user)) {
                return 'has_activity';
            }

            $this->users->delete($user);
            return 'deleted';
        });
    }
}
