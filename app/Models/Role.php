<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'roles';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
    ];

    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function workflowSteps()
    {
        return $this->hasMany(WorkflowStep::class);
    }

    public function documentApprovals()
    {
        return $this->hasMany(DocumentApproval::class);
    }
}
