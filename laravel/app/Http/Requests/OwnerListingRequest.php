<?php

namespace App\Http\Requests;

use App\DTOs\Listings\AmenityChoiceDto;
use App\DTOs\Listings\ListingDraftDto;
use App\DTOs\Listings\ListingFicheDto;
use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * La fiche saisie par le propriétaire.
 *
 * **Aucune règle sur le niveau de confiance, et c'est le point entier.**
 * `trust_level` n'est pas dans cette liste, donc il n'entre jamais par ce
 * formulaire : il est attribué par Vayla après vérification. Un propriétaire
 * qui pourrait se déclarer « séjour confirmé » viderait de son sens le seul
 * argument du produit.
 *
 * **Les bornes ne sont pas décoratives.** Un prix ou une capacité non bornés
 * laissent passer des valeurs qui cassent l'affichage sans rien signifier —
 * et sur une plateforme naissante, une annonce à 999 999 999 Ar aperçue une
 * fois suffit à faire douter de tout le reste.
 *
 * **Les messages sont écrits pour quelqu'un qui remplit ça sur son
 * téléphone.** « The guests field must be an integer » n'est pas une phrase
 * que notre public lit, et une erreur qu'on ne comprend pas est un formulaire
 * qu'on abandonne.
 */
class OwnerListingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:8', 'max:120'],
            'destination_id' => ['required', 'integer', 'exists:destinations,id'],
            'kind' => ['required', Rule::in(array_column(PropertyType::cases(), 'value'))],
            'summary' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:4000'],

            'guests' => ['required', 'integer', 'min:1', 'max:30'],
            'bedrooms' => ['required', 'integer', 'min:0', 'max:20'],
            'beds' => ['required', 'integer', 'min:1', 'max:40'],
            'bathrooms' => ['required', 'integer', 'min:1', 'max:20'],
            'surface' => ['nullable', 'integer', 'min:8', 'max:2000'],

            'price' => ['required', 'integer', 'min:5000', 'max:20000000'],
            'min_nights' => ['required', 'integer', 'min:1', 'max:90'],
            'max_nights' => ['nullable', 'integer', 'min:1', 'max:365', 'gte:min_nights'],

            'check_in_from' => ['required', 'date_format:H:i'],
            'check_out_before' => ['required', 'date_format:H:i'],
            'pets_allowed' => ['boolean'],
            'smoking_allowed' => ['boolean'],
            'events_allowed' => ['boolean'],

            'amenities' => ['array', 'max:120'],
            'amenities.*.id' => ['required', 'integer', 'exists:amenities,id'],
            'amenities.*.highlight' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Donnez un nom à votre logement.',
            'title.min' => 'Le nom est trop court : dites le type et le lieu, par exemple « Villa vue lagon, Ambatoloaka ».',
            'destination_id.required' => 'Choisissez la destination la plus proche.',
            'kind.required' => 'Choisissez le type de logement.',

            'guests.required' => 'Combien de personnes le logement peut-il accueillir au maximum ?',
            'guests.max' => 'Trente personnes au maximum. Au-delà, contactez Vayla.',
            'bedrooms.required' => 'Combien de chambres ?',
            'beds.required' => 'Combien de couchages en tout ?',
            'bathrooms.required' => 'Combien de salles d’eau ?',
            'surface.min' => 'La surface semble trop petite. Laissez vide si vous ne la connaissez pas.',

            'price.required' => 'Indiquez votre tarif pour une nuit, en ariary.',
            'price.min' => 'Ce tarif semble trop bas. Indiquez le prix d’une nuit, pas d’une heure.',
            'price.max' => 'Ce tarif semble trop élevé. Vérifiez le nombre de zéros.',

            'min_nights.required' => 'Combien de nuits au minimum ?',
            'max_nights.gte' => 'Le maximum de nuits ne peut pas être inférieur au minimum.',

            'check_in_from.required' => 'À partir de quelle heure peut-on arriver ?',
            'check_out_before.required' => 'Avant quelle heure faut-il partir ?',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Les cases décochées n'arrivent pas dans la charge utile : sans ça,
        // décocher « animaux acceptés » ne le décocherait jamais en base.
        $this->merge([
            'pets_allowed' => $this->boolean('pets_allowed'),
            'smoking_allowed' => $this->boolean('smoking_allowed'),
            'events_allowed' => $this->boolean('events_allowed'),
        ]);
    }

    public function toDraftDto(): ListingDraftDto
    {
        return new ListingDraftDto($this->ficheDto(), $this->equipementsDto());
    }

    /**
     * La fiche, nettoyée. `$featured` : seul le back-office met en avant ; nul,
     * la colonne n'est pas écrite.
     */
    protected function ficheDto(?bool $featured = null): ListingFicheDto
    {
        $texte = fn (string $champ) => $this->filled($champ) ? trim($this->string($champ)->toString()) : null;
        $entier = fn (string $champ) => $this->filled($champ) ? $this->integer($champ) : null;

        return new ListingFicheDto(
            title: trim($this->string('title')->toString()),
            destinationId: $this->integer('destination_id'),
            kind: $this->enum('kind', PropertyType::class),
            summary: $texte('summary'),
            description: $texte('description'),
            guests: $this->integer('guests'),
            bedrooms: $this->integer('bedrooms'),
            beds: $this->integer('beds'),
            bathrooms: $this->integer('bathrooms'),
            surface: $entier('surface'),
            price: $this->integer('price'),
            minNights: $this->integer('min_nights'),
            maxNights: $entier('max_nights'),
            checkInFrom: $this->string('check_in_from')->toString(),
            checkOutBefore: $this->string('check_out_before')->toString(),
            petsAllowed: $this->boolean('pets_allowed'),
            smokingAllowed: $this->boolean('smoking_allowed'),
            eventsAllowed: $this->boolean('events_allowed'),
            featured: $featured,
        );
    }

    /** @return list<AmenityChoiceDto> */
    protected function equipementsDto(): array
    {
        return array_values(array_map(
            fn (array $c) => new AmenityChoiceDto((int) $c['id'], (bool) ($c['highlight'] ?? false)),
            $this->validated('amenities', []),
        ));
    }
}
