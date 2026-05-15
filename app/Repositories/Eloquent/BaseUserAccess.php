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
                'WADIR_II',
                'WADIR_III',
                'KAPRODI',
                'KAJUR',
                // Role lintas organisasi — muncul di alur semua tipe ormawa
                'PRESIDEN_BEM',
                'MENTERI_MINAT_BAKAT_BEM',
                'KOMISI_B_BLM',
                'PENANGGUNG_JAWAB_MAHASISWA',
                'KA_SUB_BAG_AKADEMIK',
                'KA_BAG_AKADEMIK',
                'KA_BAG_AKADEMIK_UMUM',
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
