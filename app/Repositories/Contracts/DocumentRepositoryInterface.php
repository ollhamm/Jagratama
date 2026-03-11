<?php

namespace App\Repositories\Contracts;

use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DocumentRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = [], int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator;

    public function paginateCreatedBy(User $user, array $filters = [], int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator;

    public function findByIdForUser(string $id, User $user): ?Document;

    public function create(array $data): Document;

    public function update(Document $document, array $data): bool;

    public function delete(Document $document): bool;

    public function createAttachment(array $data): DocumentAttachment;
}
