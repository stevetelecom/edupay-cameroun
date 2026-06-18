@php
    $pageTitle = $pageTitle ?? 'Mon espace';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $pageTitle }}</title>
</head>
<body>
    <h1>{{ $pageTitle }}</h1>
    <p>Vue temporaire — à remplacer par le design final (maquette écran 7).</p>

    @if(isset($apprenants) && $apprenants->count())
        <ul>
            @foreach($apprenants as $apprenant)
                <li>{{ $apprenant->nom }} {{ $apprenant->prenom }} — {{ $apprenant->classe }}</li>
            @endforeach
        </ul>
    @else
        <p>Aucun enfant rattaché pour le moment.</p>
    @endif
</body>
</html>
