<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::query()->where('code', 'ADMIN')->first();

        if (! $adminRole) {
            return;
        }

        $adminUsers = [
            [
                'name' => 'Admin Jagratama',
                'email' => 'admin@jagratama.id',
                'password' => 'password',
                'is_active' => true,
            ],
        ];

        foreach ($adminUsers as $adminData) {
            $user = User::query()->firstOrCreate(
                ['email' => $adminData['email']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $adminData['name'],
                    'password_hash' => Hash::make($adminData['password']),
                    'organization_id' => null,
                    'is_active' => $adminData['is_active'],
                ]
            );

            UserRole::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'role_id' => $adminRole->id,
                    'organization_id' => null,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'assigned_at' => now(),
                ]
            );
        }
    }
}
