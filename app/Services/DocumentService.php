<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function __construct(
        private readonly DocumentRepositoryInterface $documents,
        private readonly WorkflowApprovalEngine $workflowEngine,
    ) {
    }

    public function paginate(User $user, array $filters, string $pageName = 'page'): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        return $this->documents->paginateForUser($user, $filters, $perPage, $pageName);
    }

    public function mySubmissions(User $user, array $filters, string $pageName = 'page'): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        return $this->documents->paginateCreatedBy($user, $filters, $perPage, $pageName);
    }

    public function findForUser(string $id, User $user): ?Document
    {
        return $this->documents->findByIdForUser($id, $user);
    }

    public function create(User $user, array $payload): Document
    {
        return DB::transaction(function () use ($user, $payload) {
            $document = $this->documents->create([
                'title' => $payload['title'],
                'document_type_id' => $payload['document_type_id'],
                'organization_id' => $payload['organization_id'],
                'created_by' => $user->id,
                'current_status' => DocumentStatus::DRAFT,
                'current_step_order' => 0,
            ]);

            $attachmentFile = $payload['attachment'] ?? ($payload['attachments'][0] ?? null);

            if ($attachmentFile instanceof UploadedFile) {
                $path = $attachmentFile->store('documents');

                $this->documents->createAttachment([
                    'document_id' => $document->id,
                    'file_path' => $path,
                    'file_type' => $attachmentFile->getClientMimeType() ?? 'application/octet-stream',
                    'uploaded_at' => now(),
                ]);
            }

            Log::info('dokumen_dibuat', [
                'document_id' => $document->id,
                'created_by' => $user->id,
                'organization_id' => $document->organization_id,
            ]);

            return $document->load('attachments');
        });
    }

    public function submit(Document $document, User $actor, string $signatureValue): void
    {
        if ($document->current_status !== DocumentStatus::DRAFT) {
            throw new DomainException('Dokumen hanya bisa disubmit dari status DRAFT.');
        }

        if ($document->created_by !== $actor->id) {
            throw new DomainException('Hanya pengaju yang dapat submit dokumen.');
        }

        if (blank($signatureValue)) {
            throw new DomainException('Tanda tangan pengaju wajib diisi sebelum submit.');
        }

        $document->update([
            'submitter_signature' => $signatureValue,
        ]);

        $this->workflowEngine->submitDocument($document);

        Log::info('dokumen_disubmit', [
            'document_id' => $document->id,
            'submitted_by' => $actor->id,
        ]);
    }

    public function deleteDraft(Document $document, User $actor): void
    {
        if ($document->current_status !== DocumentStatus::DRAFT) {
            throw new DomainException('Hanya dokumen berstatus DRAFT yang dapat dihapus.');
        }

        if ($document->created_by !== $actor->id) {
            throw new DomainException('Hanya pengaju pembuat dokumen yang dapat menghapus draft.');
        }

        DB::transaction(function () use ($document, $actor) {
            $document->loadMissing('attachments');

            foreach ($document->attachments as $attachment) {
                if (! blank($attachment->file_path) && Storage::exists($attachment->file_path)) {
                    Storage::delete($attachment->file_path);
                }
            }

            $this->documents->delete($document);

            Log::info('dokumen_draft_dihapus', [
                'document_id' => $document->id,
                'deleted_by' => $actor->id,
            ]);
        });
    }
}
