<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\ApprovalIndexRequest;
use App\Http\Requests\Approval\ApproveRequest;
use App\Http\Requests\Approval\RejectRequest;
use App\Services\ApprovalService;
use App\Services\DocumentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApprovalPageController extends Controller
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly DocumentService $documents,
    ) {
    }

    public function pending(ApprovalIndexRequest $request): View
    {
        $filters = [
            'search' => $request->validated('search'),
            'status' => $request->validated('status'),
            'per_page' => $request->validated('per_page') ?? 10,
        ];

        $pendingApprovals = $this->approvals->pending($request->user(), $filters, 'approval_page');
        $historyApprovals = $this->approvals->history($request->user(), $filters, 'approval_history_page');

        // Load relasi lengkap untuk dokumen approval aktif pertama
        $activeApproval = $pendingApprovals->first();
        if ($activeApproval) {
            $activeApproval->document->loadMissing([
                'attachments',
                'workflowInstances.workflow.steps.role',
                'approvals.workflowStep.role',
                'approvals.approver',
            ]);
        }

        $recentSignatures = $this->documents->getRecentSignatures(
            $request->user(),
            $activeApproval?->document_id
        );

        return view('pages.approvals.pending', [
            'title'            => 'Approval Dokumen',
            'pendingApprovals' => $pendingApprovals,
            'historyApprovals' => $historyApprovals,
            'activeApproval'   => $activeApproval,
            'recentApprovalSignatures' => $recentSignatures['approval'],
        ]);
    }

    public function approve(ApproveRequest $request, string $id): RedirectResponse
    {
        $redirectTo = $request->validated('redirect_to') ?? route('app.approvals.pending');

        try {
            $approval = $this->approvals->approve(
                $id,
                $request->user(),
                $request->validated('notes'),
                $request->validated('signature_value')
            );
        } catch (DomainException $exception) {
            return redirect()->to($redirectTo)->with('error', $exception->getMessage());
        }

        // Kalau approval ini yang menyelesaikan dokumen, selalu arahkan ke halaman detail
        // dokumen (bukan balik ke daftar pending) — supaya tombol Publish langsung terlihat.
        if ($approval->document?->current_status?->value === 'COMPLETED') {
            $redirectTo = route('app.documents.show', $approval->document_id);
        }

        return redirect()->to($redirectTo)->with('success', 'Approval berhasil diproses.');
    }

    public function reject(RejectRequest $request, string $id): RedirectResponse
    {
        $redirectTo = $request->validated('redirect_to') ?? route('app.approvals.pending');

        try {
            $this->approvals->reject($id, $request->user(), $request->validated('notes'));
        } catch (DomainException $exception) {
            return redirect()->to($redirectTo)->with('error', $exception->getMessage());
        }

        return redirect()->to($redirectTo)->with('success', 'Dokumen berhasil direject.');
    }
}
