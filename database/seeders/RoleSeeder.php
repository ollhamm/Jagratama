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
            ['code' => 'PENGAJU', 'name' => 'Pengaju'],
            ['code' => 'KETUA_PANITIA', 'name' => 'Ketua Panitia'],
            ['code' => 'KETUA_SBH', 'name' => 'Ketua SBH'],
            ['code' => 'KETUA_HMPS', 'name' => 'Ketua HMPS'],
            ['code' => 'KETUA_HMJ', 'name' => 'Ketua HMJ'],
            ['code' => 'KETUA_UKM', 'name' => 'Ketua UKM'],
            ['code' => 'KETUA_BLM', 'name' => 'Ketua BLM'],
            ['code' => 'PRESIDEN_BEM', 'name' => 'Presiden BEM'],
            ['code' => 'KOMISI_B_BLM', 'name' => 'Komisi B BLM'],
            ['code' => 'PJ_MAHASISWA_ALUMNI_JURUSAN', 'name' => 'PJ Mahasiswa dan Alumni Jurusan'],
            ['code' => 'PEMBINA_SBH', 'name' => 'Pembina SBH'],
            ['code' => 'PEMBINA_UKM', 'name' => 'Pembina UKM'],
            ['code' => 'MENTERI_MINAT_BAKAT_BEM', 'name' => 'Menteri Minat Bakat BEM'],
            ['code' => 'PENANGGUNG_JAWAB_MAHASISWA', 'name' => 'Penanggung Jawab Mahasiswa'],
            ['code' => 'ADMINISTRASI_AKADEMIK', 'name' => 'Administrasi Akademik'],
            ['code' => 'KA_SUB_BAG_AKADEMIK', 'name' => 'Ka Sub Bag Akademik'],
            ['code' => 'KA_BAG_AKADEMIK', 'name' => 'Ka Bag Akademik'],
            ['code' => 'KA_BAG_AKADEMIK_UMUM', 'name' => 'Ka Bag Akademik Umum'],
            ['code' => 'KAPRODI', 'name' => 'Kaprodi'],
            ['code' => 'KAJUR', 'name' => 'Kajur'],
            ['code' => 'WADIR_II', 'name' => 'Wakil Direktur II'],
            ['code' => 'WADIR_III', 'name' => 'Wakil Direktur III'],
            ['code' => 'DIREKTUR', 'name' => 'Direktur'],
            ['code' => 'ADMIN', 'name' => 'Administrator'],
        ];

        foreach ($roles as $roleData) {
            $role = Role::query()->firstOrCreate(
                ['code' => $roleData['code']],
                ['id' => (string) Str::uuid(), 'name' => $roleData['name']]
            );

            if ($role->name !== $roleData['name']) {
                $role->update(['name' => $roleData['name']]);
            }
        }
    }
}
