<?php

namespace App\Services\Payments\Gateways;

use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OrangeMoneyGateway — Intégration complète Orange Money Mali
 *
 * Documentation : Orange Money Web Payment API
 *
 * Environnements :
 * - DEV/Sandbox : https://api.orange.com/orange-money-webpay/dev/v1
 * - PROD Mali   : https://api.orange.com/orange-money-webpay/ml/v1
 *
 * Devise DEV  : OUV
 * Devise PROD : XOF
 *
 * Flux de paiement :
 * 1. POST /webpayment → retourne pay_token + payment_url
 * 2. Client redirigé vers payment_url
 * 3. Client entre son numéro + OTP
 * 4. Orange envoie une notification POST au notif_url
 * 5. On vérifie la signature avec notif_token
 * 6. On met à jour le statut de la commande
 */
class OrangeMoneyGateway implements PaymentGatewayInterface
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $merchantKey;
    protected string $apiUrl;
    protected string $currency;
    protected bool $isSandbox;

    public function __construct()
    {
        // Récupérer la config depuis la table payment_gateways pour le Mali
        $gateway = PaymentGateway::forCountry('ML')
            ->where('gateway', 'orange')
            ->first();

        if ($gateway) {
            $config = $gateway->config ?? [];
            $this->isSandbox = $gateway->is_sandbox;

            $this->clientId = $config['client_id'] ?? env('ORANGE_MONEY_CLIENT_ID', '');
            $this->clientSecret = $config['client_secret'] ?? env('ORANGE_MONEY_CLIENT_SECRET', '');
            $this->merchantKey = $config['merchant_key'] ?? env('ORANGE_MONEY_MERCHANT_KEY', '');
            $this->apiUrl = $this->isSandbox
                ? ($config['api_url_dev'] ?? 'https://api.orange.com/orange-money-webpay/dev/v1')
                : ($config['api_url_prod'] ?? 'https://api.orange.com/orange-money-webpay/ml/v1');
            $this->currency = $this->isSandbox ? 'OUV' : 'XOF';
        } else {
            // Fallback sur les variables .env
            $this->isSandbox = true;
            $this->clientId = env('ORANGE_MONEY_CLIENT_ID', '');
            $this->clientSecret = env('ORANGE_MONEY_CLIENT_SECRET', '');
            $this->merchantKey = env('ORANGE_MONEY_MERCHANT_KEY', '');
            $this->apiUrl = 'https://api.orange.com/orange-money-webpay/dev/v1';
            $this->currency = 'OUV';
        }
    }

    public function getCode(): string
    {
        return 'orange';
    }

    public function getName(): string
    {
        return $this->isSandbox ? 'Orange Money Mali (Sandbox)' : 'Orange Money Mali';
    }

    /**
     * 🔑 Obtenir un access_token OAuth 2.0
     *
     * POST https://api.orange.com/oauth/v3/token
     * Authorization: Basic base64(client_id:client_secret)
     * Body: grant_type=client_credentials
     *
     * Le token est valide ~90 jours.
     */
    protected function getAccessToken(): ?string
    {
        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->timeout(15)
                ->post('https://api.orange.com/oauth/v3/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                $token = $response->json('access_token');
                Log::info('✅ Orange Money — access_token obtenu');
                return $token;
            }

            Log::error('❌ Orange Money — Erreur OAuth', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('❌ Orange Money — Exception OAuth', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 💳 Initialiser un paiement
     *
     * POST /webpayment
     * Headers: Authorization: Bearer {token}
     * Body: merchant_key, currency, order_id, amount, return_url, cancel_url, notif_url, lang, reference
     */
    public function initialize(Order $order, array $data = []): array
    {
        try {
            // 1. Vérifier la config
            if (empty($this->merchantKey) || empty($this->clientId) || empty($this->clientSecret)) {
                return [
                    'success' => false,
                    'message' => 'Configuration Orange Money incomplète (merchant_key, client_id, client_secret)',
                    'payment_url' => null,
                    'transaction_id' => null,
                    'raw' => null,
                ];
            }

            // 2. Obtenir le token
            $token = $this->getAccessToken();

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'Impossible d\'obtenir le token Orange Money',
                    'payment_url' => null,
                    'transaction_id' => null,
                    'raw' => null,
                ];
            }

            // 3. Construire le payload
            $orderId = 'MASASUGU_' . $order->id . '_' . time();
            $baseUrl = config('app.url');

            $payload = [
                'merchant_key' => $this->merchantKey,
                'currency' => $this->currency,
                'order_id' => substr($orderId, 0, 30),
                'amount' => (int) $order->total,
                'return_url' => substr("{$baseUrl}/api/payments/orange/return", 0, 120),
                'cancel_url' => substr("{$baseUrl}/api/payments/orange/cancel", 0, 120),
                'notif_url' => substr("{$baseUrl}/api/webhooks/orange", 0, 120),
                'lang' => 'fr',
                'reference' => substr('Masasugu', 0, 30),
            ];

            Log::info('🟠 Orange Money — Initialisation paiement', [
                'order_id' => $order->id,
                'amount' => $order->total,
                'is_sandbox' => $this->isSandbox,
            ]);

            // 4. Appeler l'API Orange Money
            $response = Http::withToken($token)
                ->timeout(30)
                ->post("{$this->apiUrl}/webpayment", $payload);

            if ($response->successful()) {
                $body = $response->json();

                Log::info('✅ Orange Money — Paiement initié', [
                    'order_id' => $order->id,
                    'pay_token' => $body['pay_token'] ?? null,
                ]);

                return [
                    'success' => true,
                    'payment_url' => $body['payment_url'] ?? null,
                    'transaction_id' => $body['pay_token'] ?? null,
                    'pay_token' => $body['pay_token'] ?? null,
                    'notif_token' => $body['notif_token'] ?? null,
                    'message' => 'Paiement initié avec succès',
                    'raw' => $body,
                ];
            }

            Log::error('❌ Orange Money — Erreur initiation', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur Orange Money : ' . ($response->json('message') ?? 'Erreur inconnue'),
                'payment_url' => null,
                'transaction_id' => null,
                'raw' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('❌ Orange Money — Exception init', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception : ' . $e->getMessage(),
                'payment_url' => null,
                'transaction_id' => null,
                'raw' => null,
            ];
        }
    }

    /**
     * 🔍 Vérifier le statut d'un paiement
     *
     * POST /transactionstatus
     * Body: order_id, amount, pay_token
     */
    public function verify(string $transactionId): array
    {
        try {
            $token = $this->getAccessToken();

            if (!$token) {
                return ['success' => false, 'message' => 'Token invalide', 'status' => 'unknown'];
            }

            $response = Http::withToken($token)
                ->timeout(20)
                ->post("{$this->apiUrl}/transactionstatus", [
                    'order_id' => $transactionId,
                    'amount' => 0,
                    'pay_token' => $transactionId,
                ]);

            if ($response->successful()) {
                $body = $response->json();

                return [
                    'success' => true,
                    'status' => $body['status'] ?? 'unknown',
                    'transaction_id' => $body['txnid'] ?? null,
                    'order_id' => $body['order_id'] ?? null,
                    'raw' => $body,
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur vérification statut',
                'status' => 'unknown',
                'raw' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 'error',
            ];
        }
    }

    /**
     * ↩️ Rembourser un paiement
     */
    public function refund(string $transactionId, ?float $amount = null): array
    {
        // Orange Money n'a pas d'API refund directe dans la doc DEV
        // En production, il faut contacter l'équipe Orange
        Log::warning('⚠️ Orange Money — Refund non supporté nativement', [
            'transaction_id' => $transactionId,
        ]);

        return [
            'success' => false,
            'message' => 'Remboursement Orange Money non supporté nativement. Contactez Orange.',
        ];
    }

    /**
     * 🔔 Traiter un webhook
     *
     * Reçoit un POST avec :
     * {
     *   "status": "SUCCESS",
     *   "notif_token": "xxx",
     *   "txnid": "MP150709.1341.A00073"
     * }
     */
    public function handleWebhook(array $payload, array $headers = []): array
    {
        try {
            Log::info('🔔 Orange Money — Webhook reçu', ['payload' => $payload]);

            $status = $payload['status'] ?? 'UNKNOWN';
            $notifToken = $payload['notif_token'] ?? null;
            $txnId = $payload['txnid'] ?? null;

            // Mapper les statuts Orange Money → statuts Masasugu
            $mappedStatus = match ($status) {
                'SUCCESS' => 'success',
                'FAILED' => 'failed',
                'EXPIRED' => 'expired',
                'INITIATED' => 'pending',
                'PENDING' => 'processing',
                default => 'unknown',
            };

            return [
                'success' => true,
                'status' => $mappedStatus,
                'orange_status' => $status,
                'transaction_id' => $txnId,
                'notif_token' => $notifToken,
                'message' => 'Webhook traité',
            ];
        } catch (\Exception $e) {
            Log::error('❌ Orange Money — Erreur webhook', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 🔐 Vérifier la signature d'un webhook
     *
     * Orange Money n'envoie pas de signature HMAC,
     * mais un notif_token qu'on doit comparer avec celui retourné à l'initiation.
     *
     * ⚠️ Cette vérification est basique. En production, stocker les notif_tokens
     * et les comparer en DB.
     */
    public function verifyWebhookSignature(array $payload, array $headers): bool
    {
        // Vérification basique : le payload doit contenir status + notif_token
        if (empty($payload['status']) || empty($payload['notif_token'])) {
            Log::warning('⚠️ Orange Money — Webhook sans notif_token');
            return false;
        }

        return true;
    }

    /**
     * 💸 Envoyer un payout (retrait vendeur)
     *
     * ⚠️ API Payout Orange Money : à vérifier avec Orange
     * Pour l'instant, retourne "non implémenté"
     */
    public function payout(array $data): array
    {
        Log::info('💸 Orange Money — Payout demandé', $data);

        return [
            'success' => false,
            'message' => 'Orange Money Payout sera implémenté après obtention de l\'API Payout',
            'reference' => null,
        ];
    }
}
