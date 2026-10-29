<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LkImportLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'lk_import_logs';

    protected $fillable = [
        'lk_id',
        'user_id',
        'profile_id',
        'file_name',
        'file_hash',
        'status',
        'total_rows_read',
        'imported_indicators',
        'imported_items',
        'skipped_rows',
        'summary_details',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_rows_read' => 'integer',
            'imported_indicators' => 'integer',
            'imported_items' => 'integer',
            'skipped_rows' => 'integer',
            'summary_details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function lembarKerja(): BelongsTo
    {
        return $this->belongsTo(LembarKerja::class, 'lk_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(LkImportProfile::class, 'profile_id');
    }
}

