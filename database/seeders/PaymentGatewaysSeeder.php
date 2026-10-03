<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaysSeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            // ═══════════════════════════════════════════════
            // MALI (ML)
            // ═══════════════════════════════════════════════
            [
                'country_code' => 'ML',
                'currencies' => ['XOF'],
                'gateway' => 'orange',
                'display_name' => 'Orange Money Mali',
                'payment_method' => 'mobile_money',
                'provider' => 'orange',
                'config' => [
                    'merchant_key' => env('ORANGE_MONEY_MERCHANT_KEY', ''),
                    'client_id' => env('ORANGE_MONEY_CLIENT_ID', ''),
                    'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET', ''),
                    'api_url_dev' => 'https://api.orange.com/orange-money-webpay/dev/v1',
                    'api_url_prod' => 'https://api.orange.com/orange-money-webpay/ml/v1',
                    'currency' => 'OUV', // OUV en dev, XOF en prod
                ],
                'priority' => 1,
                'logo_url' => null,
                'description' => 'Payez avec votre compte Orange Money',
                'is_active' => true,
                'is_sandbox' => true,
            ],
            [
                'country_code' => 'ML',
                'currencies' => ['XOF'],
                'gateway' => 'cash_on_delivery',
                'display_name' => 'Paiement à la livraison',
                'payment_method' => 'cash',
                'provider' => null,
                'config' => [],
                'priority' => 10,
                'logo_url' => null,
                'description' => 'Payez en espèces à la réception',
                'is_active' => true,
                'is_sandbox' => false,
            ],

            // ═══════════════════════════════════════════════
            // CÔTE D'IVOIRE (CI) — Préparé pour la Phase 2
            // ═══════════════════════════════════════════════
            [
                'country_code' => 'CI',
                'currencies' => ['XOF'],
                'gateway' => 'cash_on_delivery',
                'display_name' => 'Paiement à la livraison',
                'payment_method' => 'cash',
                'provider' => null,
                'config' => [],
                'priority' => 10,
                'logo_url' => null,
                'description' => 'Payez en espèces à la réception',
                'is_active' => true,
                'is_sandbox' => false,
            ],

            // ═══════════════════════════════════════════════
            // SÉNÉGAL (SN) — Préparé
            // ═══════════════════════════════════════════════
            [
                'country_code' => 'SN',
                'currencies' => ['XOF'],
                'gateway' => 'cash_on_delivery',
                'display_name' => 'Paiement à la livraison',
                'payment_method' => 'cash',
                'provider' => null,
                'config' => [],
                'priority' => 10,
                'logo_url' => null,
                'description' => 'Payez en espèces à la réception',
                'is_active' => true,
                'is_sandbox' => false,
            ],

            // ═══════════════════════════════════════════════
            // FRANCE (FR) — Préparé pour l'international
            // ═══════════════════════════════════════════════
            [
                'country_code' => 'FR',
                'currencies' => ['EUR'],
                'gateway' => 'stripe',
                'display_name' => 'Carte bancaire (Visa/Mastercard)',
                'payment_method' => 'card',
                'provider' => 'stripe',
                'config' => [
                    'public_key' => env('STRIPE_PUBLIC_KEY', ''),
                    'secret_key' => env('STRIPE_SECRET_KEY', ''),
                ],
                'priority' => 1,
                'logo_url' => null,
                'description' => 'Payez par carte bancaire',
                'is_active' => false, // ⚠️ Inactif jusqu'à intégration
                'is_sandbox' => true,
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                [
                    'country_code' => $gateway['country_code'],
                    'gateway' => $gateway['gateway'],
                    'payment_method' => $gateway['payment_method'],
                ],
                $gateway
            );
        }

        $this->command->info('✅ ' . count($gateways) . ' gateways créés.');
    }
}
