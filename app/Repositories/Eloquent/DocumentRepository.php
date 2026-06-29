<?php

namespace App\Repositories\Eloquent;

use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DocumentRepository implements DocumentRepositoryInterface
{
    use BaseUserAccess;

    public function paginateForUser(User $user, array $filters = [], int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        $query = Document::query()
            ->with([
                'documentType', 'organization', 'creator',
                'approvals' => fn ($q) => $q->where('status', 'REJECTED')->orderByDesc('created_at'),
            ])
            ->withExists(['approvals as has_been_rejected' => fn ($q) => $q->where('status', 'REJECTED')]);

        if (! $this->hasGlobalAccess($user)) {
            $organizationIds = $this->organizationIds($user);
            $query->where(function ($subQuery) use ($organizationIds, $user) {
                $subQuery->whereIn('organization_id', $organizationIds)
                    ->orWhere('created_by', $user->id);
            });
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                    ->orWhereHas('documentType', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['status'])) {
            $query->where('current_status', $filters['status']);
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        return $query->latest('created_at')->paginate($perPage, ['*'], $pageName)->withQueryString();
    }

    public function paginateCreatedBy(User $user, array $filters = [], int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        $query = Document::query()
            ->with(['documentType', 'organization'])
            ->where('created_by', $user->id);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where('title', 'like', "%{$search}%");
        }

        if (! empty($filters['status'])) {
            $query->where('current_status', $filters['status']);
        }

        return $query->latest('created_at')->paginate($perPage, ['*'], $pageName)->withQueryString();
    }

    public function paginatePublishedForUser(User $user, array $filters = [], int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        $query = Document::query()
            ->with([
                'documentType', 'organization', 'creator',
                'approvals.workflowStep.role', 'approvals.approver',
            ])
            ->whereNotNull('published_at');

        if (! $this->isAdmin($user)) {
            $query->where(function ($subQuery) use ($user) {
                $subQuery->where('created_by', $user->id)
                    ->orWhereHas('approvals', fn ($q) => $q->where('approved_by', $user->id)->where('status', 'APPROVED'));
            });
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                    ->orWhereHas('creator', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->latest('published_at')->paginate($perPage, ['*'], $pageName)->withQueryString();
    }

    public function findByIdForUser(string $id, User $user): ?Document
    {
        $query = Document::query()->with([
            'documentType',
            'organization',
            'creator',
            'attachments',
            'workflowInstances.workflow.steps.role',
            'approvals.workflowStep.role',
            'approvals.approver',
        ]);

        if (! $this->hasGlobalAccess($user)) {
            $organizationIds = $this->organizationIds($user);
            $query->where(function ($subQuery) use ($organizationIds, $user) {
                $subQuery->whereIn('organization_id', $organizationIds)
                    ->orWhere('created_by', $user->id);
            });
        }

        return $query->find($id);
    }

    public function create(array $data): Document
    {
        return Document::query()->create($data);
    }

    public function update(Document $document, array $data): bool
    {
        return $document->update($data);
    }

    public function delete(Document $document): bool
    {
        return (bool) $document->delete();
    }

    public function createAttachment(array $data): DocumentAttachment
    {
        return DocumentAttachment::query()->create($data);
    }
}
