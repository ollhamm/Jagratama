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
                'name' => 'Dokumen',
                'path' => '/app/documents',
                'roles' => [],
            ],
            [
                'icon' => 'task',
                'name' => 'Approval',
                'path' => '/app/approvals/pending',
                'roles' => [
                    'KETUA_HMPS',
                    'KETUA_HMJ',
                    'KETUA_UKM',
                    'PRESIDEN_BEM',
                    'KOMISI_B_BLM',
                    'KAPRODI',
                    'KAJUR',
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
