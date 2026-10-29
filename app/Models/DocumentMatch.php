<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentMatch extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'document_matches';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'document_id',
        'indicator_id',
        'matched_item_id',
        'status',
        'match_method',
        'confidence_score',
        'match_reasons',
        'verified_by',
        'verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidence_score' => 'float',
            'match_reasons' => 'array',
            'verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(EvidenceDocument::class, 'document_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(LkIndicator::class, 'indicator_id');
    }

    public function matchedItem(): BelongsTo
    {
        return $this->belongsTo(LkEvidenceItem::class, 'matched_item_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
