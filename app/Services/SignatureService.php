<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\Signature;
use App\Models\User;
use DomainException;
use Illuminate\Support\Str;

class SignatureService
{
    public function create(User $user, array $payload): Signature
    {
        $approval = DocumentApproval::query()->with('document')->findOrFail($payload['document_approval_id']);

        if (! $this->canAccessDocument($user, $approval->document)) {
            throw new DomainException('Anda tidak memiliki akses ke dokumen ini.');
        }

        return Signature::query()->create([
            'document_approval_id' => $approval->id,
            'signature_type' => $payload['signature_type'],
            'signature_value' => $payload['signature_value'] ?? $this->generateBarcodeValue($user, $approval),
            'signed_at' => now(),
        ]);
    }

    private function generateBarcodeValue(User $user, DocumentApproval $approval): string
    {
        $raw = sprintf(
            'USR:%s|DOC:%s|APP:%s|TS:%s|NONCE:%s',
            $user->id,
            $approval->document_id,
            $approval->id,
            now()->toIso8601String(),
            Str::uuid()->toString(),
        );

        return base64_encode($raw);
    }

    private function canAccessDocument(User $user, Document $document): bool
    {
        $isGlobal = $user->userRoles()
            ->whereNull('organization_id')
            ->orWhereHas('role', fn ($q) => $q->whereIn('code', ['ADMIN', 'DIREKTUR', 'WADIR_III', 'KAPRODI', 'KAJUR']))
            ->exists();

        if ($isGlobal) {
            return true;
        }

        return $user->userRoles()
            ->where('organization_id', $document->organization_id)
            ->exists();
    }
}
