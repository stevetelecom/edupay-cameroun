<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Support\LogMasking;
use Illuminate\View\View;

class LandingController extends Controller
{
    /** Nombre d'établissements par page dans l'annuaire public. */
    private const PAR_PAGE = 12;

    /** Types d'établissement acceptés par le filtre de l'annuaire. */
    private const TYPES = [
        'maternelle'      => 'public.type_maternelle',
        'primaire'        => 'public.type_primaire',
        'college'         => 'public.type_college',
        'lycee_general'   => 'public.type_lycee_general',
        'lycee_technique' => 'public.type_lycee_technique',
        'institut'        => 'public.type_institut',
    ];

    /** Colonnes exposées dans l'annuaire public (celles lues par la vue). */
    private const COLONNES = [
        'id', 'code_etablissement', 'nom', 'type', 'ville', 'logo',
    ];

    public function index(Request $request): View
    {
        $stats = $this->stats();

        $q = trim((string) $request->query('q', ''));

        $type = (string) $request->query('type', '');
        if (! array_key_exists($type, self::TYPES)) {
            $type = '';
        }

        $etablissements = \App\Models\Etablissement::where('statut', 'actif')
            // Filtrage serveur : le filtre JavaScript ne portait que sur les 12
            // premières cartes, donc toute école au-delà était introuvable.
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

                $query->where(function ($sous) use ($like) {
                    $sous->where('nom', 'like', $like)
                        ->orWhere('ville', 'like', $like)
                        ->orWhere('code_etablissement', 'like', $like);
                });
            })
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderBy('nom')
            ->paginate(self::PAR_PAGE, self::COLONNES)
            ->withQueryString();

        $types = self::TYPES;

        return view('public.landing', compact('stats', 'etablissements', 'q', 'type', 'types'));
    }

    /**
     * Compteurs affiches sur les pages publiques. Agrégats volontairement
     * mis en cache : ils sont lus sur chaque page et changent peu.
     */
    private function stats(): array
    {
        return Cache::remember('landing_stats', 600, function () {
            return [
                'nb_etablissements' => \App\Models\Etablissement::where('statut', 'actif')->count(),
                'nb_apprenants'     => \App\Models\Apprenant::where('actif', true)->count(),
                'nb_paiements'      => \App\Models\Paiement::where('statut', 'valide')->count(),
                'montant_total'     => \App\Models\Paiement::where('statut', 'valide')->sum('montant'),
            ];
        });
    }

    public function about(): View
    {
        $stats = $this->stats();

        return view('public.about', compact('stats'));
    }

    public function temoignages(): View
    {
        $stats = $this->stats();

        return view('public.temoignages', compact('stats'));
    }

    public function etablissement(\App\Models\Etablissement $etablissement): View
    {
        $etablissement->load(['categoriesFrais' => function ($q) {
            $q->where('actif', true)->orderBy('nom');
        }]);

        $nbApprenants = \App\Models\Apprenant::where('etablissement_id', $etablissement->id)
            ->where('actif', true)
            ->count();

        return view('public.etablissement', compact('etablissement', 'nbApprenants'));
    }

    public function guide(): View
    {
        return view('public.guide');
    }

    public function confidentialite(): View
    {
        return view('public.confidentialite');
    }

    public function cgu(): View
    {
        return view('public.cgu');
    }

    public function support(): View
    {
        return view('public.support');
    }

    public function tarifs(): View
    {
        return view('public.tarifs', [
            'plans' => \App\Models\Abonnement::PLANS,
        ]);
    }

    public function contact(): View
    {
        return view('public.contact');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // Logging: Message reçu
        Log::info('Nouveau message de contact', [
            'name'           => $data['name'],
            'email'          => LogMasking::email($data['email']),
            'phone'          => LogMasking::telephone($data['phone']),
            'subject'        => $data['subject'],
            'message_length' => strlen($data['message']),
            'timestamp'      => now()->toDateTimeString(),
        ]);

        try {
            $recipientEmail = config('mail.contact_address', config('mail.from.address'));
            
            // Logging: Tentative d'envoi
            Log::info("Envoi de l'email à: {$recipientEmail}", [
                'from' => $data['email'],
                'name' => $data['name'],
            ]);

            Mail::to($recipientEmail)
                ->send(new ContactMessageMail($data));

            // Logging: Succès
            Log::info("Email de contact envoyé avec succès", [
                'from' => $data['email'],
                'to' => $recipientEmail,
            ]);

        } catch (\Throwable $exception) {
            // Logging: Erreur
            Log::error("Erreur lors de l'envoi du message de contact", [
                'email' => $data['email'],
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Impossible d\'envoyer votre message pour le moment. Veuillez réessayer ultérieurement.');
        }

        return redirect()->route('contact')->with('success', 'Votre message a bien été envoyé. Nous reviendrons vers vous rapidement.');
    }
}
