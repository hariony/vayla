<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = ['key', 'label', 'icon', 'position', 'sponsored'];

    protected function casts(): array
    {
        return ['sponsored' => 'boolean'];
    }

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class);
    }
}
