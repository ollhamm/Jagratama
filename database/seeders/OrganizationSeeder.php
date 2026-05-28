<?php

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $institution = $this->org('Institusi Poltekkes Kemenkes Yogyakarta', OrganizationType::SBH, null);

        $bem = $this->org('BEM Poltekkes Kemenkes Yogyakarta', OrganizationType::BEM, $institution->id);
        $this->org('BLM Poltekkes Kemenkes Yogyakarta', OrganizationType::BLM, $institution->id);

        // Jurusan + HMJ + HMPS
        $jurusanTlm = $this->org('Jurusan TLM', OrganizationType::JURUSAN, $institution->id);
        $hmjTlm     = $this->org('HMJ TLM', OrganizationType::HMJ, $jurusanTlm->id);
        $this->org('HMPS D3 TLM', OrganizationType::HMPS, $hmjTlm->id);
        $this->org('HMPS STr TLM', OrganizationType::HMPS, $hmjTlm->id);

        $jurusanGizi = $this->org('Jurusan Gizi', OrganizationType::JURUSAN, $institution->id);
        $hmjGizi     = $this->org('HMJ Gizi', OrganizationType::HMJ, $jurusanGizi->id);
        $this->org('HMPS D3 Gizi', OrganizationType::HMPS, $hmjGizi->id);
        $this->org('HMPS STr Gizi dan Dietetika', OrganizationType::HMPS, $hmjGizi->id);

        $jurusanKebidanan = $this->org('Jurusan Kebidanan', OrganizationType::JURUSAN, $institution->id);
        $hmjKebidanan     = $this->org('HMJ Kebidanan', OrganizationType::HMJ, $jurusanKebidanan->id);
        $this->org('HMPS D3 Kebidanan', OrganizationType::HMPS, $hmjKebidanan->id);
        $this->org('HMPS STr Kebidanan', OrganizationType::HMPS, $hmjKebidanan->id);

        $jurusanKeperawatan = $this->org('Jurusan Keperawatan', OrganizationType::JURUSAN, $institution->id);
        $hmjKeperawatan     = $this->org('HMJ Keperawatan', OrganizationType::HMJ, $jurusanKeperawatan->id);
        $this->org('HMPS D3 Keperawatan', OrganizationType::HMPS, $hmjKeperawatan->id);
        $this->org('HMPS STr Keperawatan + Ners', OrganizationType::HMPS, $hmjKeperawatan->id);
        $this->org('HMPS STr Keperawatan Anestesiologi', OrganizationType::HMPS, $hmjKeperawatan->id);

        $jurusanGigi = $this->org('Jurusan Kesehatan Gigi', OrganizationType::JURUSAN, $institution->id);
        $hmjGigi     = $this->org('HMJ Kesehatan Gigi', OrganizationType::HMJ, $jurusanGigi->id);
        $this->org('HMPS D3 Kesehatan Gigi', OrganizationType::HMPS, $hmjGigi->id);
        $this->org('HMPS STr Terapi Gigi', OrganizationType::HMPS, $hmjGigi->id);

        $jurusanKesling = $this->org('Jurusan Kesehatan Lingkungan', OrganizationType::JURUSAN, $institution->id);
        $hmjKesling     = $this->org('HMJ Kesehatan Lingkungan', OrganizationType::HMJ, $jurusanKesling->id);
        $this->org('HMPS D3 Sanitasi', OrganizationType::HMPS, $hmjKesling->id);
        $this->org('HMPS STr Sanitasi Lingkungan', OrganizationType::HMPS, $hmjKesling->id);
        $this->org('HMPS D3 Rekam Medis', OrganizationType::HMPS, $hmjKesling->id);

        // UKM — masing-masing org terpisah, parent institusi
        $ukmNames = [
            'UKM MB',
            'UKM Keprotokoleran',
            'UKM Paskibra',
            'UKM SBH',
            'UKM PSQ',
            'UKM KSR',
            'UKM Pers',
            'UKM PSM',
            'UKM Karawitan',
            'UKM Tari',
            'UKM PIK-M',
            'UKM Taekwondo',
            'UKM Riset',
            'UKM PMKK',
            'UKM P4GN',
            'UKM Eclipse',
            'UKM SKI',
            'UKM IT',
            'UKM Teater',
            'UKM Mapapy',
            'UKM Olahraga',
        ];

        foreach ($ukmNames as $name) {
            $this->org($name, OrganizationType::UKM, $institution->id);
        }
    }

    private function org(string $name, OrganizationType $type, ?string $parentId): Organization
    {
        return Organization::query()->updateOrCreate(
            ['name' => $name],
            [
                'type'      => $type,
                'parent_id' => $parentId,
            ]
        );
    }
}
