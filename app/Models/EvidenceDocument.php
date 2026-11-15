<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Search\DocumentSearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'approval_status',
        'approval_notes',
        'approved_by',
        'approved_at',
        'source_type',
        'drive_file_id',
        'drive_folder_id',
        'drive_md5_checksum',
        'drive_modified_at',
    ];

    protected $attributes = [
        'approval_status' => 'menunggu',
        'source_type' => 'upload',
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
            'approved_at' => 'datetime',
            'drive_modified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(DocumentMatch::class, 'document_id');
    }

    public function lkLinks(): HasMany
    {
        return $this->hasMany(DocumentLkLink::class, 'document_id');
    }

    public function officialLkLinks(): HasMany
    {
        return $this->lkLinks()->whereIn('link_source', DocumentLkLink::OFFICIAL_SOURCES);
    }

    /**
     * Lembar Kerja resmi terkait bukti dukung (hanya sumber resmi: admin, drive_sync, backfill; tanpa duplikasi LK).
     *
     * @return BelongsToMany<LembarKerja>
     */
    public function lembarKerja(): BelongsToMany
    {
        return $this->belongsToMany(
            LembarKerja::class,
            'document_lk_links',
            'document_id',
            'lk_id'
        )->whereIn('document_lk_links.link_source', DocumentLkLink::OFFICIAL_SOURCES)
            ->distinct();
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
