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
            ->whereNotNull('published_at')
            ->findOrFail($id);

        $approvalSignatures = $document->approvals
            ->sortBy('step_order')
            ->filter(fn ($a) => $a->signatures->isNotEmpty())
            ->flatMap(function ($approval) {
                return $approval->signatures->map(fn ($sig) => [
                    'role_name'     => $approval->workflowStep->role->name ?? '-',
                    'role_code'     => $approval->workflowStep->role->code ?? '',
                    'approver_name' => $approval->approver->name ?? '-',
                    'signed_at'     => optional($sig->signed_at)->format('d/m/Y H:i'),
                    'public_sig_id' => $sig->public_signature_id,
                ]);
            });

        $submitterSig = $document->public_submitter_signature_id
            ? [
                'name'          => $document->creator->name ?? '-',
                'public_sig_id' => $document->public_submitter_signature_id,
            ]
            : null;

        return view('pages.public.document', [
            'document'           => $document,
            'approvalSignatures' => $approvalSignatures,
            'submitterSig'       => $submitterSig,
        ]);
    }

    public function pdf(string $id): BinaryFileResponse
    {
        $document = Document::query()
            ->whereNotNull('public_file_path')
            ->whereNotNull('published_at')
            ->findOrFail($id);

        $path = Storage::path($document->public_file_path);

        abort_unless(file_exists($path), 404);

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="dokumen-' . $id . '.pdf"',
        ]);
    }
}
