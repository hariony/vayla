<?php

namespace App\Models;

use App\Enums\StayRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une demande de séjour « dans l'autre sens ». Relations et casts, pas de logique. */
class StayRequest extends Model
{
    protected $fillable = [
        'destination_id', 'place', 'arrival', 'departure', 'guests', 'budget',
        'name', 'email', 'phone', 'message', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => StayRequestStatus::class,
            'guests' => 'integer',
            'budget' => 'integer',
            'taken_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
