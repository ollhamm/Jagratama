<?php

namespace App\Http\Controllers\Web;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Document\DocumentIndexRequest;
use App\Http\Requests\Document\SubmitDocumentRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Models\DocumentType;
use App\Models\Organization;
use App\Services\DocumentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DocumentPageController extends Controller
{
    public function __construct(private readonly DocumentService $documents)
    {
    }

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
            'title' => 'Buat Dokumen',
            'documentTypes' => DocumentType::query()->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $document = $this->documents->create($request->user(), $request->validated());

        return redirect()->route('app.documents.show', $document->id)
            ->with('success', 'Dokumen berhasil dibuat.');
    }

    public function show(string $id): View|RedirectResponse
    {
        $document = $this->documents->findForUser($id, auth()->user());

        if (! $document) {
            return redirect()->route('app.documents.index')->with('error', 'Dokumen tidak ditemukan.');
        }

        return view('pages.documents.show', [
            'title' => 'Detail Dokumen',
            'document' => $document,
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
}
