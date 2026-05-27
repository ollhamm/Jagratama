<?php

namespace App\Models;

use App\Enums\SignatureType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'signatures';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    public const CREATED_AT = 'signed_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'document_approval_id',
        'signature_type',
        'signature_value',
        'signed_at',
        'public_signature_id',
    ];

    protected function casts(): array
    {
        return [
            'signature_type' => SignatureType::class,
            'signed_at' => 'datetime',
        ];
    }

    public function documentApproval()
    {
        return $this->belongsTo(DocumentApproval::class);
    }
}
