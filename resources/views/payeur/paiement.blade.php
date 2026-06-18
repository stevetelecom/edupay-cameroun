<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Paiement</title></head>
<body>
    <h1>Paiement — {{ $fraisApprenant->categorieFrais->nom ?? '' }}</h1>
    <p>Vue temporaire — à remplacer par le design final (maquette écran 8).</p>
    <p>Montant restant : {{ $fraisApprenant->montant_total - $fraisApprenant->montant_paye }} FCFA</p>
</body>
</html>
