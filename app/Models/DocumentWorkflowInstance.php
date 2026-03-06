<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentWorkflowInstance extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'document_workflow_instances';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    public const CREATED_AT = 'started_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'document_id',
        'workflow_id',
        'current_step_order',
        'status',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'current_step_order' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
