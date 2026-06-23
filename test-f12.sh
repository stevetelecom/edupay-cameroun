#!/bin/bash
# Test F12 - Notifications SMS/Email

cd /home/whitehack/edupay-cameroun

echo "═══════════════════════════════════════════"
echo "🧪 TEST F12 - Notifications SMS/Email"
echo "═══════════════════════════════════════════"
echo ""

echo "1️⃣ Test Confirmation Paiement (Email + SMS)..."
php artisan tinker << 'EOF'
$paiement = \App\Models\Paiement::find(1);
if ($paiement) {
    echo "Paiement trouvé: {$paiement->reference}\n";
    echo "Statut actuel: {$paiement->statut}\n";
    
    // Simuler le passage de 'en_attente' à 'valide'
    // (normalement, cela triggerait l'événement dans le modèle)
    echo "\n→ Dispatching SendConfirmationPaiement...\n";
    dispatch(new \App\Jobs\SendConfirmationPaiement($paiement));
    echo "✓ Job dispatched! (ira en queue)\n";
} else {
    echo "❌ Aucun paiement trouvé\n";
}
exit();
EOF

echo ""
echo "2️⃣ Test Alerte Impayé (Email + SMS)..."
php artisan tinker << 'EOF'
$apprenant = \App\Models\Apprenant::first();
if ($apprenant) {
    echo "Apprenant trouvé: {$apprenant->nom} {$apprenant->prenom}\n";
    
    echo "\n→ Dispatching SendAlerteImpaye...\n";
    dispatch(new \App\Jobs\SendAlerteImpaye(
        $apprenant,
        "Frais de scolarité",
        50000,
        "23/06/2026"
    ));
    echo "✓ Job dispatched! (ira en queue)\n";
} else {
    echo "❌ Aucun apprenant trouvé\n";
}
exit();
EOF

echo ""
echo "3️⃣ Vérifier les jobs en attente..."
echo "→ Fichier queue par défaut: storage/logs/"
ls -lah storage/logs/ | head -10

echo ""
echo "═══════════════════════════════════════════"
echo "✅ Tests terminés!"
echo "═══════════════════════════════════════════"
echo ""
echo "📝 NOTES:"
echo "• Les jobs sont en 'sync' par défaut (exécution immédiate)"
echo "• Vérifiez les logs: storage/logs/admin-*.log"
echo "• Pour tester les schedulers: php artisan schedule:run"
