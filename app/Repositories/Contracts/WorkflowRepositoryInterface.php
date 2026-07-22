<?php

namespace App\Repositories\Contracts;

use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Support\Collection;

interface WorkflowRepositoryInterface
{
    public function findActiveByType(string $organizationType, string $documentTypeId): ?Workflow;

    public function findStep(string $workflowId, int $stepOrder): ?WorkflowStep;

    /**
     * Semua WorkflowStep di step_order TERKECIL yang lebih besar dari $currentStepOrder —
     * bisa lebih dari 1 baris kalau step itu punya beberapa role eligible (lihat
     * WorkflowSeeder untuk contoh grup role).
     */
    public function findNextSteps(string $workflowId, int $currentStepOrder): Collection;
}
