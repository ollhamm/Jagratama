<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('lancar123');

        // Lookup roles
        $roles = Role::query()->get()->keyBy('code');

        // Lookup orgs
        $orgs = Organization::query()->get()->keyBy('name');

        $users = [
            // ── Global roles (shared di semua flow) ──────────────────────────
            ['email' => 'direktur@test.local',        'name' => 'Direktur',              'role' => 'DIREKTUR',                  'org' => null],
            ['email' => 'wadir3@test.local',           'name' => 'Wakil Direktur III',    'role' => 'WADIR_III',                 'org' => null],
            ['email' => 'presiden.bem@test.local',     'name' => 'Presiden BEM',          'role' => 'PRESIDEN_BEM',              'org' => null],
            ['email' => 'komisi.b@test.local',         'name' => 'Komisi B BLM',          'role' => 'KOMISI_B_BLM',              'org' => null],
            ['email' => 'pj.mahasiswa@test.local',     'name' => 'PJ Mahasiswa',          'role' => 'PENANGGUNG_JAWAB_MAHASISWA','org' => null],
            ['email' => 'kasubbag@test.local',         'name' => 'Ka Sub Bag Akademik',   'role' => 'KA_SUB_BAG_AKADEMIK',       'org' => null],
            ['email' => 'kabag.akademik@test.local',   'name' => 'Ka Bag Akademik Umum',  'role' => 'KA_BAG_AKADEMIK_UMUM',     'org' => null],
            ['email' => 'menteri.minat@test.local',    'name' => 'Menteri Minat Bakat',   'role' => 'MENTERI_MINAT_BAKAT_BEM',   'org' => null],

            // ── HMJ Kebidanan flow ───────────────────────────────────────────
            ['email' => 'pengaju.hmj.kebidanan@test.local',  'name' => 'Pengaju HMJ Kebidanan',       'role' => 'PENGAJU',                   'org' => 'HMJ Kebidanan'],
            ['email' => 'ketua.hmj.kebidanan@test.local',    'name' => 'Ketua HMJ Kebidanan',         'role' => 'KETUA_HMJ',                 'org' => 'HMJ Kebidanan'],
            ['email' => 'pj.kemahasiswaan.kebidanan@test.local', 'name' => 'PJ Kemahasiswaan Kebidanan', 'role' => 'PJ_MAHASISWA_ALUMNI_JURUSAN', 'org' => 'Jurusan Kebidanan'],
            ['email' => 'kajur.kebidanan@test.local',        'name' => 'Kajur Kebidanan',             'role' => 'KAJUR',                     'org' => 'Jurusan Kebidanan'],

            // ── HMJ Keperawatan flow ─────────────────────────────────────────
            ['email' => 'pengaju.hmj.keperawatan@test.local','name' => 'Pengaju HMJ Keperawatan',     'role' => 'PENGAJU',                   'org' => 'HMJ Keperawatan'],
            ['email' => 'ketua.hmj.keperawatan@test.local',  'name' => 'Ketua HMJ Keperawatan',       'role' => 'KETUA_HMJ',                 'org' => 'HMJ Keperawatan'],
            ['email' => 'pj.kemahasiswaan.keperawatan@test.local', 'name' => 'PJ Kemahasiswaan Keperawatan', 'role' => 'PJ_MAHASISWA_ALUMNI_JURUSAN', 'org' => 'Jurusan Keperawatan'],
            ['email' => 'kajur.keperawatan@test.local',      'name' => 'Kajur Keperawatan',           'role' => 'KAJUR',                     'org' => 'Jurusan Keperawatan'],

            // ── HMPS D3 Kebidanan flow ───────────────────────────────────────
            ['email' => 'pengaju.hmps.kebidanan@test.local', 'name' => 'Pengaju HMPS D3 Kebidanan',   'role' => 'PENGAJU',                   'org' => 'HMPS D3 Kebidanan'],
            ['email' => 'ketua.hmps.kebidanan@test.local',   'name' => 'Ketua HMPS D3 Kebidanan',     'role' => 'KETUA_HMPS',                'org' => 'HMPS D3 Kebidanan'],
            ['email' => 'kaprodi.d3.kebidanan@test.local',   'name' => 'Kaprodi D3 Kebidanan',        'role' => 'KAPRODI',                   'org' => 'HMPS D3 Kebidanan'],

            // ── UKM MB flow ──────────────────────────────────────────────────
            ['email' => 'pengaju.ukm.mb@test.local',         'name' => 'Pengaju UKM MB',              'role' => 'PENGAJU',                   'org' => 'UKM MB'],
            ['email' => 'ketua.ukm.mb@test.local',           'name' => 'Ketua UKM MB',                'role' => 'KETUA_UKM',                 'org' => 'UKM MB'],
            ['email' => 'pembina.ukm.mb@test.local',         'name' => 'Pembina UKM MB',              'role' => 'PEMBINA_UKM',               'org' => 'UKM MB'],
        ];

        foreach ($users as $data) {
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'id'        => (string) Str::uuid(),
                    'name'          => $data['name'],
                    'password_hash' => $password,
                    'is_active' => true,
                ]
            );

            // Reset user_roles agar tidak duplikat saat re-run
            UserRole::query()->where('user_id', $user->id)->delete();

            $role   = $roles->get($data['role']);
            $org    = $data['org'] ? $orgs->get($data['org']) : null;

            if ($role) {
                UserRole::query()->create([
                    'id'              => (string) Str::uuid(),
                    'user_id'         => $user->id,
                    'role_id'         => $role->id,
                    'organization_id' => $org?->id,
                    'assigned_at'     => now(),
                ]);
            }
        }
    }
}
