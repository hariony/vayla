<?php

namespace App\Models;

use App\Enums\AmenityGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    protected $fillable = ['key', 'label', 'group', 'icon', 'position', 'filterable'];

    protected function casts(): array
    {
        return [
            'group' => AmenityGroup::class,
            'filterable' => 'boolean',
        ];
    }

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class)->withPivot(['highlight', 'note']);
    }
}
