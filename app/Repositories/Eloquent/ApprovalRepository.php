<?php

namespace App\Repositories\Eloquent;

use App\Enums\ApprovalStatus;
use App\Models\DocumentApproval;
use App\Models\User;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ApprovalRepository implements ApprovalRepositoryInterface
{
    use BaseUserAccess;

    public function paginatePendingForUser(User $user, array $filters = [], int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        $userRoleIds = $user->userRoles()->pluck('role_id')->all();

        $query = DocumentApproval::query()
            ->with(['document.documentType', 'document.organization', 'workflowStep.role', 'approver'])
            ->where('status', ApprovalStatus::PENDING)
            ->whereIn('role_id', $userRoleIds)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('document_workflow_instances')
                    ->whereColumn('document_workflow_instances.document_id', 'document_approvals.document_id')
                    ->whereNull('document_workflow_instances.finished_at')
                    ->whereColumn('document_workflow_instances.current_step_order', 'document_approvals.step_order');
            });

        if (! $this->hasGlobalAccess($user)) {
            $organizationIds = $this->organizationIds($user);
            $query->whereHas('document', fn ($q) => $q->whereIn('organization_id', $organizationIds));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->whereHas('document', fn ($q) => $q->where('title', 'like', "%{$search}%"));
        }

        return $query->orderBy('step_order')->paginate($perPage, ['*'], $pageName)->withQueryString();
    }

    public function paginateHistoryForUser(User $user, array $filters = [], int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        $query = DocumentApproval::query()
            ->with(['document.documentType', 'document.organization', 'workflowStep.role'])
            ->where('approved_by', $user->id)
            ->whereIn('status', [ApprovalStatus::APPROVED, ApprovalStatus::REJECTED, ApprovalStatus::SKIPPED]);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->whereHas('document', fn ($q) => $q->where('title', 'like', "%{$search}%"));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest('approved_at')->paginate($perPage, ['*'], $pageName)->withQueryString();
    }

    public function findPendingByIdForUser(string $approvalId, User $user): ?DocumentApproval
    {
        $userRoleIds = $user->userRoles()->pluck('role_id')->all();

        $query = DocumentApproval::query()
            ->with(['document.organization', 'workflowStep.role'])
            ->where('id', $approvalId)
            ->where('status', ApprovalStatus::PENDING)
            ->whereIn('role_id', $userRoleIds);

        if (! $this->hasGlobalAccess($user)) {
            $organizationIds = $this->organizationIds($user);
            $query->whereHas('document', fn ($q) => $q->whereIn('organization_id', $organizationIds));
        }

        return $query->first();
    }

    public function create(array $data): DocumentApproval
    {
        return DocumentApproval::query()->create($data);
    }
}
