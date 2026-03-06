<?php

namespace App\Repositories\Contracts;

use App\Models\DocumentApproval;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ApprovalRepositoryInterface
{
    public function paginatePendingForUser(User $user, array $filters = [], int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator;

    public function paginateHistoryForUser(User $user, array $filters = [], int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator;

    public function findPendingByIdForUser(string $approvalId, User $user): ?DocumentApproval;

    public function create(array $data): DocumentApproval;
}
