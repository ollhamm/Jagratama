<?php

namespace App\Helpers;

use App\Models\User;

class MenuHelper
{
    public static function getMainNavItems()
    {
        $items = [
            [
                'icon' => 'dashboard',
                'name' => 'Dashboard',
                'path' => '/dashboard',
                'roles' => [],

            ],
            [
                'icon' => 'forms',
                'name' => 'Pengajuan',
                'path' => '/app/documents',
                'roles' => ['PENGAJU', 'ADMIN'],
            ],
            [
                'icon' => 'upload',
                'name' => 'Publish Dokumen',
                'path' => '/app/publish',
                'roles' => ['PENGAJU', 'ADMIN', 'KOMISI_B_BLM'],
            ],
            [
                'icon' => 'task',
                'name' => 'Approval',
                'path' => '/app/approvals/pending',
                'roles' => [
                    'KETUA_SBH',
                    'KETUA_HMPS',
                    'KETUA_HMJ',
                    'KETUA_UKM',
                    'KETUA_BLM',
                    'PRESIDEN_BEM',
                    'KOMISI_B_BLM',
                    'PJ_MAHASISWA_ALUMNI_JURUSAN',
                    'PEMBINA_SBH',
                    'PEMBINA_UKM',
                    'MENTERI_MINAT_BAKAT_BEM',
                    'PENANGGUNG_JAWAB_MAHASISWA',
                    'ADMINISTRASI_AKADEMIK',
                    'KA_SUB_BAG_AKADEMIK',
                    'KA_BAG_AKADEMIK',
                    'KA_BAG_AKADEMIK_UMUM',
                    'KAPRODI',
                    'KAJUR',
                    'WADIR_II',
                    'WADIR_III',
                    'DIREKTUR',
                    'ADMIN',
                ],
            ],
            [
                'icon' => 'user-profile',
                'name' => 'User Management',
                'path' => '/app/users',
                'roles' => ['ADMIN'],
            ],
        ];

        $user = auth()->user();

        return self::filterItemsByRole($items, $user);
    }

    public static function getOthersItems()
    {
        return [

        ];
    }

    public static function getMenuGroups()
    {
        return [
            [
                'title' => 'Menu',
                'items' => self::getMainNavItems(),
            ],
        ];
    }

    public static function isActive($path)
    {
        return request()->is(ltrim($path, '/'));
    }

    public static function getFeatherIcon($iconName)
    {
        $icons = [
            'dashboard' => 'grid',
            'ai-assistant' => 'cpu',
            'ecommerce' => 'shopping-cart',
            'calendar' => 'calendar',
            'user-profile' => 'user',
            'task' => 'check-square',
            'forms' => 'file-text',
            'upload' => 'upload-cloud',
            'tables' => 'table',
            'documents' => 'file-text',
            'approval' => 'check-square',
            'pages' => 'file',
            'charts' => 'bar-chart-2',
            'ui-elements' => 'package',
            'authentication' => 'lock',
            'chat' => 'message-circle',
            'support-ticket' => 'headphones',
            'email' => 'mail',
        ];

        return $icons[$iconName] ?? 'circle';
    }

    // Deprecated: Use getFeatherIcon instead
    public static function getIconSvg($iconName)
    {
        return self::getFeatherIcon($iconName);
    }

    private static function filterItemsByRole(array $items, ?User $user): array
    {
        return array_values(array_filter($items, function (array $item) use ($user): bool {
            $roles = $item['roles'] ?? [];

            // Public/authenticated menu item.
            if (empty($roles)) {
                return true;
            }

            if (! $user) {
                return false;
            }

            return $user->userRoles()
                ->whereHas('role', fn ($query) => $query->whereIn('code', $roles))
                ->exists();
        }));
    }
}
