<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\DocumentIndexRequest;
use App\Http\Requests\Document\SubmitDocumentRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Services\DocumentService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents)
    {
    }

    public function index(DocumentIndexRequest $request): JsonResponse
    {
        $result = $this->documents->paginate($request->user(), $request->validated());

        return response()->json($result);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = $this->documents->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Dokumen berhasil dibuat.',
            'data' => $document,
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $document = $this->documents->findForUser($id, $request->user());

        if (! $document) {
            return response()->json(['message' => 'Dokumen tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => $document,
        ]);
    }

    public function submit(SubmitDocumentRequest $request, string $id): JsonResponse
    {
        $document = $this->documents->findForUser($id, $request->user());

        if (! $document) {
            return response()->json(['message' => 'Dokumen tidak ditemukan.'], 404);
        }

        try {
            $this->documents->submit($document, $request->user(), $request->validated('signature_value'));
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Dokumen berhasil disubmit.',
        ]);
    }
}
