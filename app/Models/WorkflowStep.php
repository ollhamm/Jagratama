<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class WorkflowStep extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'workflow_steps';

    // Urutan tampil kalau beberapa role digabung jadi label 1 step_order (lihat
    // dedupeForTimeline()) — supaya konsisten, bukan ikut urutan baris dari DB.
    private const ROLE_DISPLAY_ORDER = [
        'PJ_MAHASISWA_ALUMNI_JURUSAN' => 0,
        'KAPRODI' => 1,
        'KAJUR' => 2,
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'workflow_id',
        'step_order',
        'role_id',
        'is_required_signature',
        'can_reject',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'is_required_signature' => 'boolean',
            'can_reject' => 'boolean',
        ];
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function approvals()
    {
        return $this->hasMany(DocumentApproval::class);
    }

    /**
     * Kalau 1 step_order punya beberapa role eligible (siapa cepat dia dapat approve —
     * mis. PJ Mahasiswa dan Alumni Jurusan / Kaprodi / Kajur), gabungkan jadi 1 entri per
     * step_order untuk ditampilkan di timeline "Progress Approval". Role-nya sendiri tetap
     * terpisah (tidak diubah/di-rename) — cuma label tampilan gabungannya yang dihitung di
     * sini, lewat atribut sementara `display_role_name` di step representatif.
     *
     * Dipakai bersama oleh resources/views/pages/documents/show.blade.php dan
     * resources/views/pages/approvals/pending.blade.php — ubah di sini saja kalau perlu
     * revisi urutan/format label, jangan duplikat logic-nya lagi di blade.
     */
    public static function dedupeForTimeline(Collection $steps): Collection
    {
        return $steps
            ->groupBy('step_order')
            ->map(function (Collection $stepsAtOrder) {
                $representative = $stepsAtOrder->first();
                $representative->display_role_name = $stepsAtOrder
                    ->sortBy(fn (self $step) => self::ROLE_DISPLAY_ORDER[$step->role->code ?? ''] ?? 999)
                    ->map(fn (self $step) => $step->role->name ?? $step->role->code ?? '-')
                    ->unique()
                    ->implode(' / ');

                return $representative;
            })
            ->sortBy('step_order')
            ->values();
    }
}
