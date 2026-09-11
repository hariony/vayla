<?php

namespace App\Http\Requests;

use App\Data\StayRequests\SentStayRequestData;
use App\DTOs\StayRequests\StayRequestPrefillDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * La recherche en cours, arrivée dans l'adresse de `/demande` depuis l'accueil.
 *
 * **Une suggestion, jamais un critère** — la règle du séjour pré-rempli sur la
 * fiche : une valeur illisible est retirée avant la validation, elle ne fait
 * pas échouer la page. Un lien partagé au critère périmé doit ouvrir le
 * formulaire, pas renvoyer ailleurs.
 */
class StayRequestPrefillRequest extends FormRequest
{
    /** La clé de session du récapitulatif, posée par l'envoi et lue ici. */
    public const FLASH_ENVOYEE = 'demandeEnvoyee';

    public function rules(): array
    {
        return [
            'destination' => ['nullable', 'string', 'max:100'],
            'arrival' => ['nullable', 'date_format:Y-m-d'],
            'departure' => ['nullable', 'date_format:Y-m-d'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['arrival', 'departure'] as $date) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $this->query($date, ''))) {
                $this->query->remove($date);
            }
        }

        $voyageurs = (int) $this->query('guests', 2);
        $this->query->set('guests', max(1, min(30, $voyageurs ?: 2)));

        if (mb_strlen((string) $this->query('destination', '')) > 100) {
            $this->query->remove('destination');
        }
    }

    public function toDto(): StayRequestPrefillDto
    {
        return new StayRequestPrefillDto(
            destination: (string) $this->query('destination', ''),
            arrival: (string) $this->query('arrival', ''),
            departure: (string) $this->query('departure', ''),
            guests: $this->integer('guests', 2),
        );
    }

    /**
     * Le récapitulatif de la demande qui vient de partir, s'il y en a une. La
     * session est sérialisée en JSON : il y revient en tableau, on le retype ici.
     */
    public function envoyee(): ?SentStayRequestData
    {
        $envoyee = $this->session()->get(self::FLASH_ENVOYEE);

        return is_array($envoyee) ? SentStayRequestData::from($envoyee) : null;
    }
}
