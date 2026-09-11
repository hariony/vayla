<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une facture mensuelle réglée. Vayla n'encaisse rien : le propriétaire pousse
 * son règlement par mobile money, et cette ligne est la seule trace qu'il est
 * arrivé.
 */
class InvoiceSettlement extends Model
{
    protected $fillable = ['owner_id', 'month', 'amount', 'reference', 'admin_id', 'settled_at'];

    protected function casts(): array
    {
        // `month` n'est **pas** casté en date, et c'est délibéré : le cast
        // l'écrirait « 2026-08-01 00:00:00 », et SQLite — faiblement typé —
        // comparerait cette chaîne à « 2026-08-01 » sans jamais la trouver. Le
        // règlement consigné n'apparaissait pas, et le suivant heurtait
        // l'unicité. Une chaîne `AAAA-MM-JJ` se compare juste sur les deux
        // moteurs. Voir « Pièges » dans CLAUDE.md.
        return [
            'settled_at' => 'datetime',
            'amount' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }
}
