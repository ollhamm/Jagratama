<?php

namespace App\Http\Controllers\Web;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Document\DocumentIndexRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\SubmitDocumentRequest;
use App\Models\DocumentApproval;
use App\Models\DocumentAttachment;
use App\Models\DocumentType;
use App\Models\Organization;
use App\Models\Workflow;
use App\Services\DocumentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentPageController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    public function index(DocumentIndexRequest $request): View
    {
        $filters = [
            'search' => $request->validated('search'),
            'status' => $request->validated('status'),
            'organization_id' => $request->validated('organization_id'),
            'per_page' => $request->validated('per_page') ?? 10,
        ];

        $documents = $this->documents->paginate($request->user(), $filters, 'doc_page');

        return view('pages.documents.index', [
            'title' => 'Dokumen',
            'documents' => $documents,
            'statuses' => DocumentStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('pages.documents.create', [
            'title' => 'Buat Pengajuan',
            'documentTypes' => DocumentType::query()->orderBy('name')->get(),
            'workflows' => Workflow::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'organization_type', 'document_type_id']),
            'organizations' => Organization::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $document = $this->documents->create($request->user(), $request->validated());

        return redirect()->route('app.documents.show', $document->id)
            ->with('success', 'Pengajuan berhasil dibuat. Lanjutkan ke halaman konfirmasi dokumen.');
    }

    public function show(string $id): View|RedirectResponse
    {
        $document = $this->documents->findForUser($id, auth()->user());

        if (! $document) {
            return redirect()->route('app.documents.index')->with('error', 'Dokumen tidak ditemukan.');
        }

        $document->loadMissing([
            'documentType',
            'organization',
            'creator',
            'attachments',
            'workflowInstances.workflow.steps.role',
            'approvals.workflowStep.role',
            'approvals.approver',
            'approvals.signatures',
        ]);

        $organizationType = (string) ($document->organization?->type->value ?? $document->organization?->type);
        $workflowName = Workflow::query()
            ->where('is_active', true)
            ->where('organization_type', $organizationType)
            ->where('document_type_id', $document->document_type_id)
            ->value('name');

        $signaturePayload = [
            'issuer' => 'JAGRATAMA',
            'version' => 1,
            'signature_id' => hash('sha256', implode('|', [
                $document->id,
                (string) $document->created_by,
                optional($document->created_at)->format('c') ?? now()->format('c'),
                (string) config('app.key'),
            ])),
            'document_id' => $document->id,
            'title' => $document->title,
            'document_type' => trim(($document->documentType->code ?? '-').' - '.($document->documentType->name ?? '-')),
            'organization' => $document->organization->name ?? '-',
            'submitter' => $document->creator->name ?? '-',
            'created_at' => optional($document->created_at)->toIso8601String(),
        ];

        $suggestedSignatureValue = base64_encode(json_encode($signaturePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $roleIds = auth()->user()->userRoles()->pluck('role_id');
        $pendingApprovalForUser = DocumentApproval::query()
            ->where('document_id', $document->id)
            ->where('status', ApprovalStatus::PENDING)
            ->where('step_order', $document->current_step_order)
            ->whereIn('role_id', $roleIds)
            ->with('workflowStep.role')
            ->first();

        $approvalSignaturePayload = null;
        $suggestedApprovalSignatureValue = null;
        if ($pendingApprovalForUser) {
            $approvalSignaturePayload = [
                'issuer' => 'JAGRATAMA',
                'version' => 1,
                'signature_scope' => 'approval',
                'signature_id' => hash('sha256', implode('|', [
                    $document->id,
                    $pendingApprovalForUser->id,
                    (string) auth()->id(),
                    (string) config('app.key'),
                ])),
                'document_id' => $document->id,
                'approval_id' => $pendingApprovalForUser->id,
                'step_order' => $pendingApprovalForUser->step_order,
                'role' => $pendingApprovalForUser->workflowStep->role->code ?? '-',
                'title' => $document->title,
                'document_type' => trim(($document->documentType->code ?? '-') . ' - ' . ($document->documentType->name ?? '-')),
                'organization' => $document->organization->name ?? '-',
                'approver' => auth()->user()->name ?? '-',
                'created_at' => now()->toIso8601String(),
            ];

            $suggestedApprovalSignatureValue = base64_encode(json_encode($approvalSignaturePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return view('pages.documents.show', [
            'title' => 'Konfirmasi Dokumen',
            'document' => $document,
            'workflowName' => $workflowName,
            'suggestedSignatureValue' => $suggestedSignatureValue,
            'signaturePayload' => $signaturePayload,
            'pendingApprovalForUser' => $pendingApprovalForUser,
            'approvalSignaturePayload' => $approvalSignaturePayload,
            'suggestedApprovalSignatureValue' => $suggestedApprovalSignatureValue,
        ]);
    }

    public function submit(SubmitDocumentRequest $request, string $id): RedirectResponse
    {
        $document = $this->documents->findForUser($id, auth()->user());

        if (! $document) {
            return redirect()->route('app.documents.index')->with('error', 'Dokumen tidak ditemukan.');
        }

        try {
            $this->documents->submit($document, auth()->user(), $request->validated('signature_value'));
        } catch (DomainException $exception) {
            return redirect()->route('app.documents.show', $id)->with('error', $exception->getMessage());
        }

        return redirect()->route('app.documents.show', $id)->with('success', 'Dokumen berhasil disubmit.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $document = $this->documents->findForUser($id, auth()->user());

        if (! $document) {
            return redirect()->route('app.documents.index')->with('error', 'Dokumen tidak ditemukan.');
        }

        try {
            $this->documents->deleteDraft($document, auth()->user());
        } catch (DomainException $exception) {
            return redirect()->route('app.documents.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('app.documents.index')->with('success', 'Draft pengajuan berhasil dihapus.');
    }

    public function download(string $id)
    {
        $document = $this->documents->findForUser($id, auth()->user());

        if (! $document) {
            return redirect()->route('app.documents.index')->with('error', 'Dokumen tidak ditemukan.');
        }

        $document->load(['documentType', 'organization', 'creator', 'approvals.workflowStep.role', 'approvals.signatures']);

        $content = [];
        $content[] = 'DOKUMEN FINAL JAGRATAMA';
        $content[] = '========================';
        $content[] = 'Judul: '.$document->title;
        $content[] = 'Tipe: '.($document->documentType->code ?? '-');
        $content[] = 'Organisasi: '.($document->organization->name ?? '-');
        $content[] = 'Pengaju: '.($document->creator->name ?? '-');
        $content[] = 'Status: '.($document->current_status->value ?? $document->current_status);
        $content[] = 'Submitter Signature: '.($document->submitter_signature ?? '-');
        $content[] = '';
        $content[] = 'RIWAYAT APPROVAL';
        $content[] = '---------------';

        foreach ($document->approvals->sortBy('step_order') as $approval) {
            $content[] = sprintf(
                'Step %d | Role %s | Status %s | Approver %s | Tanggal %s | Notes %s',
                $approval->step_order,
                $approval->workflowStep->role->code ?? '-',
                $approval->status->value ?? $approval->status,
                $approval->approver->name ?? '-',
                optional($approval->approved_at)->format('Y-m-d H:i:s') ?? '-',
                $approval->notes ?? '-'
            );

            foreach ($approval->signatures as $signature) {
                $content[] = '  - Signature ('.$signature->signature_type->value.'): '.$signature->signature_value;
            }
        }

        return response()->streamDownload(function () use ($content) {
            echo implode(PHP_EOL, $content);
        }, 'dokumen-final-'.$document->id.'.txt');
    }

    public function previewAttachment(string $id, string $attachmentId): BinaryFileResponse|RedirectResponse
    {
        $document = $this->documents->findForUser($id, auth()->user());

        if (! $document) {
            return redirect()->route('app.documents.index')->with('error', 'Dokumen tidak ditemukan.');
        }

        $attachment = DocumentAttachment::query()
            ->where('id', $attachmentId)
            ->where('document_id', $document->id)
            ->first();

        if (! $attachment) {
            return redirect()->route('app.documents.show', $document->id)->with('error', 'Lampiran tidak ditemukan.');
        }

        if (! Storage::exists($attachment->file_path)) {
            return redirect()->route('app.documents.show', $document->id)->with('error', 'File lampiran tidak tersedia.');
        }

        $path = Storage::path($attachment->file_path);
        $mimeType = $attachment->file_type ?: 'application/pdf';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.basename($attachment->file_path).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
