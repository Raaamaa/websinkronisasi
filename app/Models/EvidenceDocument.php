<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Search\DocumentSearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvidenceDocument extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'evidence_documents';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'document_name',
        'document_number',
        'year',
        'document_type',
        'external_link',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size_bytes',
        'file_hash',
        'extracted_text',
        'processing_status',
        'error_message',
        'uploaded_by',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'file_path',
        'extracted_text',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'file_size_bytes' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(DocumentMatch::class, 'document_id');
    }

    /**
     * Scope query to search documents via FTS, substring, and trigram fuzzy matching (SEARCH-01, SEARCH-02).
     *
     * @param  Builder<EvidenceDocument>  $query
     * @return Builder<EvidenceDocument>
     */
    public function scopeSearchFullTextAndFuzzy(Builder $query, ?string $searchTerm): Builder
    {
        /** @var DocumentSearchService $searchService */
        $searchService = app(DocumentSearchService::class);

        return $searchService->applySearchFilter($query, $searchTerm);
    }
}
