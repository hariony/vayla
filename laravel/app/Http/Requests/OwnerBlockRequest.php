<?php

namespace App\Http\Requests;

use App\Data\SejourData;
use App\Enums\BlockReason;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Le formulaire de blocage du propriétaire.
 *
 * Les deux dates vont **par paire**, comme partout ailleurs dans le dépôt :
 * une arrivée sans départ ne ferme rien, et un formulaire qui l'accepterait
 * en silence donnerait un calendrier que le propriétaire croit fermé.
 *
 * La borne haute est l'horizon du calendrier. Une période posée au-delà
 * n'apparaîtrait dans aucune grille : elle bloquerait des nuits que son
 * auteur ne pourrait plus retrouver pour les rouvrir.
 */
class OwnerBlockRequest extends FormRequest
{
    public function rules(): array
    {
        $horizon = Carbon::today()->addMonths(AvailabilityService::MOIS)->toDateString();

        return [
            'arrival' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'departure' => ['required', 'date_format:Y-m-d', 'after:arrival', 'before_or_equal:'.$horizon],
            'reason' => ['required', Rule::in(BlockReason::valeurs())],
        ];
    }

    /**
     * Des messages écrits pour quelqu'un qui répond depuis son téléphone.
     * « The departure field must be a date after arrival » n'est pas une
     * phrase que notre public lit — et une erreur qu'on ne comprend pas est
     * un formulaire qu'on abandonne.
     */
    public function messages(): array
    {
        return [
            'arrival.required' => 'Choisissez la première nuit à bloquer.',
            'arrival.after_or_equal' => 'On ne bloque pas une date déjà passée.',
            'departure.required' => 'Choisissez le jour où le logement se libère.',
            'departure.after' => 'Le jour de libération vient après la première nuit.',
            'departure.before_or_equal' => "Le calendrier ne va pas au-delà d'un an.",
            'reason.required' => 'Indiquez pourquoi ces nuits sont fermées.',
            'reason.in' => 'Indiquez pourquoi ces nuits sont fermées.',
        ];
    }

    /**
     * La période, déjà validée. `SejourData` n'est pas ici un garde-fou de
     * plus : c'est le seul endroit du dépôt qui sait tirer la dernière nuit
     * d'une date de départ.
     */
    public function sejour(): ?SejourData
    {
        return SejourData::depuis($this->string('arrival')->toString(), $this->string('departure')->toString());
    }

    public function motif(): BlockReason
    {
        return BlockReason::from($this->string('reason')->toString());
    }
}
