<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'KAK',   'name' => 'KAK / LPJ'],
            ['code' => 'SURAT', 'name' => 'Persuratan (Surat)'],
        ];

        $activeCodes = array_column($types, 'code');

        // Hapus document type yang sudah tidak ada di daftar (beserta workflow & steps-nya)
        DocumentType::query()->whereNotIn('code', $activeCodes)->each(function (DocumentType $dt) {
            $workflowIds = DB::table('workflows')->where('document_type_id', $dt->id)->pluck('id');
            if ($workflowIds->isNotEmpty()) {
                DB::table('workflow_steps')->whereIn('workflow_id', $workflowIds)->delete();
                DB::table('workflows')->whereIn('id', $workflowIds)->delete();
            }
            $dt->delete();
        });

        // Upsert document type yang aktif
        foreach ($types as $type) {
            $documentType = DocumentType::query()->firstOrCreate(
                ['code' => $type['code']],
                ['id' => (string) Str::uuid(), 'name' => $type['name']]
            );

            if ($documentType->name !== $type['name']) {
                $documentType->update(['name' => $type['name']]);
            }
        }
    }
}
