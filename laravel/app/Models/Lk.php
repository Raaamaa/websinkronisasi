<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lk extends Model
{
    protected $fillable = ['name', 'scope'];

    public function evidences(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }
}
