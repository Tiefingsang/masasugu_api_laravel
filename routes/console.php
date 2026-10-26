<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // ✅ Ajout de l'import

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ═══════════════════════════════════════════════════════════
// 🕒 TÂCHES PLANIFIÉES (Escrow & Payouts automatiques)
// ═══════════════════════════════════════════════════════════

// 1. Validation automatique des commandes livrées (Escrow)
// Libère l'argent du séquestre vers le solde vendeur après 7 jours
Schedule::command('orders:auto-validate')->daily();

// 2. Retraits automatiques pour les vendeurs
// Envoie l'argent aux vendeurs dont le solde dépasse le minimum
Schedule::command('payouts:auto')->daily();
