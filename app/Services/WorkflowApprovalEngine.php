<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Enums\SignatureType;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentWorkflowInstance;
use App\Models\PublicSignature;
use App\Models\Signature;
use App\Models\User;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use App\Repositories\Contracts\WorkflowRepositoryInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WorkflowApprovalEngine
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly ApprovalRepositoryInterface $approvals,
        private readonly NotificationService $notifications,
        private readonly PdfSignatureSlotService $signatureSlots,
    ) {
    }

    public function submitDocument(Document $document): DocumentWorkflowInstance
    {
        return DB::transaction(function () use ($document) {
            $document->loadMissing('organization');

            $organizationType = $document->organization?->type?->value ?? $document->organization?->type;
            $workflow = $this->workflows->findActiveByType((string) $organizationType, $document->document_type_id);

            if (! $workflow) {
                throw new DomainException('Workflow aktif tidak ditemukan untuk tipe organisasi dan tipe dokumen ini.');
            }

            if ($workflow->steps->isEmpty()) {
                throw new DomainException('Workflow tidak memiliki step.');
            }

            // Step pertama bisa punya beberapa role eligible sekaligus (step_order sama,
            // role_id beda) — mis. "PJ Kemha Jurusan/Kaprodi/Kajur". Ambil SEMUA baris di
            // step_order terkecil, bukan cuma satu.
            $firstStepOrder = $workflow->steps->min('step_order');
            $firstSteps = $workflow->steps->where('step_order', $firstStepOrder)->values();

            $document->update([
                'current_status' => DocumentStatus::SUBMITTED,
                'current_step_order' => $firstStepOrder,
                'submitted_at' => now(),
            ]);

            $instance = DocumentWorkflowInstance::query()->create([
                'document_id' => $document->id,
                'workflow_id' => $workflow->id,
                'current_step_order' => $firstStepOrder,
                'status' => DocumentStatus::IN_REVIEW,
                'started_at' => now(),
            ]);

            foreach ($firstSteps as $firstStep) {
                $this->approvals->create([
                    'document_id' => $document->id,
                    'workflow_step_id' => $firstStep->id,
                    // placeholder sampai approver aktual melakukan aksi
                    'approved_by' => $document->created_by,
                    'step_order' => $firstStep->step_order,
                    'role_id' => $firstStep->role_id,
                    'status' => ApprovalStatus::PENDING,
                    'notes' => null,
                    'approved_at' => null,
                ]);

                $this->notifications->notifyDocumentSubmitted($document, $firstStep->role_id);
            }

            return $instance;
        });
    }

    public function approveByApprovalId(string $approvalId, User $approver, ?string $notes = null, ?string $signatureValue = null): DocumentApproval
    {
        return DB::transaction(function () use ($approvalId, $approver, $notes, $signatureValue) {
            $approval = $this->approvals->findPendingByIdForUser($approvalId, $approver);
            if (! $approval) {
                throw new DomainException('Approval pending tidak ditemukan atau Anda tidak berhak mengakses.');
            }

            $document = $approval->document;
            $instance = $this->getActiveInstance($document);
            $step = $approval->workflowStep;

            if ($instance->current_step_order !== $step->step_order) {
                throw new DomainException('Approval bukan pada step aktif saat ini.');
            }

            if ($step->is_required_signature && blank($signatureValue)) {
                throw new DomainException('Step ini mewajibkan tanda tangan barcode.');
            }

            $approval->update([
                'approved_by' => $approver->id,
                'status' => ApprovalStatus::APPROVED,
                'notes' => $notes,
                'approved_at' => now(),
            ]);

            if (! blank($signatureValue)) {
                $approval->load('workflowStep.role');
                $publicSig = PublicSignature::query()->create([
                    'document_id'    => $approval->document_id,
                    'signer_name'    => $approver->name,
                    'role_name'      => $approval->workflowStep->role->name ?? ($approval->workflowStep->role->code ?? '-'),
                    'signature_value' => $signatureValue,
                    'signed_at'      => now(),
                ]);

                Signature::query()->create([
                    'document_approval_id' => $approval->id,
                    'signature_type'       => SignatureType::BARCODE,
                    'signature_value'      => $signatureValue,
                    'signed_at'            => now(),
                    'public_signature_id'  => $publicSig->id,
                ]);

                $this->embedSlotForStep(
                    $document,
                    $step->step_order,
                    $approval->workflowStep->role->name ?? ($approval->workflowStep->role->code ?? '-'),
                    $approver->name,
                    route('public.signature.show', $publicSig->id),
                    $approval->workflowStep->role->code ?? null
                );
            }

            // Step ini mungkin punya beberapa role eligible (siapa cepat dia dapat) — role
            // lain yang masih PENDING di step_order yang sama otomatis dilewati begitu
            // salah satu approve, lalu pemegang role itu diberi tahu siapa & kapan.
            $this->skipSiblingApprovals($document, $step->step_order, $approval->id, $approver->name);

            $nextSteps = $this->workflows->findNextSteps($instance->workflow_id, $step->step_order);

            if ($nextSteps->isEmpty()) {
                $this->completeDocument($document, $instance);
                $this->notifications->notifyCompleted($document);
                Log::info('dokumen_selesai', [
                    'document_id' => $document->id,
                    'approved_by' => $approver->id,
                ]);
                return $approval;
            }

            foreach ($nextSteps as $nextStep) {
                $this->approvals->create([
                    'document_id' => $document->id,
                    'workflow_step_id' => $nextStep->id,
                    'approved_by' => $approver->id,
                    'step_order' => $nextStep->step_order,
                    'role_id' => $nextStep->role_id,
                    'status' => ApprovalStatus::PENDING,
                    'notes' => null,
                    'approved_at' => null,
                ]);

                $this->notifications->notifyApprovalPending($document, $nextStep->role_id);
            }

            $nextStepOrder = $nextSteps->first()->step_order;

            $instance->update([
                'current_step_order' => $nextStepOrder,
                'status' => DocumentStatus::IN_REVIEW,
            ]);

            $document->update([
                'current_status' => DocumentStatus::IN_REVIEW,
                'current_step_order' => $nextStepOrder,
            ]);

            Log::info('approval_dilakukan', [
                'approval_id' => $approval->id,
                'document_id' => $document->id,
                'approved_by' => $approver->id,
                'step_order' => $step->step_order,
                'result' => ApprovalStatus::APPROVED->value,
                'notes' => $notes,
            ]);

            return $approval;
        });
    }

    public function rejectByApprovalId(string $approvalId, User $approver, ?string $notes = null): DocumentApproval
    {
        return DB::transaction(function () use ($approvalId, $approver, $notes) {
            $approval = $this->approvals->findPendingByIdForUser($approvalId, $approver);
            if (! $approval) {
                throw new DomainException('Approval pending tidak ditemukan atau Anda tidak berhak mengakses.');
            }

            $document = $approval->document;
            $instance = $this->getActiveInstance($document);
            $step = $approval->workflowStep;

            if ($instance->current_step_order !== $step->step_order) {
                throw new DomainException('Approval bukan pada step aktif saat ini.');
            }

            if (! $step->can_reject) {
                throw new DomainException('Step ini tidak mengizinkan aksi reject.');
            }

            $approval->update([
                'approved_by' => $approver->id,
                'status' => ApprovalStatus::REJECTED,
                'notes' => $notes,
                'approved_at' => now(),
            ]);

            // Role lain yang masih PENDING di step_order yang sama (kalau step ini punya
            // beberapa role eligible) ikut dilewati — dokumennya sudah REJECTED, tidak perlu
            // ada approval menggantung.
            DocumentApproval::query()
                ->where('document_id', $document->id)
                ->where('step_order', $step->step_order)
                ->where('status', ApprovalStatus::PENDING)
                ->where('id', '!=', $approval->id)
                ->update([
                    'status' => ApprovalStatus::SKIPPED,
                    'notes' => 'Dokumen direject melalui role lain di step yang sama.',
                    'approved_at' => now(),
                ]);

            $instance->update([
                'status' => DocumentStatus::REJECTED,
                'finished_at' => now(),
            ]);

            $document->update([
                'current_status' => DocumentStatus::REJECTED,
            ]);

            $this->notifications->notifyRejected($document);

            Log::info('approval_dilakukan', [
                'approval_id' => $approval->id,
                'document_id' => $document->id,
                'approved_by' => $approver->id,
                'step_order' => $step->step_order,
                'result' => ApprovalStatus::REJECTED->value,
                'notes' => $notes,
            ]);

            return $approval;
        });
    }

    public function resubmitDocument(Document $document): DocumentWorkflowInstance
    {
        return DB::transaction(function () use ($document) {
            // Ambil instance yang rejected
            $instance = DocumentWorkflowInstance::query()
                ->where('document_id', $document->id)
                ->where('status', DocumentStatus::REJECTED)
                ->latest('started_at')
                ->first();

            if (! $instance) {
                throw new DomainException('Tidak ada dokumen yang berstatus rejected.');
            }

            // Ambil approval yang ditolak — paling baru, baik dari step_order maupun siklus reject berulang di step yang sama
            $rejectedApproval = DocumentApproval::query()
                ->where('document_id', $document->id)
                ->where('status', ApprovalStatus::REJECTED)
                ->orderByDesc('step_order')
                ->orderByDesc('created_at')
                ->first();

            if (! $rejectedApproval) {
                throw new DomainException('Approval yang ditolak tidak ditemukan.');
            }

            $rejectedStepOrder = $rejectedApproval->step_order;

            // Catatan reject DIBIARKAN sebagai histori (status REJECTED, notes tetap ada) —
            // tidak ditimpa. Buat approval baru di step_order yang sama untuk siklus berikutnya.
            $newApproval = $this->approvals->create([
                'document_id'       => $document->id,
                'workflow_step_id'  => $rejectedApproval->workflow_step_id,
                'approved_by'       => $document->created_by,
                'step_order'        => $rejectedApproval->step_order,
                'role_id'           => $rejectedApproval->role_id,
                'status'            => ApprovalStatus::PENDING,
                'notes'             => null,
                'approved_at'       => null,
            ]);

            // Aktifkan kembali instance dari step yang ditolak
            $instance->update([
                'status'              => DocumentStatus::IN_REVIEW,
                'current_step_order'  => $rejectedStepOrder,
                'finished_at'         => null,
            ]);

            // Update dokumen
            $document->update([
                'current_status'      => DocumentStatus::IN_REVIEW,
                'current_step_order'  => $rejectedStepOrder,
            ]);

            $this->notifications->notifyApprovalPending($document, $rejectedApproval->role_id, isResubmission: true);

            Log::info('dokumen_disubmit_ulang', [
                'document_id'       => $document->id,
                'resume_step_order' => $rejectedStepOrder,
                'previous_reject_notes' => $rejectedApproval->notes,
            ]);

            return $instance;
        });
    }

    /**
     * Tempel QR code (link verifikasi) + teks jabatan & nama ke slot tanda tangan
     * milik step_order ini, kalau dokumen punya pemetaan signature_slots untuk step ini.
     */
    private function embedSlotForStep(Document $document, int $stepOrder, string $jabatan, string $nama, string $verificationUrl, ?string $roleCode = null): void
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

        $document->loadMissing('attachments');
        $attachment = $document->attachments->first();
        if (! $attachment) {
            return;
        }

        $this->signatureSlots->embedSlot(
            Storage::path($attachment->file_path),
            $slot,
            $jabatan,
            $nama,
            $verificationUrl,
            $roleCode
        );
    }

    /**
     * Kalau step_order ini punya beberapa role eligible (mis. "PJ Kemha Jurusan/Kaprodi/
     * Kajur"), begitu satu role approve, role lain yang masih PENDING di step_order yang
     * sama otomatis dilewati (SKIPPED) dan pemegangnya diberi tahu siapa & kapan yang
     * approve — supaya tidak ada dua orang approve step yang sama.
     */
    private function skipSiblingApprovals(Document $document, int $stepOrder, string $approvedApprovalId, string $approverName): void
    {
        $siblings = DocumentApproval::query()
            ->where('document_id', $document->id)
            ->where('step_order', $stepOrder)
            ->where('status', ApprovalStatus::PENDING)
            ->where('id', '!=', $approvedApprovalId)
            ->get();

        $approvedAt = now();

        foreach ($siblings as $sibling) {
            $sibling->update([
                'status' => ApprovalStatus::SKIPPED,
                'notes' => sprintf('Otomatis dilewati — sudah disetujui oleh %s.', $approverName),
                'approved_at' => $approvedAt,
            ]);

            $this->notifications->notifyApprovalTakenByPeer($document, $sibling->role_id, $approverName, $approvedAt);
        }
    }

    private function completeDocument(Document $document, DocumentWorkflowInstance $instance): void
    {
        $instance->update([
            'status' => DocumentStatus::COMPLETED,
            'finished_at' => now(),
        ]);

        $document->update([
            'current_status' => DocumentStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    private function getActiveInstance(Document $document): DocumentWorkflowInstance
    {
        $instance = DocumentWorkflowInstance::query()
            ->where('document_id', $document->id)
            ->whereNull('finished_at')
            ->latest('started_at')
            ->first();

        if (! $instance) {
            throw new DomainException('Workflow instance aktif untuk dokumen ini tidak ditemukan.');
        }

        return $instance;
    }
}
