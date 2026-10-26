<?php

namespace App\Services\Payments\Gateways;

use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayDunyaGateway implements PaymentGatewayInterface
{
    protected $masterKey;
    protected $privateKey;
    protected $token;
    protected $baseUrl;

    public function __construct()
    {
        $this->masterKey = config('services.paydunya.master_key');
        $this->privateKey = config('services.paydunya.private_key');
        $this->token = config('services.paydunya.token');

        $mode = config('services.paydunya.mode');
        $this->baseUrl = $mode === 'test'
            ? 'https://app.paydunya.com/sandbox-api/v1'
            : 'https://app.paydunya.com/api/v1';
    }

    /**
     * Initialiser le paiement (appelé par le PaymentManager)
     */
    public function initialize(Order $order, array $data = []): array
    {
        // 1. Déterminer les canaux par défaut selon le pays de la commande
        $country = strtoupper($order->country ?? 'ML');
        $defaultChannels = [];

        switch ($country) {
            case 'ML': // Mali
                $defaultChannels = ['orange-money-mali', 'moov-ml'];
                break;
            case 'BF': // Burkina Faso
                $defaultChannels = ['orange-money-burkina', 'moov-burkina'];
                break;
            case 'CI': // Côte d'Ivoire
                $defaultChannels = ['orange-money-ci', 'mtn-ci', 'moov-ci', 'wave-ci'];
                break;
            case 'SN': // Sénégal
                $defaultChannels = ['orange-money-senegal', 'wave-senegal', 'free-money-senegal'];
                break;
            case 'BJ': // Bénin
                $defaultChannels = ['mtn-benin', 'moov-benin', 'celtiis-cash'];
                break;
            case 'TG': // Togo
                $defaultChannels = ['t-money-togo', 'moov-togo'];
                break;
            default:
                // Si le pays n'est pas géré par PayDunya (ex: Niger)
                throw new PaymentException("PayDunya n'est pas disponible pour le pays : {$country}");
        }

        // 2. Si l'utilisateur a choisi un canal spécifique (depuis Flutter), on l'utilise
        $channels = !empty($data['channel']) ? [$data['channel']] : $defaultChannels;

        // 3. Construire le payload
        $payload = [
            'invoice' => [
                'total_amount' => $order->total,
                'description' => 'Commande Masasugu #' . $order->id,
            ],
            'store' => [
                'name' => 'Masasugu',
                'website_url' => 'https://masasugu.com',
            ],
            'actions' => [
                'callback_url' => 'https://api.masasugu.com/api/payments/webhook/paydunya',
                'return_url' => 'https://masasugu.com/checkout/success',
                'cancel_url' => 'https://masasugu.com/checkout/cancel',
            ],
            'custom_data' => [
                'order_id' => $order->id,
                'transaction_id' => $data['transaction_id'] ?? null,
            ],
            'channels' => $channels,
        ];

        try {
            $response = Http::withHeaders([
                'PAYDUNYA-MASTER-KEY' => $this->masterKey,
                'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
                'PAYDUNYA-TOKEN' => $this->token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/checkout-invoice/create', $payload);

            $data = $response->json();

            if (isset($data['response_code']) && $data['response_code'] === '00') {
                return [
                    'success' => true,
                    'payment_url' => $data['response_text'],
                    'pay_token' => $data['token'],
                    'notif_token' => $data['token'],
                    'raw' => $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur PayDunya: ' . ($data['response_text'] ?? 'Inconnue'),
                'raw' => $data
            ];

        } catch (\Exception $e) {
            Log::error('❌ PayDunya initialize error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'raw' => null
            ];
        }
    }

    /**
     * Vérifier la signature du Webhook (appelé par le PaymentManager)
     */
    public function verifyWebhookSignature(array $payload, array $headers = []): bool
    {
        // PayDunya signe avec le hash SHA-512 de votre Master Key
        $expectedHash = hash('sha512', $this->masterKey);

        return isset($payload['hash']) && $payload['hash'] === $expectedHash;
    }

    /**
     * Traiter le Webhook (appelé par le PaymentManager)
     */
    public function handleWebhook(array $payload, array $headers = []): array
    {
        $orderId = $payload['custom_data']['order_id'] ?? null;
        $status = $payload['status'] ?? 'unknown';

        return [
            'order_id' => $orderId,
            'status' => $status,
            'transaction_id' => $payload['invoice_token'] ?? null,
            'raw' => $payload
        ];
    }

    // Méthodes requises par l'interface
    public function verify(string $transactionId): array { return ['success' => true]; }
    public function refund(string $transactionId, ?float $amount = null): array { return ['success' => true]; }
    public function payout(array $data): array { return ['success' => true]; }

    /**
     * Retourne le code unique du gateway
     */
    public function getCode(): string
    {
        return 'paydunya';
    }

    /**
     * Retourne le nom lisible du gateway
     */
    public function getName(): string
    {
        return 'PayDunya';
    }

        /**
     * 💸 Envoyer de l'argent à un vendeur (Payout)
     *
     * @param array $data [
     *     'amount' => 5000,
     *     'phone' => '7701901164',
     *     'channel' => 'wave-ci', // ou 'orange-money-ci', etc.
     *     'name' => 'SANGARE Tiefing',
     *     'payout_id' => 'PAYOUT-123'
     * ]
     */
    public function disburse(array $data): array
    {
        $channel = $data['channel'] ?? null;
        if (!$channel) {
            return ['success' => false, 'message' => 'Canal de retrait manquant'];
        }

        // Map des canaux PayDunya Disburse
        $channelMap = [
            // Mali
            'orange_money_ml' => 'orange-money-burkina', // (à adapter selon PayDunya)
            'moov_money_ml' => 'moov-burkina',
            // Côte d'Ivoire
            'orange_money_ci' => 'orange-money-ci',
            'wave_ci' => 'wave-ci',
            'mtn_ci' => 'mtn-ci',
            'moov_ci' => 'moov-ci',
            // Burkina Faso
            'orange_money_bf' => 'orange-money-burkina',
            'moov_money_bf' => 'moov-burkina',
            // Sénégal
            'orange_money_sn' => 'orange-money-senegal',
            'wave_sn' => 'wave-senegal',
            'free_money_sn' => 'free-money-senegal',
            // Bénin
            'mtn_bj' => 'mtn-benin',
            'moov_bj' => 'moov-benin',
            'celtiis_bj' => 'celtiis-cash',
            // Togo
            't_money_tg' => 't-money-togo',
            'moov_tg' => 'moov-togo',
        ];

        $paydunyaChannel = $channelMap[$channel] ?? $channel;

        $payload = [
            'account_alias' => $data['phone'],
            'amount' => $data['amount'],
            'withdraw_mode' => $paydunyaChannel,
            'callback_url' => 'https://api.masasugu.com/api/payments/webhook/paydunya-payout',
            'custom_data' => [
                'payout_id' => $data['payout_id'] ?? null,
                'seller_name' => $data['name'] ?? 'Vendeur',
            ],
        ];

        try {
            $response = Http::withHeaders([
                'PAYDUNYA-MASTER-KEY' => $this->masterKey,
                'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
                'PAYDUNYA-TOKEN' => $this->token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/disburse/submit-invoice', $payload);

            $result = $response->json();

            if (isset($result['response_code']) && $result['response_code'] === '00') {
                Log::info('✅ Disburse PayDunya envoyé', [
                    'payout_id' => $data['payout_id'] ?? null,
                    'channel' => $paydunyaChannel,
                ]);
                return [
                    'success' => true,
                    'reference' => $result['disburse_token'] ?? null,
                    'raw' => $result,
                ];
            }

            Log::error('❌ Erreur Disburse PayDunya', ['response' => $result]);
            return [
                'success' => false,
                'message' => $result['response_text'] ?? 'Erreur inconnue',
                'raw' => $result,
            ];

        } catch (\Exception $e) {
            Log::error('❌ Exception Disburse PayDunya: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
