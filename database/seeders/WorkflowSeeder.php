<?php

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Models\DocumentType;
use App\Models\Role;
use App\Models\Workflow;
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

        $organizationLeaderRole = [
            OrganizationType::HMPS->value => 'KETUA_HMPS',
            OrganizationType::HMJ->value => 'KETUA_HMJ',
            OrganizationType::UKM->value => 'KETUA_UKM',
            OrganizationType::BEM->value => 'PRESIDEN_BEM',
            OrganizationType::BLM->value => 'KOMISI_B_BLM',
            OrganizationType::SBH->value => 'ADMIN',
        ];

        $documentTypes = DocumentType::query()->get();

        foreach (OrganizationType::cases() as $organizationType) {
            foreach ($documentTypes as $documentType) {
                $firstRoleCode = $organizationLeaderRole[$organizationType->value] ?? 'ADMIN';

                $stepDefinitions = [
                    ['order' => 1, 'role_code' => $firstRoleCode, 'is_required_signature' => true, 'can_reject' => true],
                    ['order' => 2, 'role_code' => 'KAPRODI', 'is_required_signature' => true, 'can_reject' => true],
                    ['order' => 3, 'role_code' => 'KAJUR', 'is_required_signature' => true, 'can_reject' => true],
                    ['order' => 4, 'role_code' => 'WADIR_III', 'is_required_signature' => true, 'can_reject' => true],
                    ['order' => 5, 'role_code' => 'DIREKTUR', 'is_required_signature' => true, 'can_reject' => true],
                ];

                $workflow = Workflow::query()->firstOrCreate(
                    [
                        'organization_type' => $organizationType,
                        'document_type_id' => $documentType->id,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'name' => sprintf('%s %s Approval Flow', $documentType->code, $organizationType->value),
                        'is_active' => true,
                    ]
                );

                foreach ($stepDefinitions as $step) {
                    $role = $roles->get($step['role_code']);
                    if (! $role) {
                        continue;
                    }

                    $workflow->steps()->updateOrCreate(
                        [
                            'workflow_id' => $workflow->id,
                            'step_order' => $step['order'],
                        ],
                        [
                            'id' => (string) Str::uuid(),
                            'role_id' => $role->id,
                            'is_required_signature' => $step['is_required_signature'],
                            'can_reject' => $step['can_reject'],
                        ]
                    );
                }
            }
        }
    }
}
