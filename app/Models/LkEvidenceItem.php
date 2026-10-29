<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LkEvidenceItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'lk_evidence_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'indicator_id',
        'raw_template_name',
        'resolved_name',
        'order_index',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(LkIndicator::class, 'indicator_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(DocumentMatch::class, 'matched_item_id');
    }
}

