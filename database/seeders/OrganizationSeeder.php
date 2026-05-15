<?php

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $institution = Organization::query()->firstOrCreate(
            ['name' => 'Institusi Poltekkes Kemenkes Yogyakarta'],
            [
                'id' => (string) Str::uuid(),
                'type' => OrganizationType::SBH,
                'parent_id' => null,
            ]
        );

        $hmj = Organization::query()->firstOrCreate(
            ['name' => 'HMJ Keperawatan'],
            [
                'id' => (string) Str::uuid(),
                'type' => OrganizationType::HMJ,
                'parent_id' => $institution->id,
            ]
        );

        Organization::query()->firstOrCreate(
            ['name' => 'HMPS Keperawatan'],
            [
                'id' => (string) Str::uuid(),
                'type' => OrganizationType::HMPS,
                'parent_id' => $hmj->id,
            ]
        );

        Organization::query()->firstOrCreate(
            ['name' => 'BEM Poltekkes Kemenkes Yogyakarta'],
            [
                'id' => (string) Str::uuid(),
                'type' => OrganizationType::BEM,
                'parent_id' => $institution->id,
            ]
        );

        Organization::query()->firstOrCreate(
            ['name' => 'BLM Poltekkes Kemenkes Yogyakarta'],
            [
                'id' => (string) Str::uuid(),
                'type' => OrganizationType::BLM,
                'parent_id' => $institution->id,
            ]
        );

        Organization::query()->firstOrCreate(
            ['name' => 'UKM Kesehatan'],
            [
                'id' => (string) Str::uuid(),
                'type' => OrganizationType::UKM,
                'parent_id' => $institution->id,
            ]
        );
    }
}
