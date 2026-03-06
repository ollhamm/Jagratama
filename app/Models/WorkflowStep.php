<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'workflow_steps';

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
}
