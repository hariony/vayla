<?php

namespace App\Enums;

/**
 * Ce qu'un administrateur peut faire et qui mérite une trace.
 *
 * **Seulement les gestes qui engagent quelqu'un d'autre** : un propriétaire
 * (sa fiche, son niveau, sa facture), un voyageur (son séjour), ou l'équipe.
 * Consulter ne s'écrit pas — un journal où chaque ouverture de page laisse une
 * ligne est un journal que personne ne relit.
 */
enum AdminActionKind: string
{
    case ListingPublished = 'listing_published';
    case ListingReturned = 'listing_returned';
    case ListingArchived = 'listing_archived';
    case TrustLevelChanged = 'trust_level_changed';
    case PhoneVerified = 'phone_verified';
    case AccessLinkSent = 'access_link_sent';
    case BookingCancelled = 'booking_cancelled';
    case MessageWritten = 'message_written';
    case WhatsAppSent = 'whatsapp_sent';
    case InvoiceSettled = 'invoice_settled';
    case InvoiceReopened = 'invoice_reopened';
    case AdminAdded = 'admin_added';
    case AdminRemoved = 'admin_removed';
    case AdminPasswordReset = 'admin_password_reset';
    case ListingEdited = 'listing_edited';
    case ListingCreated = 'listing_created';
    case DestinationSaved = 'destination_saved';
    case DestinationDeleted = 'destination_deleted';
    case CategorySaved = 'category_saved';
    case CategoryDeleted = 'category_deleted';
    case AmenitySaved = 'amenity_saved';
    case AmenityDeleted = 'amenity_deleted';
    case SettingChanged = 'setting_changed';
    case PageSaved = 'page_saved';
    case PagePublished = 'page_published';
    case PageUnpublished = 'page_unpublished';
    case PageDeleted = 'page_deleted';
    case SiteTextsChanged = 'site_texts_changed';
    case PhotoUploaded = 'photo_uploaded';
    case PhotoEdited = 'photo_edited';
    case PhotoDeleted = 'photo_deleted';
    case StayRequestTaken = 'stay_request_taken';
    case StayRequestClosed = 'stay_request_closed';

    public function label(): string
    {
        return match ($this) {
            self::ListingPublished => 'Annonce mise en ligne',
            self::ListingReturned => 'Annonce renvoyée au propriétaire',
            self::ListingArchived => 'Annonce archivée',
            self::TrustLevelChanged => 'Niveau de confiance modifié',
            self::PhoneVerified => 'Numéro vérifié par appel',
            self::AccessLinkSent => "Lien d'accès renvoyé",
            self::BookingCancelled => 'Réservation annulée',
            self::MessageWritten => 'Message de Vayla dans un fil',
            self::WhatsAppSent => 'Message WhatsApp envoyé',
            self::InvoiceSettled => 'Facture réglée',
            self::InvoiceReopened => 'Règlement annulé',
            self::AdminAdded => "Membre ajouté à l'équipe",
            self::AdminRemoved => "Membre retiré de l'équipe",
            self::AdminPasswordReset => 'Mot de passe remis à zéro',
            self::ListingEdited => 'Contenu d’une annonce modifié',
            self::ListingCreated => 'Annonce saisie pour un propriétaire',
            self::DestinationSaved => 'Destination enregistrée',
            self::DestinationDeleted => 'Destination supprimée',
            self::CategorySaved => 'Catégorie enregistrée',
            self::CategoryDeleted => 'Catégorie supprimée',
            self::AmenitySaved => 'Équipement enregistré',
            self::AmenityDeleted => 'Équipement supprimé',
            self::SettingChanged => 'Réglage modifié',
            self::PageSaved => 'Page enregistrée',
            self::PagePublished => 'Page publiée',
            self::PageUnpublished => 'Page retirée du site',
            self::PageDeleted => 'Page supprimée',
            self::SiteTextsChanged => 'Textes du site modifiés',
            self::PhotoUploaded => 'Photo ajoutée à la photothèque',
            self::PhotoEdited => 'Crédit d’une photo corrigé',
            self::PhotoDeleted => 'Photo supprimée',
            self::StayRequestTaken => 'Demande de séjour prise en charge',
            self::StayRequestClosed => 'Demande de séjour close',
        };
    }

    /** La famille, pour le filtre du journal et la couleur de la pastille. */
    public function famille(): string
    {
        return match ($this) {
            self::ListingPublished, self::ListingReturned, self::ListingArchived, self::TrustLevelChanged,
            self::ListingEdited, self::ListingCreated => 'annonces',
            self::DestinationSaved, self::DestinationDeleted, self::CategorySaved, self::CategoryDeleted,
            self::AmenitySaved, self::AmenityDeleted, self::SettingChanged,
            self::PageSaved, self::PagePublished, self::PageUnpublished, self::PageDeleted, self::SiteTextsChanged,
            self::PhotoUploaded, self::PhotoEdited, self::PhotoDeleted => 'contenu',
            self::PhoneVerified, self::AccessLinkSent => 'proprietaires',
            self::BookingCancelled, self::MessageWritten, self::StayRequestTaken, self::StayRequestClosed => 'reservations',
            self::WhatsAppSent => 'whatsapp',
            self::InvoiceSettled, self::InvoiceReopened => 'facturation',
            self::AdminAdded, self::AdminRemoved, self::AdminPasswordReset => 'equipe',
        };
    }
}
