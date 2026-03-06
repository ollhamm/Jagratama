<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Enums\SignatureType;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentWorkflowInstance;
use App\Models\Signature;
use App\Models\User;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use App\Repositories\Contracts\WorkflowRepositoryInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowApprovalEngine
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly ApprovalRepositoryInterface $approvals,
        private readonly NotificationService $notifications,
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

            $firstStep = $workflow->steps->first();
            if (! $firstStep) {
                throw new DomainException('Workflow tidak memiliki step.');
            }

            $document->update([
                'current_status' => DocumentStatus::SUBMITTED,
                'current_step_order' => $firstStep->step_order,
                'submitted_at' => now(),
            ]);

            $instance = DocumentWorkflowInstance::query()->create([
                'document_id' => $document->id,
                'workflow_id' => $workflow->id,
                'current_step_order' => $firstStep->step_order,
                'status' => DocumentStatus::IN_REVIEW,
                'started_at' => now(),
            ]);

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
                Signature::query()->create([
                    'document_approval_id' => $approval->id,
                    'signature_type' => SignatureType::BARCODE,
                    'signature_value' => $signatureValue,
                    'signed_at' => now(),
                ]);
            }

            $nextStep = $this->workflows->findNextStep($instance->workflow_id, $step->step_order);

            if (! $nextStep) {
                $this->completeDocument($document, $instance);
                $this->notifications->notifyCompleted($document);
                Log::info('dokumen_selesai', [
                    'document_id' => $document->id,
                    'approved_by' => $approver->id,
                ]);
                return $approval;
            }

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

            $instance->update([
                'current_step_order' => $nextStep->step_order,
                'status' => DocumentStatus::IN_REVIEW,
            ]);

            $document->update([
                'current_status' => DocumentStatus::IN_REVIEW,
                'current_step_order' => $nextStep->step_order,
            ]);

            Log::info('approval_dilakukan', [
                'approval_id' => $approval->id,
                'document_id' => $document->id,
                'approved_by' => $approver->id,
                'step_order' => $step->step_order,
                'result' => ApprovalStatus::APPROVED->value,
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
            ]);

            return $approval;
        });
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
