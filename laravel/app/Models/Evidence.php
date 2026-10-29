<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidence extends Model
{
    protected $table = 'evidences';

    protected $fillable = [
        'lk_id',
        'uploaded_by',
        'title',
        'document_number',
        'original_filename',
        'sha256',
        'drive_url',
    ];

    public function lk(): BelongsTo
    {
        return $this->belongsTo(Lk::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
