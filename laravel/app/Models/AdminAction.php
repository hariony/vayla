<?php

namespace App\Models;

use App\Enums\AdminActionKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une ligne du journal : qui a fait quoi, sur quoi, et quand.
 *
 * **On n'écrit qu'une fois** — pas de `updated_at`. Un journal qu'on peut
 * corriger n'est plus un journal.
 */
class AdminAction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['admin_id', 'admin_name', 'kind', 'subject_type', 'subject_id', 'summary', 'note'];

    protected function casts(): array
    {
        return ['kind' => AdminActionKind::class];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
