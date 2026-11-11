<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriveSyncFolder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'drive_sync_folders';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'folder_id',
        'label',
        'lk_id',
        'is_active',
        'last_synced_at',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function lembarKerja(): BelongsTo
    {
        return $this->belongsTo(LembarKerja::class, 'lk_id');
    }
}

