<?php

return [

    /*
    |---------------------------------------------------------------------------
    | API error messages (audit P)
    |---------------------------------------------------------------------------
    */

    'unauthenticated' => 'Not authenticated. A valid token is required.',
    'forbidden'       => 'Access denied.',
    'not_found'       => 'Resource not found.',
    'validation'      => 'The submitted data is invalid.',
    'throttle'        => 'Too many requests. Please try again shortly.',
    'server_error'    => 'An internal error occurred. Please try again later.',

    'compte_suspendu'           => 'This account is suspended. Contact EduPay support.',
    'compte_suspendu_raison'    => 'This account is suspended: :raison',
    'acces_etablissement'       => 'This account has no access to the school back-office.',
    'etablissement_non_actif'    => 'This school no longer accepts new operations.',

    'reconnexion_requise'        => 'Your password has been changed. Please sign in again on your other devices.',

    'paiement_en_cours'         => 'A payment is already in progress for these fees. Confirm it on your phone or wait 5 minutes.',
    'relance_anti_spam'          => 'A reminder was already sent to these parents in the last :heures hours.',
    'relance_force'              => 'Use force=true to send it anyway.',

    'mode'                      => 'This operation is not allowed in the current mode.',
    'erreur'                    => 'Error',

];
