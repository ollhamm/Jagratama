<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Organization;
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

    public function notifyApprovalPending(Document $document, string $nextRoleId, bool $isResubmission = false): void
    {
        if ($isResubmission) {
            $this->notifyRoleUsers(
                $document,
                $nextRoleId,
                'APPROVAL_PENDING_REVISION',
                sprintf('[REVISI] Dokumen "%s" telah diperbaiki dan menunggu approval ulang pada tahap Anda.', $document->title)
            );
            return;
        }

        $this->notifyRoleUsers(
            $document,
            $nextRoleId,
            'APPROVAL_PENDING',
            sprintf('Dokumen "%s" menunggu approval pada tahap Anda.', $document->title)
        );
    }

    /**
     * Kirim ke pemegang role lain yang eligible di step yang sama (mis. Kaprodi/Kajur saat
     * PJ Kemha Jurusan yang approve duluan) — memberi tahu step ini sudah selesai lewat
     * role lain, tidak perlu approve lagi.
     */
    public function notifyApprovalTakenByPeer(Document $document, string $roleId, string $approverName, \DateTimeInterface $approvedAt): void
    {
        $this->notifyRoleUsers(
            $document,
            $roleId,
            'APPROVAL_TAKEN_BY_PEER',
            sprintf(
                'Dokumen "%s" pada tahap ini sudah disetujui oleh %s pada %s. Anda tidak perlu melakukan approval lagi.',
                $document->title,
                $approverName,
                $approvedAt->format('d M Y H:i')
            )
        );
    }

    public function notifyRejected(Document $document): void
    {
        SystemNotification::query()->create([
            'id'          => (string) Str::uuid(),
            'user_id'     => $document->created_by,
            'document_id' => $document->id,
            'type'        => 'DOCUMENT_REJECTED',
            'message'     => sprintf('Dokumen "%s" direject. Silakan cek catatan revisi.', $document->title),
        ]);
    }

    public function notifyCompleted(Document $document): void
    {
        SystemNotification::query()->create([
            'id'          => (string) Str::uuid(),
            'user_id'     => $document->created_by,
            'document_id' => $document->id,
            'type'        => 'DOCUMENT_COMPLETED',
            'message'     => sprintf('Dokumen "%s" telah selesai disetujui.', $document->title),
        ]);
    }

    // Role yang muncul di workflow lintas organisasi — notifikasi dikirim ke semua pemegang role tanpa filter org.
    private const GLOBAL_ROLE_CODES = [
        'ADMIN', 'DIREKTUR', 'WADIR_II', 'WADIR_III',
        'PRESIDEN_BEM', 'MENTERI_MINAT_BAKAT_BEM', 'KOMISI_B_BLM',
        'PENANGGUNG_JAWAB_MAHASISWA', 'KA_SUB_BAG_AKADEMIK',
        'KA_BAG_AKADEMIK', 'KA_BAG_AKADEMIK_UMUM',
    ];

    private function notifyRoleUsers(Document $document, string $roleId, string $type, string $message): void
    {
        $role          = \App\Models\Role::query()->find($roleId);
        $isGlobalRole  = in_array($role?->code, self::GLOBAL_ROLE_CODES, true);

        $query = UserRole::query()->where('role_id', $roleId);

        if (! $isGlobalRole) {
            // Untuk role org-scoped (KAJUR, KAPRODI, PJ, PEMBINA_UKM, dll):
            // user di-assign ke org ancestor (misal Jurusan Kebidanan),
            // dokumen berasal dari org turunan (misal HMJ Kebidanan).
            // Cocokkan dengan cara: org_id user harus ada di jalur ancestor dokumen.
            $ancestorIds   = $this->ancestorIds($document->organization_id);
            $ancestorIds[] = $document->organization_id;

            $query->where(function ($q) use ($ancestorIds) {
                $q->whereNull('organization_id')
                    ->orWhereIn('organization_id', $ancestorIds);
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

    private function ancestorIds(string $orgId): array
    {
        $ids = [];
        $org = Organization::query()->find($orgId);

        while ($org && $org->parent_id) {
            $ids[] = $org->parent_id;
            $org   = Organization::query()->find($org->parent_id);
        }

        return $ids;
    }
}
