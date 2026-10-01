<?php

/*
 * Draft legal texts (French). They are starting points written for a Moroccan
 * directory under law 09-08 and MUST be reviewed by a lawyer before launch.
 * Bracketed values ([...]) are to be completed. Admins edit them afterwards
 * in Filament → Pages; the seeder never overwrites an existing page.
 */

$todo = '<p><strong>Projet à faire valider par un juriste avant la mise en ligne.</strong></p>';

return [
    'cgu' => $todo.<<<'HTML'
<h2>1. Objet</h2>
<p>Les présentes conditions encadrent l’utilisation de l’annuaire des services de la ville, édité par [Raison sociale] (ci-après « nous »). En utilisant le site, vous les acceptez.</p>
<h2>2. Compte</h2>
<p>La consultation est libre. Publier un avis, revendiquer ou ajouter un établissement nécessite un compte vérifié par un code envoyé sur votre mobile. Vous êtes responsable de l’usage de votre compte. Un compte par personne.</p>
<h2>3. Contenus publiés par les utilisateurs</h2>
<p>Les avis, réponses et photos doivent respecter les <a href="regles-des-avis">règles des avis</a>. Vous nous accordez une licence gratuite et non exclusive pour les afficher sur le site pendant toute la durée de leur publication. Nous pouvons masquer ou supprimer tout contenu contraire aux règles ou à la loi.</p>
<h2>4. Établissements</h2>
<p>Les fiches sont informatives. Les professionnels peuvent revendiquer leur fiche après vérification. Le classement naturel ne s’achète pas : les formules payantes et les emplacements sponsorisés (toujours signalés « Sponsorisé ») n’influencent ni les notes ni l’ordre des résultats naturels.</p>
<h2>5. Offres payantes</h2>
<p>Les formules et emplacements sont activés après validation du paiement. Une facture est émise pour chaque paiement validé. [Conditions de remboursement à préciser.]</p>
<h2>6. Responsabilité</h2>
<p>Nous faisons nos meilleurs efforts pour l’exactitude des informations (horaires, pharmacies de garde…) sans pouvoir la garantir. En cas d’urgence, appelez les services d’urgence (15, 19, 150).</p>
<h2>7. Signalement</h2>
<p>Tout contenu illicite peut être signalé via le bouton « Signaler » ou à [adresse e-mail de contact].</p>
<h2>8. Droit applicable</h2>
<p>Droit marocain. Tribunaux compétents : [ville].</p>
HTML,

    'confidentialite' => $todo.<<<'HTML'
<h2>Responsable du traitement</h2>
<p>[Raison sociale], [adresse], [e-mail]. Traitement déclaré auprès de la CNDP sous le n° [numéro] (loi 09-08).</p>
<h2>Données collectées</h2>
<ul>
<li>Compte : numéro de mobile, nom affiché, e-mail (connexion Google ou e-mail), langue.</li>
<li>Contributions : avis, notes, photos, réponses, signalements, suggestions de modification.</li>
<li>Professionnels : justificatifs de revendication, informations de facturation (raison sociale, ICE, adresse), reçus de paiement.</li>
<li>Statistiques : clics « appeler », « WhatsApp », « itinéraire » sans adresse IP (empreinte anonyme renouvelée chaque jour).</li>
<li>Position : uniquement si vous l’autorisez, arrondie à environ 110 m et conservée dans votre session, jamais dans l’URL.</li>
</ul>
<h2>Finalités</h2>
<p>Fonctionnement de l’annuaire, publication et modération des avis, lutte contre les abus, facturation des offres payantes, statistiques pour les professionnels.</p>
<h2>Durées de conservation</h2>
<ul>
<li>Codes de connexion : quelques minutes, supprimés chaque nuit.</li>
<li>Événements statistiques bruts : 13 mois ; seuls les totaux journaliers sont gardés ensuite.</li>
<li>Compte et contributions : jusqu’à la suppression du compte.</li>
<li>Factures : 10 ans (obligation comptable).</li>
<li>Copies de vos données : 7 jours.</li>
</ul>
<h2>Destinataires</h2>
<p>Notre équipe de modération et nos prestataires techniques (hébergement, envoi des SMS/WhatsApp, e-mail) : [liste et pays d’hébergement].</p>
<h2>Vos droits</h2>
<p>Vous disposez d’un droit d’accès, de rectification, d’opposition et de suppression. Depuis la page <strong>Mes données</strong> de votre compte, vous pouvez télécharger une copie de vos données et supprimer votre compte. Lors de la suppression, vos avis restent signés « Utilisateur supprimé », sauf si vous demandez leur suppression. Pour toute autre demande : [e-mail]. Vous pouvez saisir la CNDP (www.cndp.ma).</p>
HTML,

    'regles-des-avis' => $todo.<<<'HTML'
<h2>Un avis utile</h2>
<ul>
<li>Il raconte une expérience réelle et personnelle avec l’établissement, datant de moins de [12] mois.</li>
<li>Il est respectueux : pas d’insultes, de menaces, de propos discriminatoires ni d’informations personnelles (numéros, adresses, noms de salariés).</li>
<li>Il ne fait pas de publicité et ne contient pas de liens.</li>
<li>Il n’est pas écrit par le propriétaire, un salarié ou un concurrent de l’établissement, ni rémunéré.</li>
</ul>
<h2>Modération</h2>
<p>Certains avis sont vérifiés avant publication (nouveaux comptes, mots signalés, nombre d’avis inhabituel). Un avis peut être masqué s’il ne respecte pas ces règles ; son auteur en est informé avec le motif.</p>
<h2>Ce que nous ne faisons jamais</h2>
<p>Un établissement ne peut pas payer pour supprimer un avis négatif, ni pour améliorer sa note. Les professionnels peuvent répondre publiquement à chaque avis et signaler un avis contraire aux règles.</p>
HTML,

    'cookies' => $todo.<<<'HTML'
<h2>Cookies nécessaires (toujours actifs)</h2>
<ul>
<li><strong>session</strong> : maintient votre connexion (durée de la session).</li>
<li><strong>XSRF-TOKEN</strong> : protection contre les envois de formulaires frauduleux.</li>
<li><strong>locale</strong> : mémorise votre langue.</li>
<li><strong>cookie_consent</strong> : mémorise votre choix sur les cookies (6 mois).</li>
</ul>
<h2>Cookies soumis à votre accord</h2>
<p>Aucun à ce jour. Si nous ajoutons une mesure d’audience, elle ne sera activée qu’après votre accord. Vous pouvez changer d’avis à tout moment via le lien « Gérer les cookies » en bas de page.</p>
<h2>Cartes</h2>
<p>Les fonds de carte sont chargés depuis [fournisseur de tuiles], qui reçoit votre adresse IP pour afficher les images.</p>
HTML,

    'mentions-legales' => $todo.<<<'HTML'
<h2>Éditeur</h2>
<p>[Raison sociale], [forme juridique] au capital de [montant] DH<br>Siège : [adresse]<br>RC : [numéro] · ICE : [numéro] · IF : [numéro] · Patente : [numéro]<br>Directeur de la publication : [nom]<br>Contact : [e-mail], [téléphone]</p>
<h2>Hébergement</h2>
<p>[Hébergeur], [adresse], [pays].</p>
<h2>Données personnelles</h2>
<p>Déclaration CNDP n° [numéro]. Voir la politique de confidentialité.</p>
HTML,

    'a-propos' => <<<'HTML'
<p>Un annuaire local pour trouver rapidement un artisan, un mécanicien, une pharmacie de garde ou un commerce, avec les avis des habitants.</p>
<p>Les résultats naturels sont classés selon la pertinence, la distance, les avis et l’activité de la fiche. Les emplacements sponsorisés sont toujours signalés et ne changent jamais les notes.</p>
HTML,

    'contact' => <<<'HTML'
<p>Une question, une erreur sur une fiche, un contenu à signaler ? Écrivez-nous à [e-mail de contact].</p>
<p>Pour corriger une fiche, utilisez le lien « Suggérer une modification » sur la page de l’établissement. Les professionnels peuvent revendiquer leur fiche gratuitement.</p>
HTML,
];
