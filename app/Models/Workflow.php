<?php

namespace App\Models;

use App\Enums\OrganizationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'workflows';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'organization_type',
        'document_type_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'organization_type' => OrganizationType::class,
            'is_active' => 'boolean',
        ];
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function steps()
    {
        return $this->hasMany(WorkflowStep::class);
    }

    public function instances()
    {
        return $this->hasMany(DocumentWorkflowInstance::class);
    }
}
