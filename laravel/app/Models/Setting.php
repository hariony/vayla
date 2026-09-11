<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Un réglage tenu depuis le back-office. Voir `SettingsService`. */
class Setting extends Model
{
    public const CREATED_AT = null;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value', 'admin_id'];
}
