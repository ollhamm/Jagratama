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
            ['code' => 'KAK', 'name' => 'Kerangka Acuan Kegiatan'],
            ['code' => 'LPJ', 'name' => 'Laporan Pertanggungjawaban'],
            ['code' => 'SURAT', 'name' => 'Persuratan Organisasi Mahasiswa'],
        ];

        foreach ($types as $type) {
            DocumentType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $type['name'],
                ]
            );
        }
    }
}
