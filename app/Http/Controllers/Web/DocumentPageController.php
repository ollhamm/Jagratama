<?php

namespace App\Http\Controllers\Web;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Document\DocumentIndexRequest;
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

    public function submit(string $id): RedirectResponse
    {
        $document = $this->documents->findForUser($id, auth()->user());

        if (! $document) {
            return redirect()->route('app.documents.index')->with('error', 'Dokumen tidak ditemukan.');
        }

        try {
            $this->documents->submit($document, auth()->user());
        } catch (DomainException $exception) {
            return redirect()->route('app.documents.show', $id)->with('error', $exception->getMessage());
        }

        return redirect()->route('app.documents.show', $id)->with('success', 'Dokumen berhasil disubmit.');
    }
}
