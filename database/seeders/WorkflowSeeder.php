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

                // Kombinasi (step_order, role_id) yang valid menurut definisi TERBARU —
                // dipakai untuk membersihkan baris lama yang jadi nyasar kalau ada step baru
                // disisipkan di tengah (semua role sesudahnya geser posisi, baris lamanya
                // sendiri tidak otomatis hilang kalau cuma dicek "posisi di luar rentang").
                $validStepRoleCombos = [];

                foreach ($stepRoleCodes as $idx => $roleCodeOrGroup) {
                    $stepOrder = $idx + 1;

                    // Satu posisi bisa punya beberapa role eligible sekaligus (mis. step
                    // "PJ Kemha Jurusan/Kaprodi/Kajur" — siapa pun dari 3 role ini boleh
                    // approve, siapa cepat dia dapat). Grup ditulis sebagai array di
                    // resolveKakLpjFlow()/resolveSuratFlow(); step biasa tetap string tunggal.
                    $roleCodesAtThisStep = is_array($roleCodeOrGroup) ? $roleCodeOrGroup : [$roleCodeOrGroup];

                    // is_required_signature harus SAMA untuk semua role dalam 1 grup (mereka
                    // mewakili slot tanda tangan yang sama di PDF) — true kalau SALAH SATU
                    // role di grup ini butuh TTD menurut aturan asli.
                    $isRequiredSignature = collect($roleCodesAtThisStep)->contains(
                        fn ($rc) => $this->isStepRequiresSignature(
                            $organizationType->value,
                            strtoupper($documentType->code),
                            $rc
                        )
                    );

                    foreach ($roleCodesAtThisStep as $roleCode) {
                        $role = $roles->get($roleCode);
                        if (! $role) {
                            continue;
                        }

                        $validStepRoleCombos[] = $stepOrder.':'.$role->id;

                        $workflowStep = WorkflowStep::query()->firstOrCreate(
                            [
                                'workflow_id' => $workflow->id,
                                'step_order' => $stepOrder,
                                'role_id' => $role->id,
                            ],
                            [
                                'id' => (string) Str::uuid(),
                                'is_required_signature' => $isRequiredSignature,
                                'can_reject' => true,
                            ]
                        );

                        $workflowStep->update([
                            'is_required_signature' => $isRequiredSignature,
                            'can_reject' => true,
                        ]);
                    }
                }

                // Hapus baris step lama yang kombinasi (step_order, role_id)-nya sudah tidak
                // valid lagi menurut definisi saat ini — termasuk role yang "geser posisi"
                // karena ada step baru disisipkan di tengah, bukan cuma yang posisinya di
                // luar rentang. Skip yang sudah punya approval, supaya tidak melanggar FK.
                WorkflowStep::query()
                    ->where('workflow_id', $workflow->id)
                    ->whereDoesntHave('approvals')
                    ->get()
                    ->each(function (WorkflowStep $step) use ($validStepRoleCombos) {
                        if (! in_array($step->step_order.':'.$step->role_id, $validStepRoleCombos, true)) {
                            $step->delete();
                        }
                    });
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
                // Satu posisi, 3 role eligible — siapa cepat dia dapat approve (lihat
                // WorkflowApprovalEngine::approveByApprovalId untuk sibling-skip-nya).
                ['PJ_MAHASISWA_ALUMNI_JURUSAN', 'KAPRODI', 'KAJUR'],
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
                // Satu posisi, 2 role eligible (tanpa Kaprodi — HMJ di level Jurusan, tidak
                // ada konsep Prodi di flow ini) — siapa cepat dia dapat approve.
                ['PJ_MAHASISWA_ALUMNI_JURUSAN', 'KAJUR'],
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
                    // Satu posisi, 3 role eligible — siapa cepat dia dapat approve (sama
                    // seperti resolveKakLpjFlow() HMPS).
                    ['PJ_MAHASISWA_ALUMNI_JURUSAN', 'KAPRODI', 'KAJUR'],
                    'PRESIDEN_BEM',
                    'KOMISI_B_BLM',
                    'PENANGGUNG_JAWAB_MAHASISWA',
                    'KA_SUB_BAG_AKADEMIK',
                    'KA_BAG_AKADEMIK_UMUM',
                ],
                OrganizationType::HMJ->value => [
                    'KETUA_HMJ',
                    // Satu posisi, 2 role eligible (tanpa Kaprodi — HMJ di level Jurusan, tidak
                    // ada konsep Prodi di flow ini) — siapa cepat dia dapat approve.
                    ['PJ_MAHASISWA_ALUMNI_JURUSAN', 'KAJUR'],
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
