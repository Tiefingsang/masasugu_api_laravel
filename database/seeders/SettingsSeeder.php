<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ═══════════════════════════════════════════════
            // COMMISSION MASASUGU
            // ═══════════════════════════════════════════════
            [
                'key' => 'platform_fee_percent',
                'value' => '5',
                'type' => 'decimal',
                'group' => 'commission',
                'label' => 'Commission Masasugu (%)',
                'description' => 'Pourcentage prélevé par Masasugu sur chaque vente',
                'is_public' => false,
            ],
            [
                'key' => 'platform_fee_min',
                'value' => '0',
                'type' => 'decimal',
                'group' => 'commission',
                'label' => 'Commission minimum (FCFA)',
                'description' => 'Montant minimum de commission par commande',
                'is_public' => false,
            ],
            [
                'key' => 'gateway_fee_percent',
                'value' => '2',
                'type' => 'decimal',
                'group' => 'commission',
                'label' => 'Frais prestataire (%)',
                'description' => 'Pourcentage estimé des frais du prestataire de paiement',
                'is_public' => false,
            ],

            // ═══════════════════════════════════════════════
            // PARAMÈTRES GÉNÉRAUX
            // ═══════════════════════════════════════════════
            [
                'key' => 'app_name',
                'value' => 'Masasugu',
                'type' => 'string',
                'group' => 'general',
                'label' => "Nom de l'application",
                'description' => 'Nom affiché aux utilisateurs',
                'is_public' => true,
            ],
            [
                'key' => 'default_currency',
                'value' => 'XOF',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Devise par défaut',
                'description' => 'Devise utilisée par défaut',
                'is_public' => true,
            ],
            [
                'key' => 'default_country',
                'value' => 'ML',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Pays par défaut',
                'description' => 'Code ISO du pays par défaut',
                'is_public' => true,
            ],

            // ═══════════════════════════════════════════════
            // PARAMÈTRES DE WALLET
            // ═══════════════════════════════════════════════
            [
                'key' => 'payout_min_amount',
                'value' => '5000',
                'type' => 'decimal',
                'group' => 'payout',
                'label' => 'Montant minimum de retrait (FCFA)',
                'description' => 'Montant minimum qu\'un vendeur peut retirer',
                'is_public' => true,
            ],
            [
                'key' => 'payout_delay_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'payout',
                'label' => 'Délai de disponibilité (jours)',
                'description' => 'Nombre de jours après livraison avant que les fonds soient disponibles',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('✅ ' . count($settings) . ' settings créés/mis à jour.');
    }
}
