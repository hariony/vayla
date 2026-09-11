<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un texte du site **modifié** par l'équipe. L'original vit dans `SiteTextCatalog`. */
class SiteText extends Model
{
    public const CREATED_AT = null;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value', 'admin_id'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
