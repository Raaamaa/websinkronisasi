<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LembarKerja extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'lembar_kerja';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'category',
        'period',
        'description',
        'default_profile_id',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => 'string',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Default import profile relation.
     */
    public function defaultProfile(): BelongsTo
    {
        return $this->belongsTo(LkImportProfile::class, 'default_profile_id');
    }

    /**
     * Indicators belonging to this LK.
     */
    public function indicators(): HasMany
    {
        return $this->hasMany(LkIndicator::class, 'lk_id');
    }

    /**
     * Import logs for this LK.
     */
    public function importLogs(): HasMany
    {
        return $this->hasMany(LkImportLog::class, 'lk_id');
    }
}
