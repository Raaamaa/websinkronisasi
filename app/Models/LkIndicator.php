<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LkIndicator extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'lk_indicators';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'lk_id',
        'parent_id',
        'code',
        'title',
        'order_index',
        'evidence_link',
        'pic',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function lembarKerja(): BelongsTo
    {
        return $this->belongsTo(LembarKerja::class, 'lk_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function evidenceItems(): HasMany
    {
        return $this->hasMany(LkEvidenceItem::class, 'indicator_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(DocumentMatch::class, 'indicator_id');
    }
}

