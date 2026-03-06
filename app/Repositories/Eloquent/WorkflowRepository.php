<?php

namespace App\Repositories\Eloquent;

use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Repositories\Contracts\WorkflowRepositoryInterface;

class WorkflowRepository implements WorkflowRepositoryInterface
{
    public function findActiveByType(string $organizationType, string $documentTypeId): ?Workflow
    {
        return Workflow::query()
            ->with(['steps' => fn ($q) => $q->orderBy('step_order')])
            ->where('organization_type', $organizationType)
            ->where('document_type_id', $documentTypeId)
            ->where('is_active', true)
            ->first();
    }

    public function findStep(string $workflowId, int $stepOrder): ?WorkflowStep
    {
        return WorkflowStep::query()
            ->where('workflow_id', $workflowId)
            ->where('step_order', $stepOrder)
            ->first();
    }

    public function findNextStep(string $workflowId, int $currentStepOrder): ?WorkflowStep
    {
        return WorkflowStep::query()
            ->where('workflow_id', $workflowId)
            ->where('step_order', '>', $currentStepOrder)
            ->orderBy('step_order')
            ->first();
    }
}
