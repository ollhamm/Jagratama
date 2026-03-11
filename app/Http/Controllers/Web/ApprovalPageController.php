<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\ApprovalIndexRequest;
use App\Http\Requests\Approval\ApproveRequest;
use App\Http\Requests\Approval\RejectRequest;
use App\Services\ApprovalService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApprovalPageController extends Controller
{
    public function __construct(private readonly ApprovalService $approvals)
    {
    }

    public function pending(ApprovalIndexRequest $request): View
    {
        $filters = [
            'search' => $request->validated('search'),
            'per_page' => $request->validated('per_page') ?? 10,
        ];

        $pendingApprovals = $this->approvals->pending($request->user(), $filters, 'approval_page');
        $historyApprovals = $this->approvals->history($request->user(), $filters, 'approval_history_page');

        return view('pages.approvals.pending', [
            'title' => 'Approval Dokumen',
            'pendingApprovals' => $pendingApprovals,
            'historyApprovals' => $historyApprovals,
        ]);
    }

    public function approve(ApproveRequest $request, string $id): RedirectResponse
    {
        $redirectTo = $request->validated('redirect_to') ?? route('app.approvals.pending');

        try {
            $this->approvals->approve(
                $id,
                $request->user(),
                $request->validated('notes'),
                $request->validated('signature_value')
            );
        } catch (DomainException $exception) {
            return redirect()->to($redirectTo)->with('error', $exception->getMessage());
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
