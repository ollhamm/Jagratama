<?php

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Models\DocumentType;
use App\Models\Role;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Role::query()->get()->keyBy('code');

        $documentTypes = DocumentType::query()->get();

        foreach (OrganizationType::cases() as $organizationType) {
            foreach ($documentTypes as $documentType) {
                $stepRoleCodes = $this->resolveFlowRoleCodes(
                    $organizationType->value,
                    strtoupper($documentType->code)
                );

                $workflow = Workflow::query()->firstOrCreate(
                    [
                        'organization_type' => $organizationType,
                        'document_type_id' => $documentType->id,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'name' => $this->resolveWorkflowName(strtoupper($documentType->code), $organizationType->value),
                        'is_active' => true,
                    ]
                );

                // Keep workflow metadata up-to-date without mutating id.
                $workflow->update([
                    'name' => $this->resolveWorkflowName(strtoupper($documentType->code), $organizationType->value),
                    'is_active' => true,
                ]);

                foreach ($stepRoleCodes as $idx => $roleCode) {
                    $role = $roles->get($roleCode);
                    if (! $role) {
                        continue;
                    }

                    $stepOrder = $idx + 1;
                    $isRequiredSignature = $this->isStepRequiresSignature(
                        $organizationType->value,
                        strtoupper($documentType->code),
                        $roleCode
                    );
                    $workflowStep = WorkflowStep::query()->firstOrCreate(
                        [
                            'workflow_id' => $workflow->id,
                            'step_order' => $stepOrder,
                        ],
                        [
                            'id' => (string) Str::uuid(),
                            'role_id' => $role->id,
                            'is_required_signature' => $isRequiredSignature,
                            'can_reject' => true,
                        ]
                    );

                    $workflowStep->update([
                        'role_id' => $role->id,
                        'is_required_signature' => $isRequiredSignature,
                        'can_reject' => true,
                    ]);
                }

                // Remove obsolete steps — skip any that already have approvals to avoid FK violation.
                WorkflowStep::query()
                    ->where('workflow_id', $workflow->id)
                    ->where('step_order', '>', count($stepRoleCodes))
                    ->whereDoesntHave('approvals')
                    ->delete();
            }
        }
    }

    private function resolveFlowRoleCodes(string $organizationType, string $documentTypeCode): array
    {
        if ($documentTypeCode === 'SURAT') {
            return $this->resolveSuratFlow($organizationType);
        }

        return $this->resolveKakLpjFlow($organizationType);
    }

    private function resolveWorkflowName(string $documentTypeCode, string $organizationType): string
    {
        if ($documentTypeCode === 'SURAT') {
            return sprintf('ALUR PENGAJUAN PERSURATAN DIREKTORAT DAN DESAIN SERTIFIKAT (%s)', $organizationType);
        }

        return sprintf('ALUR TOR, KAK/LPJ/PROPOSAL SPONSORSHIP(%s)', $organizationType);
    }

    private function resolveKakLpjFlow(string $organizationType): array
    {
        return match ($organizationType) {
            OrganizationType::SBH->value => [
                'KETUA_SBH',
                'PEMBINA_SBH',
                'PRESIDEN_BEM',
                'KOMISI_B_BLM',
                'PENANGGUNG_JAWAB_MAHASISWA',
                'KA_SUB_BAG_AKADEMIK',
                'KA_BAG_AKADEMIK_UMUM',
                'WADIR_III',
                'DIREKTUR',
            ],
            OrganizationType::UKM->value => [
                'KETUA_UKM',
                'PEMBINA_UKM',
                'MENTERI_MINAT_BAKAT_BEM',
                'KOMISI_B_BLM',
                'PENANGGUNG_JAWAB_MAHASISWA',
                'KA_SUB_BAG_AKADEMIK',
                'KA_BAG_AKADEMIK_UMUM',
                'WADIR_III',
                'DIREKTUR',
            ],
            OrganizationType::HMPS->value => [
                'KETUA_HMPS',
                'PJ_MAHASISWA_ALUMNI_JURUSAN',
                'PRESIDEN_BEM',
                'KOMISI_B_BLM',
                'PENANGGUNG_JAWAB_MAHASISWA',
                'KA_SUB_BAG_AKADEMIK',
                'KA_BAG_AKADEMIK_UMUM',
                'WADIR_III',
                'DIREKTUR',
            ],
            OrganizationType::HMJ->value => [
                'KETUA_HMJ',
                'PJ_MAHASISWA_ALUMNI_JURUSAN',
                'PRESIDEN_BEM',
                'KOMISI_B_BLM',
                'PENANGGUNG_JAWAB_MAHASISWA',
                'KA_SUB_BAG_AKADEMIK',
                'KA_BAG_AKADEMIK_UMUM',
                'WADIR_III',
                'DIREKTUR',
            ],
            OrganizationType::BEM->value => [
                'PRESIDEN_BEM',
                'KOMISI_B_BLM',
                'PENANGGUNG_JAWAB_MAHASISWA',
                'KA_SUB_BAG_AKADEMIK',
                'KA_BAG_AKADEMIK_UMUM',
                'WADIR_III',
                'DIREKTUR',
            ],
            OrganizationType::BLM->value => [
                'KETUA_BLM',
                'KOMISI_B_BLM',
                'PENANGGUNG_JAWAB_MAHASISWA',
                'KA_SUB_BAG_AKADEMIK',
                'KA_BAG_AKADEMIK_UMUM',
                'WADIR_III',
                'DIREKTUR',
            ],
            default => ['ADMIN', 'DIREKTUR'],
        };
    }

    private function resolveSuratFlow(string $organizationType): array
    {
        return [
            ...match ($organizationType) {
                OrganizationType::SBH->value => [
                    'KETUA_SBH',
                    'PEMBINA_SBH',
                    'PRESIDEN_BEM',
                    'KOMISI_B_BLM',
                    'PENANGGUNG_JAWAB_MAHASISWA',
                    'KA_SUB_BAG_AKADEMIK',
                    'KA_BAG_AKADEMIK_UMUM',
                ],
                OrganizationType::UKM->value => [
                    'KETUA_UKM',
                    'PEMBINA_UKM',
                    'MENTERI_MINAT_BAKAT_BEM',
                    'KOMISI_B_BLM',
                    'PENANGGUNG_JAWAB_MAHASISWA',
                    'KA_SUB_BAG_AKADEMIK',
                    'KA_BAG_AKADEMIK_UMUM',
                ],
                OrganizationType::HMPS->value => [
                    'KETUA_HMPS',
                    'PJ_MAHASISWA_ALUMNI_JURUSAN',
                    'PRESIDEN_BEM',
                    'KOMISI_B_BLM',
                    'PENANGGUNG_JAWAB_MAHASISWA',
                    'KA_SUB_BAG_AKADEMIK',
                    'KA_BAG_AKADEMIK_UMUM',
                ],
                OrganizationType::HMJ->value => [
                    'KETUA_HMJ',
                    'PJ_MAHASISWA_ALUMNI_JURUSAN',
                    'PRESIDEN_BEM',
                    'KOMISI_B_BLM',
                    'PENANGGUNG_JAWAB_MAHASISWA',
                    'KA_SUB_BAG_AKADEMIK',
                    'KA_BAG_AKADEMIK_UMUM',
                ],
                OrganizationType::BEM->value => [
                    'PRESIDEN_BEM',
                    'KOMISI_B_BLM',
                    'PENANGGUNG_JAWAB_MAHASISWA',
                    'KA_SUB_BAG_AKADEMIK',
                    'KA_BAG_AKADEMIK_UMUM',
                ],
                OrganizationType::BLM->value => [
                    'KETUA_BLM',
                    'KOMISI_B_BLM',
                    'PENANGGUNG_JAWAB_MAHASISWA',
                    'KA_SUB_BAG_AKADEMIK',
                    'KA_BAG_AKADEMIK_UMUM',
                ],
                default => ['ADMINISTRASI_AKADEMIK'],
            },
        ];
    }

    private function isStepRequiresSignature(string $organizationType, string $documentTypeCode, string $roleCode): bool
    {
        if ($documentTypeCode === 'SURAT') {
            return in_array($roleCode, $this->resolveSuratSignatureRoleCodes($organizationType), true);
        }

        return in_array($roleCode, $this->resolveKakLpjSignatureRoleCodes($organizationType), true);
    }

    private function resolveKakLpjSignatureRoleCodes(string $organizationType): array
    {
        return match ($organizationType) {
            OrganizationType::SBH->value => ['KETUA_SBH', 'DIREKTUR'],
            OrganizationType::UKM->value => ['KETUA_UKM', 'PRESIDEN_BEM', 'DIREKTUR'],
            OrganizationType::HMPS->value => ['KETUA_HMPS', 'PJ_MAHASISWA_ALUMNI_JURUSAN', 'DIREKTUR'],
            OrganizationType::HMJ->value => ['KETUA_HMJ', 'PJ_MAHASISWA_ALUMNI_JURUSAN', 'DIREKTUR'],
            OrganizationType::BEM->value => ['PRESIDEN_BEM', 'DIREKTUR'],
            OrganizationType::BLM->value => ['KETUA_BLM', 'DIREKTUR'],
            default => ['DIREKTUR'],
        };
    }

    private function resolveSuratSignatureRoleCodes(string $organizationType): array
    {
        return match ($organizationType) {
            OrganizationType::SBH->value => ['KETUA_SBH', 'KA_BAG_AKADEMIK_UMUM'],
            OrganizationType::UKM->value => ['KETUA_UKM', 'KA_BAG_AKADEMIK_UMUM'],
            OrganizationType::HMPS->value => ['KETUA_HMPS', 'KA_BAG_AKADEMIK_UMUM'],
            OrganizationType::HMJ->value => ['KETUA_HMJ', 'KA_BAG_AKADEMIK_UMUM'],
            OrganizationType::BEM->value => ['PRESIDEN_BEM', 'KA_BAG_AKADEMIK_UMUM'],
            OrganizationType::BLM->value => ['KETUA_BLM', 'KA_BAG_AKADEMIK_UMUM'],
            default => ['KA_BAG_AKADEMIK_UMUM'],
        };
    }
}
