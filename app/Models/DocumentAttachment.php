<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentAttachment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'document_attachments';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    public const CREATED_AT = 'uploaded_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'document_id',
        'file_path',
        'file_type',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
