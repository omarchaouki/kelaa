<?php

return [
    'role' => ['member' => 'Membre', 'business_owner' => 'Professionnel', 'moderator' => 'Modérateur', 'admin' => 'Administrateur'],
    'business_status' => ['pending' => 'En attente', 'published' => 'Publié', 'suspended' => 'Suspendu'],
    'review_status' => ['pending' => 'En attente', 'published' => 'Publié', 'rejected' => 'Refusé', 'hidden_by_moderation' => 'Masqué par la modération'],
    'reply_status' => ['pending' => 'En attente', 'published' => 'Publié', 'rejected' => 'Refusé', 'hidden_by_moderation' => 'Masqué par la modération'],
    'report_reason' => [
        'spam' => 'Spam ou publicité', 'fake' => 'Faux avis ou fausse fiche', 'offensive' => 'Propos injurieux',
        'conflict_of_interest' => "Conflit d'intérêts", 'wrong_information' => 'Informations erronées',
        'closed_business' => 'Établissement fermé', 'inappropriate_photo' => 'Photo inappropriée', 'other' => 'Autre',
    ],
    'report_status' => ['open' => 'Ouvert', 'resolved' => 'Traité', 'dismissed' => 'Classé sans suite'],
    'claim_method' => ['phone_otp_to_business_number' => 'Code envoyé au numéro de l’établissement', 'document_upload' => 'Justificatif (RC, patente, CIN)', 'manual' => 'Vérification manuelle'],
    'claim_status' => ['pending' => 'En attente', 'approved' => 'Approuvée', 'rejected' => 'Refusée'],
    'edit_suggestion_status' => ['pending' => 'En attente', 'approved' => 'Appliquée', 'rejected' => 'Refusée'],
    'billing_period' => ['monthly' => 'Mensuel', 'yearly' => 'Annuel'],
    'subscription_status' => ['pending' => 'En attente de paiement', 'active' => 'Actif', 'expired' => 'Expiré', 'cancelled' => 'Annulé'],
    'placement_status' => ['pending' => 'En attente de paiement', 'active' => 'Actif', 'expired' => 'Expiré', 'cancelled' => 'Annulé'],
    'payment_method' => ['bank_transfer' => 'Virement bancaire', 'cash_agency' => 'Espèces en agence (CashPlus, Wafacash)', 'cmi' => 'Carte bancaire (CMI)', 'manual' => 'Manuel'],
    'payment_status' => ['pending' => 'En attente', 'validated' => 'Validé', 'rejected' => 'Refusé', 'refunded' => 'Remboursé'],
    'contact_event_type' => ['profile_view' => 'Vue de la fiche', 'call_click' => 'Appel', 'whatsapp_click' => 'WhatsApp', 'directions_click' => 'Itinéraire', 'website_click' => 'Site web'],
    'contact_event_source' => ['organic' => 'Naturel', 'sponsored' => 'Sponsorisé', 'search' => 'Recherche', 'map' => 'Carte'],
    'otp_purpose' => ['login' => 'Connexion', 'business_claim' => 'Revendication', 'phone_verification' => 'Vérification du téléphone'],
    'search_sort' => ['relevance' => 'Pertinence', 'rating' => 'Mieux notés', 'distance' => 'Plus proches', 'reviews' => 'Plus d\'avis', 'newest' => 'Nouveautés'],
    'payment_purpose' => ['subscription' => 'Abonnement', 'placement' => 'Emplacement sponsorisé'],
    'placement_unit' => ['week' => 'Semaine', 'month' => 'Mois'],
];
