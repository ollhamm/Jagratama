<?php

namespace App\Http\Controllers;

use App\Http\Requests\Approval\ApprovalIndexRequest;
use App\Http\Requests\Approval\ApproveRequest;
use App\Http\Requests\Approval\RejectRequest;
use App\Services\ApprovalService;
use DomainException;
use Illuminate\Http\JsonResponse;

class ApprovalController extends Controller
{
    public function __construct(private readonly ApprovalService $approvals)
    {
    }

    public function pending(ApprovalIndexRequest $request): JsonResponse
    {
        $result = $this->approvals->pending($request->user(), $request->validated());

        return response()->json($result);
    }

    public function approve(ApproveRequest $request, string $id): JsonResponse
    {
        try {
            $approval = $this->approvals->approve(
                $id,
                $request->user(),
                $request->validated('notes'),
                $request->validated('signature_value')
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Approval berhasil diproses.',
            'data' => $approval,
        ]);
    }

    public function reject(RejectRequest $request, string $id): JsonResponse
    {
        try {
            $approval = $this->approvals->reject(
                $id,
                $request->user(),
                $request->validated('notes')
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Approval berhasil direject.',
            'data' => $approval,
        ]);
    }
}
