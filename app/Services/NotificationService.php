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

    private function notifyRoleUsers(Document $document, string $roleId, string $type, string $message): void
    {
        $targets = UserRole::query()
            ->where('role_id', $roleId)
            ->where(function ($query) use ($document) {
                $query->whereNull('organization_id')
                    ->orWhere('organization_id', $document->organization_id);
            })
            ->pluck('user_id')
            ->unique();

        foreach ($targets as $userId) {
            SystemNotification::query()->create([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'document_id' => $document->id,
                'type' => $type,
                'message' => $message,
            ]);
        }
    }
}
