<?php

use Illuminate\Support\Facades\Hash;

// 1. Restaurer Carine (etudiant@test.cm) — lève le soft delete
$carine = \App\Models\User::withTrashed()->find(3);
if ($carine) {
    $carine->restore();
    echo "Carine restauree - deleted_at maintenant : " . ($carine->deleted_at ?? "NON") . PHP_EOL;
} else {
    echo "Carine (id 3) introuvable" . PHP_EOL;
}

// 2. Recreer/verifier le directeur pour l'etablissement id 2
$etab = \App\Models\Etablissement::find(2);

$responsable = \App\Models\User::firstOrCreate(
    ['email' => 'directeur.douala@test.cm'],
    [
        'prenom'           => 'Paul',
        'nom'              => 'ATEBA',
        'telephone'        => '677999888',
        'ville'            => 'Douala',
        'password'         => Hash::make('password'),
        'etablissement_id' => $etab->id,
    ]
);

if (! $responsable->hasRole('directeur')) {
    $responsable->assignRole('directeur');
}

echo "Directeur cree/verifie - id:" . $responsable->id . " - email:" . $responsable->email . PHP_EOL;
echo "Roles : " . $responsable->getRoleNames()->implode(', ') . PHP_EOL;
