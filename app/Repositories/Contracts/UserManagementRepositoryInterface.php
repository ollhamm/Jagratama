<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserManagementRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator;

    public function findById(string $id): ?User;

    public function create(array $data): User;

    public function update(User $user, array $data): bool;

    public function hasActivity(User $user): bool;

    public function delete(User $user): bool;

    public function deleteUserRoles(User $user): void;

    public function addRoleToUser(User $user, string $roleId, ?string $organizationId = null): void;
}
