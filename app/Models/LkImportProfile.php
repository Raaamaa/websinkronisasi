<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LkImportProfile extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'lk_import_profiles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'lk_code',
        'sheet_name',
        'header_row',
        'parsing_strategy',
        'column_mapping',
        'target_unit_column',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'header_row' => 'integer',
            'column_mapping' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Lembar kerja that use this profile as default.
     */
    public function lembarKerja(): HasMany
    {
        return $this->hasMany(LembarKerja::class, 'default_profile_id');
    }
}

