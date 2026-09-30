<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Erreurs renvoyees par l'API (audit P)
    |---------------------------------------------------------------------------
    | Les messages centraux (401 / 403 / 404 / 422 / 429 / 500) et ceux des
    | middlewares sont ici plutot qu'ecrits en dur dans le code, afin que le
    | mobile puisse les afficher dans la langue du demandeur.
    */

    'unauthenticated' => 'Non authentifié. Un token valide est requis.',
    'forbidden'       => 'Accès non autorisé.',
    'not_found'       => 'Ressource introuvable.',
    'validation'      => 'Les données envoyées sont invalides.',
    'throttle'        => 'Trop de requêtes. Merci de réessayer dans un instant.',
    'server_error'    => 'Une erreur interne est survenue. Réessayez plus tard.',

    'compte_suspendu'           => 'Ce compte est suspendu. Contactez le support EduPay.',
    'compte_suspendu_raison'    => 'Ce compte est suspendu : :raison',
    'acces_etablissement'       => "Ce compte n'a pas accès au back-office établissement.",
    'etablissement_non_actif'    => 'Cet établissement n\'accepte plus de nouvelle opération.',

    'reconnexion_requise'        => 'Votre mot de passe a été modifié. Reconnectez-vous sur vos autres appareils.',

    'paiement_en_cours'         => 'Un paiement est déjà en cours pour ces frais. Confirmez-le sur votre téléphone ou attendez 5 minutes.',
    'relance_anti_spam'          => 'Une relance a déjà été envoyée à ces parents dans les :heures dernières heures.',
    'relance_force'              => 'Utilisez force=true pour la renvoyer quand même.',

    'mode'                      => 'Cette opération n\'est pas autorisée dans le mode courant.',
    'erreur'                    => 'Erreur',

];
