<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\PublicSignature;
use App\Models\Signature;
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

    public function paginatePublished(User $user, array $filters, string $pageName = 'page'): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        return $this->documents->paginatePublishedForUser($user, $filters, $perPage, $pageName);
    }

    public function findForUser(string $id, User $user): ?Document
    {
        return $this->documents->findByIdForUser($id, $user);
    }

    /**
     * Ambil daftar tanda tangan terakhir yang pernah dipakai user ini di dokumen LAIN —
     * sebagai submitter (pengaju) dan/atau sebagai approver. Dipakai untuk tab
     * "Tanda Tangan Terakhir" di modal pad tanda tangan: ditampilkan sebagai daftar
     * gambar yang bisa langsung diklik, supaya user tidak perlu menggambar/upload
     * ulang kalau sudah pernah TTD sebelumnya.
     *
     * @return array{submitter: array<int, array{value: string, label: string}>, approval: array<int, array{value: string, label: string}>}
     */
    public function getRecentSignatures(User $user, ?string $excludeDocumentId = null, int $limit = 6): array
    {
        $submitterRows = Document::query()
            ->where('created_by', $user->id)
            ->whereNotNull('submitter_signature')
            ->when($excludeDocumentId, fn ($q) => $q->where('id', '!=', $excludeDocumentId))
            ->latest('submitted_at')
            ->limit($limit * 3)
            ->get(['title', 'submitted_at', 'submitter_signature']);

        $submitterSignatures = [];
        $seen = [];
        foreach ($submitterRows as $row) {
            if (in_array($row->submitter_signature, $seen, true)) {
                continue;
            }
            $seen[] = $row->submitter_signature;
            $submitterSignatures[] = [
                'value' => $row->submitter_signature,
                'label' => sprintf('%s — %s', $row->title, optional($row->submitted_at)->format('d/m/Y H:i') ?? '-'),
            ];
            if (count($submitterSignatures) >= $limit) {
                break;
            }
        }

        $approvalRows = Signature::query()
            ->whereHas('documentApproval', function ($q) use ($user, $excludeDocumentId) {
                $q->where('approved_by', $user->id);
                if ($excludeDocumentId) {
                    $q->where('document_id', '!=', $excludeDocumentId);
                }
            })
            ->with('documentApproval.document')
            ->latest('signed_at')
            ->limit($limit * 3)
            ->get();

        $approvalSignatures = [];
        $seenApproval = [];
        foreach ($approvalRows as $signature) {
            if (in_array($signature->signature_value, $seenApproval, true)) {
                continue;
            }
            $seenApproval[] = $signature->signature_value;
            $docTitle = $signature->documentApproval->document->title ?? '-';
            $approvalSignatures[] = [
                'value' => $signature->signature_value,
                'label' => sprintf('%s — %s', $docTitle, optional($signature->signed_at)->format('d/m/Y H:i') ?? '-'),
            ];
            if (count($approvalSignatures) >= $limit) {
                break;
            }
        }

        return [
            'submitter' => $submitterSignatures,
            'approval'  => $approvalSignatures,
        ];
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
                // Slot Pengaju tidak perlu label jabatan di atas QR — beda dengan approver lain.
                $this->embedApproverSlot(
                    $document,
                    self::PENGAJU_STEP_ORDER,
                    '',
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

    /**
     * Publish dokumen yang sudah COMPLETED, langsung pakai attachment asli
     * (yang sudah berisi QR tanda tangan tiap approver) — tidak ada upload file baru.
     * Hanya boleh dilakukan oleh approver di step PALING AKHIR alur dokumen ini, atau admin.
     */
    public function publishCompleted(Document $document, User $actor): void
    {
        if ($document->current_status !== DocumentStatus::COMPLETED) {
            throw new DomainException('Dokumen hanya bisa dipublikasikan setelah semua approval selesai.');
        }

        if ($document->published_at) {
            throw new DomainException('Dokumen ini sudah dipublikasikan sebelumnya.');
        }

        $isAdmin = $actor->userRoles()->whereHas('role', fn ($q) => $q->where('code', 'ADMIN'))->exists();

        if (! $isAdmin && ! $this->isLastApprover($document, $actor)) {
            throw new DomainException('Hanya approver pada step paling akhir yang dapat mempublikasikan dokumen ini.');
        }

        $document->loadMissing('attachments');
        $attachment = $document->attachments->first();
        if (! $attachment) {
            throw new DomainException('Lampiran dokumen tidak ditemukan.');
        }

        $document->update([
            'public_file_path' => $attachment->file_path,
            'publish_status'   => null,
            'publish_notes'    => null,
            'published_at'     => now(),
        ]);

        Log::info('dokumen_dipublikasikan', [
            'document_id'  => $document->id,
            'published_by' => $actor->id,
        ]);
    }

    /**
     * Cek apakah $actor adalah approver yang melakukan approval pada step_order
     * PALING BESAR (paling akhir) untuk dokumen ini — dihitung dinamis per dokumen,
     * bukan hardcode, karena jumlah step beda-beda tiap alur (org_type + document_type).
     */
    public function isLastApprover(Document $document, User $actor): bool
    {
        $lastApproval = DocumentApproval::query()
            ->where('document_id', $document->id)
            ->where('status', ApprovalStatus::APPROVED)
            ->orderByDesc('step_order')
            ->first();

        return $lastApproval !== null && $lastApproval->approved_by === $actor->id;
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

        // unique('step_order') — kalau 1 step_order punya beberapa role eligible (mis. "PJ
        // Kemha Jurusan/Kaprodi/Kajur"), tetap dihitung SATU slot tanda tangan di PDF, bukan
        // satu slot per role (cuma satu dari mereka yang akan benar-benar tanda tangan).
        return $workflow->steps->where('is_required_signature', true)->sortBy('step_order')->unique('step_order')->values();
    }

    /**
     * Ekstrak & validasi slot tanda tangan dari PDF attachment lewat key eksplisit
     * (${jabatan_pengaju}, ${jabatan_approver_1}, dst — lihat PdfSignatureSlotService),
     * petakan ke step_order, simpan ke documents.signature_slots. Melempar DomainException
     * kalau ada key yang tidak ditemukan di PDF.
     */
    private function prepareSignatureSlots(Document $document, \Illuminate\Support\Collection $requiredSteps): void
    {
        $attachment = $document->attachments->first();
        if (! $attachment) {
            throw new DomainException('Lampiran dokumen tidak ditemukan untuk validasi slot tanda tangan.');
        }

        $absolutePath = Storage::path($attachment->file_path);

        $stepOrders = array_merge([self::PENGAJU_STEP_ORDER], $requiredSteps->pluck('step_order')->values()->all());
        $identifiers = array_merge(
            [PdfSignatureSlotService::SLOT_PENGAJU],
            array_map(
                fn (int $position) => PdfSignatureSlotService::approverSlotId($position),
                range(1, $requiredSteps->count())
            )
        );

        $extracted = $this->signatureSlots->extractSlots($absolutePath, $identifiers);

        $slots = [];
        foreach ($identifiers as $index => $identifier) {
            $slots[] = array_merge($extracted['slots'][$identifier], ['step_order' => $stepOrders[$index]]);
        }

        $document->update([
            'signature_slots' => [
                'slots' => $slots,
            ],
        ]);
    }

    /**
     * Tempel QR + teks untuk satu step_order tertentu, kalau memang termasuk slot wajib TTD.
     */
    private function embedApproverSlot(Document $document, int $stepOrder, string $jabatan, string $nama, string $verificationUrl, ?string $roleCode = null): void
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
            $slot,
            $jabatan,
            $nama,
            $verificationUrl,
            $roleCode
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

            $this->embedApproverSlot($document, $approval->step_order, $jabatan, $nama, $verificationUrl, $approval->workflowStep->role->code ?? null);
        }

        // Pengaju juga perlu ditempel ulang (slot pertama, sentinel step_order = 0).
        // Tidak perlu label jabatan di atas QR — beda dengan approver lain.
        if ($document->public_submitter_signature_id && $requiredSteps->isNotEmpty()) {
            $document->loadMissing('creator');
            $this->embedApproverSlot(
                $document,
                self::PENGAJU_STEP_ORDER,
                '',
                $document->creator->name ?? '-',
                route('public.signature.show', $document->public_submitter_signature_id)
            );
        }
    }
}
