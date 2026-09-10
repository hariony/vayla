<?php

namespace App\Http\Requests;

use App\Data\ListingFiltreData;
use App\Data\SejourData;
use App\Enums\ListingSort;
use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation des critères de recherche, pour le site ET pour l'API.
 *
 * Une seule classe parce qu'il n'y a qu'un seul contrat : le catalogue de
 * `/logements` et `/api/v1/listings` acceptent exactement les mêmes
 * paramètres, et doivent refuser exactement les mêmes. Deux requêtes
 * jumelles auraient fini par diverger sur une borne.
 *
 * `authorize()` laisse passer — la lecture est publique — mais les bornes
 * sont fermes : un `per_page` non borné est une porte de déni de service,
 * et une liste d'équipements non bornée deviendrait deux cents jointures.
 */
class ListingIndexRequest extends FormRequest
{
    /**
     * Côté site, un critère invalide ne doit pas éjecter vers l'accueil —
     * c'est ce que faisait la redirection « retour » par défaut, faute de
     * page précédente. Un lien partagé qui porte un filtre périmé rouvre
     * donc le catalogue propre, sans le critère fautif.
     *
     * Côté API, cette propriété n'est jamais lue : `expectsJson()` déclenche
     * un 422 avec le détail des erreurs, et un client qui envoie n'importe
     * quoi doit l'apprendre bruyamment.
     */
    protected $redirect = '/logements';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:50'],
            // Les dates vont **par paire**. Une arrivée seule ne filtre rien,
            // et un critère qui ne filtre rien en silence est pire que pas de
            // critère : c'était le défaut du moteur de recherche, qui
            // collectait deux dates et les jetait.
            'arrival' => ['nullable', 'required_with:departure', 'date_format:Y-m-d', 'after_or_equal:today'],
            'departure' => ['nullable', 'required_with:arrival', 'date_format:Y-m-d', 'after:arrival'],
            'min_trust' => ['nullable', 'integer', 'min:1', 'max:4'],
            'kind' => ['nullable', 'string', Rule::enum(PropertyType::class)],
            'max_price' => ['nullable', 'integer', 'min:1'],
            'amenities' => ['nullable', 'array', 'max:20'],
            'amenities.*' => ['string', 'max:60'],
            'sort' => ['nullable', 'string', Rule::enum(ListingSort::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * Un séjour n'excède pas un an. Ce n'est pas une borne de performance —
     * la clause SQL coûte le même prix sur trois nuits que sur trois ans —
     * mais un séjour de dix ans n'est pas une recherche, c'est une erreur de
     * saisie ou une sonde, et lui répondre « aucun logement » serait un
     * message faux.
     */
    public function after(): array
    {
        return [
            function (Validator $validateur) {
                $sejour = SejourData::depuis($this->input('arrival'), $this->input('departure'));

                if ($sejour && $sejour->nights > 365) {
                    $validateur->errors()->add('departure', 'Un séjour ne peut pas dépasser un an.');
                }
            },
        ];
    }

    public function toFiltre(): ListingFiltreData
    {
        return new ListingFiltreData(
            destination: $this->input('destination'),
            category: $this->input('category'),
            guests: $this->integer('guests') ?: null,
            sejour: SejourData::depuis($this->input('arrival'), $this->input('departure')),
            minTrust: $this->integer('min_trust') ?: null,
            kind: $this->input('kind'),
            maxPrice: $this->integer('max_price') ?: null,
            amenities: array_values(array_filter((array) $this->input('amenities', []))),
            sort: ListingSort::tryFrom((string) $this->input('sort')) ?? ListingSort::defaut(),
            page: $this->integer('page') ?: 1,
            perPage: $this->integer('per_page') ?: 24,
        );
    }
}
