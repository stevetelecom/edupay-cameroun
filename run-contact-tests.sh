#!/bin/bash

# 🧪 Script de test pour le formulaire de contact avec logs Laravel
# Exécute les tests et affiche les logs en direct

set -e

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║   📧 TEST FORMULAIRE DE CONTACT - Avec logs Laravel           ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# 1️⃣ Nettoyer les vieux logs
echo "📋 PRÉPARATION..."
echo "   ✓ Suppression des vieux logs..."
rm -f storage/logs/laravel.log
rm -f storage/logs/laravel-*.log

echo ""
echo "🧪 EXÉCUTION DES TESTS..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 2️⃣ Exécuter les tests avec output détaillé
php artisan test tests/Feature/ContactFormTest.php \
    --testsuite=Feature \
    --verbose

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📊 RÉSULTATS DES LOGS..."
echo ""

# 3️⃣ Afficher les logs générés
if [ -f storage/logs/laravel.log ]; then
    echo "✅ LOGS GÉNÉRÉS (dernières 60 lignes):"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    tail -60 storage/logs/laravel.log
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
else
    echo "⚠️  Aucun fichier de log généré"
fi

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║   ✨ Tests complétés! Vérifiez les logs ci-dessus            ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "💡 CONSEIL: Pour voir les logs en temps réel pendant les tests:"
echo "   Terminal 1: tail -f storage/logs/laravel.log"
echo "   Terminal 2: php artisan test tests/Feature/ContactFormTest.php -v"
