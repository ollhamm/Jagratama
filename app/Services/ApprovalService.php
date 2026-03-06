<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApprovalService
{
    public function __construct(
        private readonly ApprovalRepositoryInterface $approvals,
        private readonly WorkflowApprovalEngine $workflowEngine,
    ) {
    }

    public function pending(User $user, array $filters, string $pageName = 'page'): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        return $this->approvals->paginatePendingForUser($user, $filters, $perPage, $pageName);
    }

    public function history(User $user, array $filters, string $pageName = 'page'): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        return $this->approvals->paginateHistoryForUser($user, $filters, $perPage, $pageName);
    }

    public function approve(string $approvalId, User $user, ?string $notes = null, ?string $signatureValue = null)
    {
        return $this->workflowEngine->approveByApprovalId($approvalId, $user, $notes, $signatureValue);
    }

    public function reject(string $approvalId, User $user, ?string $notes = null)
    {
        return $this->workflowEngine->rejectByApprovalId($approvalId, $user, $notes);
    }
}
