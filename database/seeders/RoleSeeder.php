<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['code' => 'KETUA_HMPS', 'name' => 'Ketua HMPS'],
            ['code' => 'KETUA_HMJ', 'name' => 'Ketua HMJ'],
            ['code' => 'KETUA_UKM', 'name' => 'Ketua UKM'],
            ['code' => 'PRESIDEN_BEM', 'name' => 'Presiden BEM'],
            ['code' => 'KOMISI_B_BLM', 'name' => 'Komisi B BLM'],
            ['code' => 'KAPRODI', 'name' => 'Kaprodi'],
            ['code' => 'KAJUR', 'name' => 'Kajur'],
            ['code' => 'WADIR_III', 'name' => 'Wakil Direktur III'],
            ['code' => 'DIREKTUR', 'name' => 'Direktur'],
            ['code' => 'ADMIN', 'name' => 'Administrator'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['code' => $role['code']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $role['name'],
                ]
            );
        }
    }
}
