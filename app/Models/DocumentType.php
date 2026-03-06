<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'document_types';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }
}
