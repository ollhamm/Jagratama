<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BaseUserAccess
{
    private function hasGlobalAccess(User $user): bool
    {
        return $user->userRoles()
            ->whereNull('organization_id')
            ->orWhereHas('role', fn (Builder $query) => $query->whereIn('code', [
                'ADMIN',
                'DIREKTUR',
                'WADIR_III',
                'KAPRODI',
                'KAJUR',
            ]))
            ->exists();
    }

    private function organizationIds(User $user): array
    {
        return $user->userRoles()
            ->whereNotNull('organization_id')
            ->pluck('organization_id')
            ->unique()
            ->values()
            ->all();
    }
}
