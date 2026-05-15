<?php

namespace App\Services;

use App\Models\Document;
use App\Models\SystemNotification;
use App\Models\UserRole;
use Illuminate\Support\Str;

class NotificationService
{
    public function notifyDocumentSubmitted(Document $document, string $firstRoleId): void
    {
        $this->notifyRoleUsers(
            $document,
            $firstRoleId,
            'DOCUMENT_SUBMITTED',
            sprintf('Dokumen "%s" telah disubmit dan menunggu approval Anda.', $document->title)
        );
    }

    public function notifyApprovalPending(Document $document, string $nextRoleId): void
    {
        $this->notifyRoleUsers(
            $document,
            $nextRoleId,
            'APPROVAL_PENDING',
            sprintf('Dokumen "%s" menunggu approval pada tahap Anda.', $document->title)
        );
    }

    public function notifyRejected(Document $document): void
    {
        SystemNotification::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $document->created_by,
            'document_id' => $document->id,
            'type' => 'DOCUMENT_REJECTED',
            'message' => sprintf('Dokumen "%s" direject. Silakan cek catatan revisi.', $document->title),
        ]);
    }

    public function notifyCompleted(Document $document): void
    {
        SystemNotification::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $document->created_by,
            'document_id' => $document->id,
            'type' => 'DOCUMENT_COMPLETED',
            'message' => sprintf('Dokumen "%s" telah selesai disetujui.', $document->title),
        ]);
    }

    // Role yang muncul di workflow lintas organisasi — tidak perlu filter per org saat notifikasi.
    private const GLOBAL_ROLE_CODES = [
        'ADMIN', 'DIREKTUR', 'WADIR_II', 'WADIR_III', 'KAPRODI', 'KAJUR',
        'PRESIDEN_BEM', 'MENTERI_MINAT_BAKAT_BEM', 'KOMISI_B_BLM',
        'PENANGGUNG_JAWAB_MAHASISWA', 'KA_SUB_BAG_AKADEMIK',
        'KA_BAG_AKADEMIK', 'KA_BAG_AKADEMIK_UMUM',
    ];

    private function notifyRoleUsers(Document $document, string $roleId, string $type, string $message): void
    {
        $role = \App\Models\Role::query()->find($roleId);
        $isGlobalRole = in_array($role?->code, self::GLOBAL_ROLE_CODES, true);

        $query = UserRole::query()->where('role_id', $roleId);

        if (! $isGlobalRole) {
            $query->where(function ($q) use ($document) {
                $q->whereNull('organization_id')
                    ->orWhere('organization_id', $document->organization_id);
            });
        }

        $targets = $query->pluck('user_id')->unique();

        foreach ($targets as $userId) {
            SystemNotification::query()->create([
                'id'          => (string) Str::uuid(),
                'user_id'     => $userId,
                'document_id' => $document->id,
                'type'        => $type,
                'message'     => $message,
            ]);
        }
    }
}
