<?php

namespace App\Http\Controllers;

use App\Http\Requests\Signature\StoreSignatureRequest;
use App\Services\SignatureService;
use DomainException;
use Illuminate\Http\JsonResponse;

class SignatureController extends Controller
{
    public function __construct(private readonly SignatureService $signatures)
    {
    }

    public function store(StoreSignatureRequest $request): JsonResponse
    {
        try {
            $signature = $this->signatures->create($request->user(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Signature berhasil dibuat.',
            'data' => $signature,
        ], 201);
    }
}
