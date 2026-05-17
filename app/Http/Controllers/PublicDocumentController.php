<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;

class PublicDocumentController extends Controller
{
    public function show(string $id)
    {
        $document = Document::query()
            ->with([
                'documentType',
                'organization',
                'creator',
                'approvals.workflowStep.role',
                'approvals.approver',
                'approvals.signatures',
            ])
            ->whereNotNull('public_file_path')
            ->findOrFail($id);

        $approvalSignatures = $document->approvals
            ->sortBy('step_order')
            ->filter(fn ($a) => $a->signatures->isNotEmpty())
            ->flatMap(function ($approval) {
                return $approval->signatures->map(fn ($sig) => [
                    'role_name'       => $approval->workflowStep->role->name ?? '-',
                    'approver_name'   => $approval->approver->name ?? '-',
                    'signed_at'       => optional($sig->signed_at)->format('d/m/Y H:i'),
                    'signature_value' => $sig->signature_value,
                ]);
            });

        return view('pages.public.document', [
            'document'          => $document,
            'approvalSignatures' => $approvalSignatures,
        ]);
    }

    public function pdf(string $id): BinaryFileResponse
    {
        $document = Document::query()
            ->whereNotNull('public_file_path')
            ->findOrFail($id);

        $path = Storage::path($document->public_file_path);

        abort_unless(file_exists($path), 404);

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="dokumen-' . $id . '.pdf"',
        ]);
    }
}
