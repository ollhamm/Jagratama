<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGuide extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_guides';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'title',
        'content',
        'updated_by',
    ];

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
