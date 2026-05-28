<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'documents';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'title',
        'document_type_id',
        'organization_id',
        'created_by',
        'current_status',
        'current_step_order',
        'submitted_at',
        'completed_at',
        'submitter_signature',
        'public_submitter_signature_id',
        'public_file_path',
        'published_at',
        'publish_status',
        'publish_notes',
    ];

    protected function casts(): array
    {
        return [
            'current_status' => DocumentStatus::class,
            'current_step_order' => 'integer',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->hasMany(DocumentAttachment::class);
    }

    public function workflowInstances()
    {
        return $this->hasMany(DocumentWorkflowInstance::class);
    }

    public function approvals()
    {
        return $this->hasMany(DocumentApproval::class);
    }
}
