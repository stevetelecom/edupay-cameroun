<?php

namespace App\Jobs;

use App\Mail\AlerteReversementManquantMail;
use App\Models\Commission;
use App\Services\AangaraaPayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Reverse le net à l'établissement via AangaraaPay.
 *
 * Sécurité (E-02 audit) : cet appel HTTP (timeout 30s) était précédemment
 * exécuté À L'INTÉRIEUR de la transaction DB verrouillée de
 * traiterPaiementValide(), ce qui pouvait maintenir un lockForUpdate()
 * sur la ligne paiement jusqu'à ~45s en cas de lenteur AangaraaPay —
 * risque de deadlock et saturation du pool de connexions sous charge
 * (CDC §6.4 exige 500 tx/min). Il est maintenant dispatché en job,
 * après le commit de la transaction, avec retry automatique.
 *
 * ── Ce qui a été corrigé le 27/09/2026 (l'argent pouvait rester bloqué
 *    indefiniment, sans que personne ne le sache) ─────────────────────────
 * 1. L'echec DEFINITIF ne leve aucune exception : sur la derniere tentative le
 *    job sortait normalement, failed() n'etait donc jamais appele et le TODO
 *    « alerter l'admin » n'a jamais existe. La commission restait 'calculee'
 *    indefiniment, sans aucun moyen de la rejouer.
 * 2. Un numero de reversement manquant ou mal forme faisait Log::critical +
 *    return : aucun retry, aucune alerte, argent bloque.
 * 3. AUCUNE protection contre le double virement : si AangaraaPay executait le
 *    transfert puis que la reponse se perdait, le retry renvoyait l'argent une
 *    seconde fois. L'etat 'en_cours' est pose AVANT l'appel : au retry, un
 *    etat 'en_cours' orphelin passe en 'a_verifier' (verification humaine)
 *    au lieu d'etre renvoye a l'aveugle.
 */
class ReverserEtablissementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300]; // 30s, 2min, 5min entre tentatives

    public function __construct(public int $commissionId)
    {
    }

    public function handle(AangaraaPayService $aangaraa): void
    {
        $commission = Commission::with(['etablissement', 'paiement'])->find($this->commissionId);

        if (! $commission || $commission->statut === Commission::STATUT_PRELEVEE) {
            return; // Déjà traité ou supprimé — idempotent
        }

        // Ces états demandent une intervention humaine : on ne rejoue jamais
        // tout seul un virement dont on ignore le sort.
        if (in_array($commission->statut, [Commission::STATUT_A_VERIFIER, Commission::STATUT_ECHEC], true)) {
            Log::warning('ReverserEtablissementJob : etat terminal, aucun nouvel envoi', [
                'commission_id' => $commission->id,
                'statut'        => $commission->statut,
            ]);
            return;
        }

        // ── Double virement : un essai precedent a ete engage mais son sort
        //    n'a jamais ete enregistre (crash, kill -9, redemarrage PHP).
        if ($commission->statut === Commission::STATUT_EN_COURS) {
            $this->mettreEnAttenteDeVerification(
                $commission,
                'Un reversement etait deja engage sans confirmation enregistree : '
                .'il est peut-etre deja parti. Verification manuelle obligatoire avant tout nouvel envoi.'
            );
            return;
        }

        $etablissement = $commission->etablissement;

        if (! $etablissement) {
            $this->mettreEnEchec($commission, 'Etablissement introuvable (supprime ?) : reversement impossible.');
            return;
        }

        $numeroReversement = $etablissement->numero_momo_reversement;

        if (! $numeroReversement) {
            $this->mettreEnEchec(
                $commission,
                'Etablissement sans numero_momo_reversement : renseigner ce numero dans ses parametres '
                .'puis relancer la commande aangaraa:reversements.'
            );
            return;
        }

        // Opérateur : détecté automatiquement depuis le préfixe du numéro
        // (même logique que pour l'encaissement). Le préfixe fait foi sur la
        // valeur saisie en back-office, qui peut être vide ou erronée.
        $detecte = strtolower((string) $aangaraa->detecterOperateur(
            preg_replace('/\D/', '', (string) $numeroReversement)
        ));
        $operateurDetecte = str_contains($detecte, 'orange') ? 'orange'
            : (str_contains($detecte, 'mtn') ? 'mtn' : null);

        $operateurSaisi = $etablissement->operateur_momo_reversement;

        if ($operateurDetecte === null && $operateurSaisi === null) {
            $this->mettreEnEchec(
                $commission,
                'Operateur du numero de reversement indeterminable (' . $numeroReversement . ') : '
                .'verifier le numero dans les parametres de l\'etablissement.'
            );
            return;
        }

        $operateurRevers = $operateurDetecte ?? $operateurSaisi;

        if ($operateurSaisi !== null && $operateurDetecte !== null && $operateurSaisi !== $operateurDetecte) {
            Log::warning('Reversement : operateur saisi != operateur detecte, le prefixe fait foi', [
                'etablissement_id' => $etablissement->id,
                'saisi'            => $operateurSaisi,
                'detecte'          => $operateurDetecte,
            ]);
        }

        // On mémorise la valeur détectée pour que le back-office soit à jour.
        if ($operateurDetecte !== null && $operateurSaisi !== $operateurDetecte) {
            $etablissement->update(['operateur_momo_reversement' => $operateurDetecte]);
        }

        // Garde de sécurité : on ne reverse jamais vers un numéro invalide.
        if (! preg_match('/^6\d{8}$/', preg_replace('/\D/', '', $numeroReversement))) {
            $this->mettreEnEchec($commission, sprintf(
                'Numero de reversement invalide (%s) : le format attendu est 9 chiffres commencant par 6 (6XXXXXXXX).',
                $numeroReversement
            ));
            return;
        }

        $montant = (int) $commission->montant_net_etablissement;

        if ($montant <= 0) {
            $this->mettreEnEchec($commission, 'Montant net nul ou negatif : rien a reverser, verifiez la commission.');
            return;
        }

        // ── On pose 'en_cours' AVANT l'appel HTTP. C'est ce qui rend le double
        //    virement détectable au tour suivant (voir le garde-fou en tête).
        //
        //    La réservation se fait par un UPDATE conditionnel, pas par un
        //    `update()` inconditionnel suivi d'un rechargement : deux workers
        //    (ou le job et la commande `aangaraa:reversements`) pouvaient lire
        //    `calculee` au meme instant, passer chacun le garde-fou de la
        //    ligne 55, et appeler l'API chacun de leur cote. Une seule
        //    reservation reussit, l'autre l'autre concurrent qui attend
        //    `en_cours` sans confirmation et part en verification manuelle.
        $reserve = Commission::whereKey($this->commissionId)
            ->where('statut', Commission::STATUT_CALCULEE)
            ->update([
                'statut'               => Commission::STATUT_EN_COURS,
                'reversement_tente_le' => now(),
            ]);

        // L'UPDATE conditionnel passe par le query builder : l'instance
        // Eloquent en memoire n'a donc toujours pas vu le nouveau statut. Sans
        // ce rafraichissement, le `update(['statut' => 'calculee'])` de
        // `mettreAJourEchecTemporaire()` ne trouvait aucun attribut sale et
        // n'ecrivait rien : un refus net laissait la commission en
        // `en_cours` a jamais, donc bloquee pour tout rejeu.
        $commission->refresh();

        if ($reserve === 0) {
            // L'echec de la reservation a DEUX causes tres differentes, et les
            // confondre enverrait chercher une concurrence inexistante :
            //  1. un autre traitement a bien reserve -> vraie concurrence ;
            //  2. la commission n'etait pas « calculee » mais dans un etat
            //     terminal (« echec »), le job ayant ete dispatche a tort.
            // Le cas 2 est survenu reel le 30/09/2026 via
            // `aangaraa:reversements:rejouer --retablir-echec`, qui annoncait
            // « 1 reversement mis en file » alors que l'UPDATE conditionnel ne
            // pouvait pas aboutir : aucun appel API n'etait emis, et le log
            // designait une concurrence fantome.
            $statutObserve = $commission->statut;
            $estTerminal = in_array($statutObserve, [
                Commission::STATUT_ECHEC,
                Commission::STATUT_A_VERIFIER,
                Commission::STATUT_PRELEVEE,
            ], true);

            $raison = $estTerminal
                ? 'Reversement dans un etat terminal (statut : ' . $statutObserve . ') : '
                    . 'ce job ne reserve que les commissions « calculee ». Il a ete dispatche '
                    . 'sur une ligne non reservable, donc aucun appel AangaraaPay n\'a ete fait. '
                    . 'Repartir de zero demande « aangaraa:reversements:rejouer --retablir-echec ».'
                : 'Un autre traitement a reserve ce reversement entre-temps (statut : '
                    . $statutObserve . ') : aucun nouvel envoi.';

            $this->mettreEnAttenteDeVerification($commission, $raison);

            return;
        }

        // ── Garde-fou metier : aucun reversement sans paiement VALIDE ──
        //
        // Le statut `calculee` de la commission est pose par le webhook quand le
        // paiement passe a `valide`, mais ce statut n'est pas une preuve : une
        // commission reinserive a la main, un rejeu de commande, ou un webhook
        // qui aurait produit la commission avant d'aboutir pourraient tous
        // envoyer de l'argent sur un paiement echeoue, annule ou encore en
        // attente de confirmation du client.
        //
        // `paiements.statut` est la seule source de verite, et elle se verifie
        // seule, independamment du code qui a emis la commission. On relit donc
        // le paiement en base juste avant d'envoyer l'argent.
        $paiement = $commission->paiement()->first();

        if (! $paiement || $paiement->statut !== 'valide') {
            $this->mettreEnAttenteDeVerification(
                $commission,
                'Aucun reversement envoye : le paiement #'
                . ($paiement?->id ?? '?') . ' est au statut « '
                . ($paiement?->statut ?? 'intrant') . ' », pas « valide ».'
            );
            return;
        }

        $resultat = $aangaraa->reverserEtablissement(
            telephone:   $numeroReversement,
            operateur:   $operateurRevers,
            montant:     $montant,
            description: 'Reversement EduPay — paiement #' . $commission->paiement_id
        );

        if ($resultat['succes']) {
            $commission->update([
                'statut'                => Commission::STATUT_PRELEVEE,
                'reference_reversement' => $resultat['reference'],
                'reversed_at'           => now(),
            ]);

            Log::info('Reversement établissement réussi', [
                'commission_id' => $commission->id,
                'reference'     => $resultat['reference'],
                'montant'       => $montant,
            ]);
            return;
        }

        // ── Réponse perdue / 5xx : l'argent est peut-être parti. Ne JAMAIS
        //    renvoyer automatiquement, il faut vérifier.
        if (($resultat['outcome'] ?? 'refuse') === 'indetermine') {
            $this->mettreEnAttenteDeVerification(
                $commission,
                'Reponse AangaraaPay perdue (timeout ou erreur serveur) : le virement est peut-etre deja parti. '
                .'Verification manuelle obligatoire avant tout nouvel envoi. Motif : ' . ($resultat['message'] ?? '?')
            );
            return;
        }

        // ── Refus net (« solde insuffisant », cle invalide...) : rien n'est parti.
        //
        // 30/09/2026 : ce chemin déclenchait jusqu'à 3 nouvel essai. Chaque échec
        // remettait la commission à `calculee` (voir mettreAJourEchecTemporaire),
        // donc la rendait à nouveau réservable : le job suivant de la file
        // repartait, et `aangaraa:reversements:rejouer` en rajoutait un toutes
        // les 10 minutes. Un seul paiement a produit 5 appels AangaraaPay en 1 s.
        //
        // Aucun 4xx ne se résout tout seul : le solde AangaraaPay reste vide, le
        // numéro reste invalide, le montant reste hors limites. Réessayer toutes
        // les 5 minutes n'a jamais rien réglé — et chaque tentative rouvrait une
        // fenêtre de double virement si le paiement était remboursé entre-temps,
        // ce qui est le risque financier réel ici.
        //
        // Le refus devient donc un état TERMINAL. La reprise est une décision
        // humaine, explicite et tracée, après correction de la cause.
        Log::error('Reversement établissement refusé', [
            'commission_id' => $commission->id,
            'message'       => $resultat['message'] ?? null,
            'tentative'     => $this->attempts(),
            'tries'         => $this->tries,
        ]);

        $this->mettreEnEchec($commission, sprintf(
            'Refus AangaraaPay, aucun virement envoyé : %s. '
            .'Corriger la cause puis relancer « aangaraa:reversements:rejouer --retablir-echec ». '
            .'Aucun nouvel essai automatique : un 4xx ne se résout pas seul.',
            $resultat['message'] ?? 'raison inconnue'
        ));
    }

    /**
     * Le job n'leve jamais d'exception, donc failed() n'est pas_appele par
     * Laravel : c'est ici qu'on s'assure que l'echec definitif est visible et
     * notifie, y compris quand le job est abandonne par la queue.
     */
    public function failed(\Throwable $exception): void
    {
        $commission = Commission::find($this->commissionId);

        Log::critical('ReverserEtablissementJob définitivement échoué', [
            'commission_id' => $this->commissionId,
            'erreur'        => $exception->getMessage(),
        ]);

        if ($commission && $commission->statut !== Commission::STATUT_PRELEVEE) {
            $commission->update([
                'statut'               => Commission::STATUT_ECHEC,
                'reversement_erreur'   => mb_substr($exception->getMessage(), 0, 500),
            ]);
        }

        $this->alerter($commission, 'Echec technique du reversement : '.$exception->getMessage());
    }

    /** Échec définitif : la commission sort de la file et l'admin est prévenu. */
    private function mettreEnEchec(Commission $commission, string $raison): void
    {
        $commission->update([
            'statut'             => Commission::STATUT_ECHEC,
            'reversement_erreur' => mb_substr($raison, 0, 500),
        ]);

        Log::critical('Reversement établissement ANNULÉ — action manuelle requise', [
            'commission_id'     => $commission->id,
            'paiement_id'       => $commission->paiement_id,
            'etablissement_id'  => $commission->etablissement_id,
            'montant_bloque'    => (int) $commission->montant_net_etablissement,
            'raison'            => $raison,
        ]);

        $this->alerter($commission, $raison);
    }

    /** Sort inconnue : l'argent est peut-être parti, vérification humaine. */
    private function mettreEnAttenteDeVerification(Commission $commission, string $raison): void
    {
        $commission->update([
            'statut'             => Commission::STATUT_A_VERIFIER,
            'reversement_erreur' => mb_substr($raison, 0, 500),
        ]);

        Log::critical('Reversement établissement À VÉRIFIER — ne pas renvoyer', [
            'commission_id'    => $commission->id,
            'paiement_id'      => $commission->paiement_id,
            'montant'          => (int) $commission->montant_net_etablissement,
            'raison'           => $raison,
        ]);

        $this->alerter($commission, $raison);
    }

    /**
     * Entre deux tentatives l'etat doit redevenir eligible au rejeu : on
     * repasse en 'calculee' en gardant la trace de la tentative.
     */
    private function mettreAJourEchecTemporaire(Commission $commission): void
    {
        $commission->update([
            'statut' => Commission::STATUT_CALCULEE,
            'reversement_tente_le' => now(),
        ]);
    }

    private function alerter(?Commission $commission, string $raison): void
    {
        try {
            Mail::to(config('services.admin_alert_email'))
                ->send(new AlerteReversementManquantMail(
                    commissionId: $commission?->id,
                    etablissement: $commission?->etablissement?->nom,
                    numeroReversement: $commission?->etablissement?->numero_momo_reversement,
                    montant: $commission ? (int) $commission->montant_net_etablissement : null,
                    paiementId: $commission?->paiement_id,
                    raison: $raison,
                ));
        } catch (\Throwable $e) {
            // L'alerte ne doit jamais masquer l'échec métier : on le dit.
            Log::error('Échec envoi alerte reversement manquant : '.$e->getMessage());
        }
    }
}
