<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Une page éditoriale du site. Voir `PageService`. */
class Page extends Model
{
    protected $fillable = [
        'slug', 'title', 'lede', 'body', 'seo_description', 'footer_group', 'footer_position',
        'is_published', 'is_system', 'internal_note', 'published_at', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_system' => 'boolean',
            'published_at' => 'datetime',
            'footer_position' => 'integer',
        ];
    }
}
