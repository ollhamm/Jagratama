<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => 'KAK', 'name' => 'TOR, KAK/LPJ/Proposal Sponsorship'],
            ['code' => 'LPJ', 'name' => 'TOR, KAK/LPJ/Proposal Sponsorship'],
            ['code' => 'SURAT', 'name' => 'Pengajuan Persuratan Direktorat dan Desain Sertifikat'],
        ];

        foreach ($types as $type) {
            $documentType = DocumentType::query()->firstOrCreate(
                ['code' => $type['code']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $type['name'],
                ]
            );

            if ($documentType->name !== $type['name']) {
                $documentType->update(['name' => $type['name']]);
            }
        }
    }
}
