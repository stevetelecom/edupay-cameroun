<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Historique des paiements</title></head>
<body>
    <h1>Historique des paiements</h1>
    <p>Vue temporaire — à remplacer par le design final (maquette écran 7).</p>
    <ul>
        @foreach($paiements as $paiement)
            <li>{{ $paiement->reference }} — {{ $paiement->montant }} FCFA — {{ $paiement->statut }}</li>
        @endforeach
    </ul>
    {{ $paiements->links() }}
</body>
</html>
