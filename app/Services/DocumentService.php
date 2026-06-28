<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\PublicSignature;
use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Contracts\WorkflowRepositoryInterface;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    /**
     * Sentinel step_order untuk slot tanda tangan Pengaju — terpisah dari step_order
     * workflow asli (yang dimulai dari approver pertama, bukan dari pengaju).
     */
    private const PENGAJU_STEP_ORDER = 0;

    public function __construct(
        private readonly DocumentRepositoryInterface $documents,
        private readonly WorkflowApprovalEngine $workflowEngine,
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly PdfSignatureSlotService $signatureSlots,
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
                'created_by' => $payload['on_behalf_of'] ?? $user->id,
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

    public function submit(Document $document, User $actor, ?string $signatureValue = null): void
    {
        if ($document->current_status !== DocumentStatus::DRAFT) {
            throw new DomainException('Dokumen hanya bisa disubmit dari status DRAFT.');
        }

        $isAdmin = $actor->userRoles()->whereHas('role', fn ($q) => $q->where('code', 'ADMIN'))->exists();

        if (! $isAdmin && $document->created_by !== $actor->id) {
            throw new DomainException('Hanya pengaju yang dapat submit dokumen.');
        }

        $document->loadMissing('organization', 'attachments');
        $requiredSteps = $this->requiredSignatureSteps($document);

        // Validasi & petakan slot tanda tangan di PDF SEBELUM dokumen benar-benar disubmit,
        // supaya pengaju langsung tahu kalau template-nya salah, bukan baru ketahuan saat approval.
        // Pengaju selalu jadi slot pertama (step_order sentinel = 0), terpisah dari step_order
        // workflow asli (yang mulai dari approver pertama, bukan dari pengaju).
        if ($requiredSteps->isNotEmpty()) {
            $this->prepareSignatureSlots($document, $requiredSteps);
        }

        if ($signatureValue) {
            $document->load('creator');
            $publicSig = PublicSignature::query()->create([
                'document_id'     => $document->id,
                'signer_name'     => $document->creator->name ?? $actor->name,
                'role_name'       => 'Pengaju',
                'signature_value' => $signatureValue,
                'signed_at'       => now(),
            ]);

            $document->update([
                'submitter_signature'             => $signatureValue,
                'public_submitter_signature_id'   => $publicSig->id,
            ]);

            if ($requiredSteps->isNotEmpty()) {
                $this->embedApproverSlot(
                    $document,
                    self::PENGAJU_STEP_ORDER,
                    'Pengaju',
                    $document->creator->name ?? $actor->name,
                    route('public.signature.show', $publicSig->id)
                );
            }
        }

        $this->workflowEngine->submitDocument($document);

        Log::info('dokumen_disubmit', [
            'document_id' => $document->id,
            'submitted_by' => $actor->id,
        ]);
    }

    public function resubmit(Document $document, User $actor, UploadedFile $newFile): void
    {
        if ($document->current_status !== DocumentStatus::REJECTED) {
            throw new DomainException('Dokumen hanya bisa disubmit ulang dari status REJECTED.');
        }

        $isAdmin = $actor->userRoles()->whereHas('role', fn ($q) => $q->where('code', 'ADMIN'))->exists();

        if (! $isAdmin && $document->created_by !== $actor->id) {
            throw new DomainException('Hanya pengaju yang dapat submit ulang dokumen.');
        }

        DB::transaction(function () use ($document, $newFile) {
            // Hapus lampiran lama dan simpan file baru
            $document->loadMissing('attachments', 'organization');
            foreach ($document->attachments as $old) {
                Storage::delete($old->file_path);
                $old->delete();
            }

            $path = $newFile->store('documents');
            $this->documents->createAttachment([
                'document_id' => $document->id,
                'file_path'   => $path,
                'file_type'   => $newFile->getClientMimeType() ?? 'application/octet-stream',
                'uploaded_at' => now(),
            ]);

            $document->unsetRelation('attachments');
            $document->loadMissing('attachments');

            // File baru = belum ada QR sama sekali. Petakan ulang slot, lalu tempel ulang
            // QR untuk semua approval yang SUDAH approve sebelumnya (mereka tidak perlu ttd ulang).
            $requiredSteps = $this->requiredSignatureSteps($document);
            if ($requiredSteps->isNotEmpty()) {
                $this->prepareSignatureSlots($document, $requiredSteps);
                $this->reembedExistingApprovals($document, $requiredSteps);
            }

            $this->workflowEngine->resubmitDocument($document);
        });

        Log::info('dokumen_resubmit', [
            'document_id'    => $document->id,
            'resubmitted_by' => $actor->id,
        ]);
    }

    public function deleteDraft(Document $document, User $actor): void
    {
        if ($document->current_status !== DocumentStatus::DRAFT) {
            throw new DomainException('Hanya dokumen berstatus DRAFT yang dapat dihapus.');
        }

        $isAdmin = $actor->userRoles()->whereHas('role', fn ($q) => $q->where('code', 'ADMIN'))->exists();

        if (! $isAdmin && $document->created_by !== $actor->id) {
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

    /**
     * Step-step workflow aktif (org_type + document_type dokumen ini) yang wajib tanda tangan,
     * terurut berdasarkan step_order.
     */
    private function requiredSignatureSteps(Document $document): \Illuminate\Support\Collection
    {
        $organizationType = $document->organization?->type?->value ?? $document->organization?->type;
        $workflow = $this->workflows->findActiveByType((string) $organizationType, $document->document_type_id);

        if (! $workflow) {
            return collect();
        }

        return $workflow->steps->where('is_required_signature', true)->sortBy('step_order')->values();
    }

    /**
     * Ekstrak & validasi slot tanda tangan dari PDF attachment, petakan ke step_order,
     * simpan ke documents.signature_slots. Melempar DomainException kalau jumlah key
     * di PDF tidak sesuai jumlah step yang wajib tanda tangan.
     */
    private function prepareSignatureSlots(Document $document, \Illuminate\Support\Collection $requiredSteps): void
    {
        $attachment = $document->attachments->first();
        if (! $attachment) {
            throw new DomainException('Lampiran dokumen tidak ditemukan untuk validasi slot tanda tangan.');
        }

        $absolutePath = Storage::path($attachment->file_path);
        // +1 untuk slot Pengaju, yang selalu jadi slot pertama (terpisah dari step_order workflow)
        $expectedCount = $requiredSteps->count() + 1;
        $extracted = $this->signatureSlots->extractSlots($absolutePath, $expectedCount);

        $stepOrders = array_merge([self::PENGAJU_STEP_ORDER], $requiredSteps->pluck('step_order')->values()->all());
        $slots = [];
        foreach ($extracted['slots'] as $index => $slot) {
            $slots[] = array_merge($slot, ['step_order' => $stepOrders[$index]]);
        }

        $document->update([
            'signature_slots' => [
                'page_index' => $extracted['page_index'],
                'page_width' => $extracted['page_width'],
                'page_height' => $extracted['page_height'],
                'slots' => $slots,
            ],
        ]);
    }

    /**
     * Tempel QR + teks untuk satu step_order tertentu, kalau memang termasuk slot wajib TTD.
     */
    private function embedApproverSlot(Document $document, int $stepOrder, string $jabatan, string $nama, string $verificationUrl): void
    {
        $document->refresh();
        $signatureSlots = $document->signature_slots;

        if (! $signatureSlots) {
            return;
        }

        $slot = collect($signatureSlots['slots'])->firstWhere('step_order', $stepOrder);
        if (! $slot) {
            return;
        }

        $attachment = $document->attachments->first() ?? $document->attachments()->first();
        if (! $attachment) {
            return;
        }

        $absolutePath = Storage::path($attachment->file_path);

        $this->signatureSlots->embedSlot(
            $absolutePath,
            $signatureSlots,
            $slot,
            $jabatan,
            $nama,
            $verificationUrl
        );
    }

    /**
     * Saat resubmit, file PDF baru tidak punya QR siapapun. Tempel ulang QR untuk semua
     * approval yang SUDAH approve sebelumnya (mereka tidak perlu tanda tangan ulang).
     */
    private function reembedExistingApprovals(Document $document, \Illuminate\Support\Collection $requiredSteps): void
    {
        $requiredStepOrders = $requiredSteps->pluck('step_order')->all();

        $approvedSignedSteps = DocumentApproval::query()
            ->where('document_id', $document->id)
            ->where('status', ApprovalStatus::APPROVED)
            ->whereIn('step_order', $requiredStepOrders)
            ->with(['workflowStep.role', 'approver', 'signatures'])
            ->orderBy('step_order')
            ->get();

        foreach ($approvedSignedSteps as $approval) {
            $signature = $approval->signatures->first();
            if (! $signature || ! $signature->public_signature_id) {
                continue;
            }

            $jabatan = $approval->workflowStep->role->name ?? ($approval->workflowStep->role->code ?? '-');
            $nama = $approval->approver->name ?? '-';
            $verificationUrl = route('public.signature.show', $signature->public_signature_id);

            $this->embedApproverSlot($document, $approval->step_order, $jabatan, $nama, $verificationUrl);
        }

        // Pengaju juga perlu ditempel ulang (slot pertama, sentinel step_order = 0).
        if ($document->public_submitter_signature_id && $requiredSteps->isNotEmpty()) {
            $document->loadMissing('creator');
            $this->embedApproverSlot(
                $document,
                self::PENGAJU_STEP_ORDER,
                'Pengaju',
                $document->creator->name ?? '-',
                route('public.signature.show', $document->public_submitter_signature_id)
            );
        }
    }
}
