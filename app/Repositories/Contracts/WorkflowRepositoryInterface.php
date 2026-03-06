<?php

namespace App\Repositories\Contracts;

use App\Models\Workflow;
use App\Models\WorkflowStep;

interface WorkflowRepositoryInterface
{
    public function findActiveByType(string $organizationType, string $documentTypeId): ?Workflow;

    public function findStep(string $workflowId, int $stepOrder): ?WorkflowStep;

    public function findNextStep(string $workflowId, int $currentStepOrder): ?WorkflowStep;
}
