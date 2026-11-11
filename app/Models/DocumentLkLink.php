<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLkLink extends Model
{
    use HasFactory, HasUuids;

    /**
     * Sumber link resmi yang dihitung sebagai centang pada Matriks & Cari Dokumen.
     *
     * @var list<string>
     */
    public const OFFICIAL_SOURCES = ['admin', 'drive_sync', 'backfill'];

    protected $table = 'document_lk_links';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'document_id',
        'lk_id',
        'indicator_id',
        'link_source',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(EvidenceDocument::class, 'document_id');
    }

    public function lembarKerja(): BelongsTo
    {
        return $this->belongsTo(LembarKerja::class, 'lk_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(LkIndicator::class, 'indicator_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

