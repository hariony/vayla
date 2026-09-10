<?php

namespace App\Models;

use App\Enums\NotificationKind;
use App\Support\Telephone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un message WhatsApp à envoyer, ou déjà envoyé. Relations, casts, et le lien
 * `wa.me` — qui est de la mise en forme d'une donnée, pas une décision.
 */
class OutboundMessage extends Model
{
    protected $fillable = ['kind', 'to', 'body', 'owner_id', 'booking_id', 'sent_at', 'failure'];

    protected function casts(): array
    {
        return ['kind' => NotificationKind::class, 'sent_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Le lien qui ouvre WhatsApp avec le message déjà écrit.
     *
     * C'est le canal d'envoi tant que Vayla n'a pas d'entreprise enregistrée :
     * l'API Business de Meta en exige une. Un clic, le message est là, il ne
     * reste qu'à appuyer sur envoyer — et ça ne coûte rien.
     *
     * Le numéro part **sans le `+`** : `wa.me` l'exige, et un `+` laissé là
     * ouvre une conversation vide sans dire pourquoi.
     */
    public function lienWhatsApp(): string
    {
        $numero = Telephone::depuis($this->to);

        return 'https://wa.me/'.($numero ? ltrim($numero->e164(), '+') : preg_replace('/\D+/', '', $this->to))
            .'?text='.rawurlencode($this->body);
    }

    public function envoye(): bool
    {
        return $this->sent_at !== null;
    }
}
