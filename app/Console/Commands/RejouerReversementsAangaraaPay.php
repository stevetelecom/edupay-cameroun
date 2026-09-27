<?php

namespace App\Console\Commands;

use App\Jobs\ReverserEtablissementJob;
use App\Models\Commission;
use App\Services\AangaraaPayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Rejoue les reversements d'établissement restés en attente.
 *
 * Tant que cette commande n'existait pas, une commission bloquée en 'calculee'
 * restait ainsi DEFINITIVEMENT : aucun moyen de la renvoyer sans passer par un
 * tinker artisan manuel. C'est la principale porte de sortie du job.
 */
class RejouerReversementsAangaraaPay extends Command
{
    protected $signature = 'aangaraa:reversements:rejouer
        {--id=* : Rejouer uniquement ces commissions (sinon toutes les eligibles)}
        {--inclure-a-verifier : Inclure aussi les commissions « a verifier » (RISQUE DE DOUBLE PAIEMENT, a utiliser apres verification manuelle AangaraaPay)}
        {--retablir-echec : Repartir de zéro pour les commissions en « echec » (nouvel essai complet)}
        {--dry-run : Afficher ce qui serait rejoue sans rien envoyer}';

    protected $description = 'Relance les reversements AangaraaPay non effectues (etats calculee / a_verifier / echec)';

    public function handle(AangaraaPayService $aangaraa): int
    {
        $this->verifierNotifyUrl($aangaraa);

        $requete = Commission::query()->with(['etablissement', 'paiement']);

        if ($ids = $this->option('id')) {
            $requete->whereIn('id', $ids);
        } elseif ($this->option('retablir-echec')) {
            $requete->whereIn('statut', [Commission::STATUT_CALCULEE, Commission::STATUT_ECHEC]);
        } elseif ($this->option('inclure-a-verifier')) {
            $requete->whereIn('statut', [Commission::STATUT_CALCULEE, Commission::STATUT_A_VERIFIER]);
        } else {
            // Par defaut on ne touche JAMAIS aux commissions « a verifier » ni
            // « echec » : « a verifier » signifie justement que l'argent est
            // peut-etre deja parti, un rejeu automatique pourrait payer deux fois.
            $requete->where('statut', Commission::STATUT_CALCULEE);
        }

        $commissions = $requete->orderBy('id')->get();

        if ($commissions->isEmpty()) {
            $this->info('Aucun reversement a rejouer.');

            return self::SUCCESS;
        }

        $totalBloque = $commissions->sum('montant_net_etablissement');

        $this->line(sprintf(
            '%d commission(s), %s FCFA a reverser.',
            $commissions->count(),
            number_format($totalBloque, 0, ',', ' ')
        ));

        if ($this->option('inclure-a-verifier')) {
            $this->warn('ATTENTION : --inclure-a-verifier peut PAYER DEUX FOIS si l\'argent est deja parti.');
            $this->warn('Verifiez l\'historique AangaraaPay pour chaque reference avant de continuer.');
        }

        foreach ($commissions as $commission) {
            $etablissement = $commission->etablissement?->nom ?? '?';
            $numero = $commission->etablissement?->numero_momo_reversement ?? 'AUCUN NUMERO';

            $this->line(sprintf(
                '  #%d  %s  %s FCFA  ->  %s (%s)  [%s]',
                $commission->id,
                $etablissement,
                number_format((int) $commission->montant_net_etablissement, 0, ',', ' '),
                $numero,
                $commission->etablissement?->operateur_momo_reversement ?? 'operateur inconnu',
                $commission->statut
            ));

            if ($this->option('dry-run')) {
                continue;
            }

            ReverserEtablissementJob::dispatch($commission->id);
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run : aucun envoi effectue.');

            return self::SUCCESS;
        }

        $this->info(sprintf('%d reversement(s) mis en file.', $commissions->count()));
        $this->line('Ne forget pas le worker : queue:work --stop-when-empty');

        Log::info('Rejeu des reversements AangaraaPay', [
            'nombre' => $commissions->count(),
            'montant_total' => $totalBloque,
            'inclure_a_verifier' => (bool) $this->option('inclure-a-verifier'),
        ]);

        return self::SUCCESS;
    }

    /** Refuse de lancer un rejeu si AangaraaPay ne peut pas rappeler l'API. */
    private function verifierNotifyUrl(AangaraaPayService $aangaraa): void
    {
        $controle = $aangaraa->verifierNotifyUrl(config('services.aangaraa.notify_url'));

        if (! $controle['ok']) {
            $this->error('AANGARAA_NOTIFY_URL : ' . $controle['raison']);
            $this->error('Corrigez .env avant de rejouer des reversements, sinon les retards de');
            $this->error('confirmation seront de plus en plus longs a detecter.');
        }
    }
}
