<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\PaymentGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PaymentManager — Orchestrateur des paiements
 *
 * Point d'entrée UNIQUE pour tous les paiements.
 * Route vers le bon gateway selon le pays et le moyen choisi.
 */
class PaymentManager
{
    /**
     * Registry des gateways disponibles
     */
    protected array $gateways = [];

    public function __construct()
    {
        $this->registerGateways();
    }

    /**
     * Enregistrer les gateways disponibles
     *
     * Ajouter un gateway = ajouter 1 ligne ici
     */
    protected function registerGateways(): void
    {
        $this->gateways = [
            'orange' => \App\Services\Payments\Gateways\OrangeMoneyGateway::class,
            // 'cinetpay'    => \App\Services\Payments\Gateways\CinetPayGateway::class,
            // 'flutterwave' => \App\Services\Payments\Gateways\FlutterwaveGateway::class,
            // 'stripe'      => \App\Services\Payments\Gateways\StripeGateway::class,
        ];
    }

    /**
     * Récupérer les moyens de paiement disponibles pour un pays
     */
    public function getAvailableMethods(string $countryCode, bool $onlyActive = true): Collection
    {
        $query = PaymentGateway::forCountry($countryCode)->orderBy('priority');

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Initialiser un paiement
     */
    public function initiate(Order $order, string $gatewayCode, array $data = []): array
    {
        // 1. Récupérer la config du gateway
        $gatewayModel = PaymentGateway::where('country_code', $order->country)
            ->where('gateway', $gatewayCode)
            ->where('is_active', true)
            ->first();

        if (!$gatewayModel) {
            throw new PaymentException(
                "Gateway {$gatewayCode} non disponible pour le pays {$order->country}",
                400,
                ['country' => $order->country, 'gateway' => $gatewayCode]
            );
        }

        // 2. Récupérer l'instance du gateway
        $gatewayInstance = $this->resolveGateway($gatewayCode);

        // 3. Appeler le gateway
        Log::info("💳 Initiation paiement", [
            'order_id' => $order->id,
            'gateway' => $gatewayCode,
            'amount' => $order->total,
            'country' => $order->country,
        ]);

        $result = $gatewayInstance->initialize($order, $data);

        if (!$result['success']) {
            throw new PaymentException(
                $result['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                400,
                ['gateway_response' => $result['raw'] ?? null]
            );
        }

        return $result;
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function verify(string $gatewayCode, string $transactionId): array
    {
        $gatewayInstance = $this->resolveGateway($gatewayCode);
        return $gatewayInstance->verify($transactionId);
    }

    /**
     * Rembourser un paiement
     */
    public function refund(string $gatewayCode, string $transactionId, ?float $amount = null): array
    {
        $gatewayInstance = $this->resolveGateway($gatewayCode);
        return $gatewayInstance->refund($transactionId, $amount);
    }

    /**
     * Traiter un webhook
     */
    public function handleWebhook(string $gatewayCode, array $payload, array $headers = []): array
    {
        $gatewayInstance = $this->resolveGateway($gatewayCode);

        // 1. Vérifier la signature
        if (!$gatewayInstance->verifyWebhookSignature($payload, $headers)) {
            Log::warning("⚠️ Signature webhook invalide", [
                'gateway' => $gatewayCode,
                'payload' => $payload,
            ]);
            throw new PaymentException('Signature webhook invalide', 401);
        }

        // 2. Traiter le webhook
        return $gatewayInstance->handleWebhook($payload, $headers);
    }

    /**
     * Envoyer un payout
     */
    public function payout(string $gatewayCode, array $data): array
    {
        $gatewayInstance = $this->resolveGateway($gatewayCode);
        return $gatewayInstance->payout($data);
    }

    /**
     * Résoudre l'instance du gateway
     */
    protected function resolveGateway(string $gatewayCode): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$gatewayCode])) {
            throw new PaymentException(
                "Gateway '{$gatewayCode}' non supporté par Masasugu",
                400,
                ['available' => array_keys($this->gateways)]
            );
        }

        $gatewayClass = $this->gateways[$gatewayCode];
        $instance = new $gatewayClass();

        if (!$instance instanceof PaymentGatewayInterface) {
            throw new PaymentException(
                "Gateway '{$gatewayCode}' n'implémente pas PaymentGatewayInterface",
                500
            );
        }

        return $instance;
    }

    /**
     * Générer un ID de transaction unique
     */
    public function generateTransactionId(): string
    {
        return 'TXN-' . strtoupper(Str::random(12));
    }

    /**
     * Vérifier si un gateway est supporté
     */
    public function isSupported(string $gatewayCode): bool
    {
        return isset($this->gateways[$gatewayCode]);
    }

    /**
     * Lister tous les gateways supportés
     */
    public function getSupportedGateways(): array
    {
        return array_keys($this->gateways);
    }
}
