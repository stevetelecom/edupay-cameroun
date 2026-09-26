<?php

namespace App\Http\Resources;

use App\Support\AnneeScolaire;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Audit E : le back-office mobile affiche « Reste dû » a partir de
        // total_du / total_paye / solde_du. Ces trois champs n'etaient pas
        // exposes, l'ecran affichait donc 0. Les soldes sont calcules sur
        // l'annee scolaire ACTIVE de l'etablissement (meme reference que les
        // KPI et les impayes, cf. App\Support\AnneeScolaire).
        $anneeScolaire = AnneeScolaire::active($this->etablissement);
        $soldes        = $this->soldes($anneeScolaire);

        return [
            'id'                      => $this->id,
            'nom'                     => $this->nom,
            'prenom'                  => $this->prenom,
            'nom_complet'             => trim($this->prenom . ' ' . $this->nom),
            'classe'                  => $this->classe,
            'matricule'               => $this->matricule,
            'date_naissance'          => $this->date_naissance
                ? ($this->date_naissance instanceof \DateTimeInterface
                    ? $this->date_naissance->format('Y-m-d')
                    : (string) substr($this->date_naissance, 0, 10))
                : null,
            'sexe'                    => $this->sexe,
            // Audit F : le mobile.base sa decision d'afficher les boutons
            // « Valider » / « Rejeter » sur `statut` (qui n'existait pas).
            'statut'                  => $this->statutRattachement(),
            'statut_paiement'         => $this->statut_paiement,
            'total_du'                => $soldes['total_du'],
            'total_paye'              => $soldes['total_paye'],
            'solde_du'                => $soldes['solde_du'],
            'annee_scolaire'          => $anneeScolaire,
            'valide_par_etablissement' => (bool) $this->valide_par_etablissement,
            'lien'                    => $this->pivot?->lien,
            'etablissement'           => new EtablissementResource($this->whenLoaded('etablissement')),
            'frais'                   => FraisResource::collection($this->whenLoaded('frais')),
        ];
    }
}
