<?php

namespace App\Repositories\Eloquent;

use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Repositories\Contracts\WorkflowRepositoryInterface;
use Illuminate\Support\Collection;

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

    public function findNextSteps(string $workflowId, int $currentStepOrder): Collection
    {
        $nextStepOrder = WorkflowStep::query()
            ->where('workflow_id', $workflowId)
            ->where('step_order', '>', $currentStepOrder)
            ->orderBy('step_order')
            ->value('step_order');

        if ($nextStepOrder === null) {
            return collect();
        }

        return WorkflowStep::query()
            ->where('workflow_id', $workflowId)
            ->where('step_order', $nextStepOrder)
            ->get();
    }
}
