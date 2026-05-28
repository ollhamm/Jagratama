<?php

namespace App\Repositories\Eloquent;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BaseUserAccess
{
    private function hasGlobalAccess(User $user): bool
    {
        return $user->userRoles()
            ->where(function (Builder $q) {
                $q->whereNull('organization_id')
                    ->orWhereHas('role', fn (Builder $r) => $r->whereIn('code', [
                        'ADMIN',
                        'DIREKTUR',
                        'WADIR_II',
                        'WADIR_III',
                        // Role lintas organisasi — muncul di alur semua tipe ormawa
                        'PRESIDEN_BEM',
                        'MENTERI_MINAT_BAKAT_BEM',
                        'KOMISI_B_BLM',
                        'PENANGGUNG_JAWAB_MAHASISWA',
                        'KA_SUB_BAG_AKADEMIK',
                        'KA_BAG_AKADEMIK',
                        'KA_BAG_AKADEMIK_UMUM',
                    ]));
            })
            ->exists();
    }

    private function organizationIds(User $user): array
    {
        $directOrgIds = $user->userRoles()
            ->whereNotNull('organization_id')
            ->pluck('organization_id')
            ->unique()
            ->all();

        if (empty($directOrgIds)) {
            return [];
        }

        $orgs = Organization::query()
            ->whereIn('id', $directOrgIds)
            ->with('children.children.children')
            ->get();

        $allIds = collect($directOrgIds);

        foreach ($orgs as $org) {
            $allIds = $allIds->merge(
                $org->descendants()->pluck('id')
            );
        }

        return $allIds->unique()->values()->all();
    }
}
