<?php

namespace App\Services\Payments;

use App\Models\Order;

/**
 * Interface PaymentGatewayInterface
 *
 * Toutes les gateways de paiement DOIVENT implémenter cette interface.
 *
 * Cela permet d'avoir une abstraction uniforme :
 * - Orange Money
 * - CinetPay
 * - Flutterwave
 * - Stripe
 * - PayDunya
 *
 * → Ajouter un gateway = créer 1 classe qui implémente cette interface.
 */
interface PaymentGatewayInterface
{
    /**
     * Initialiser un paiement
     *
     * @param Order $order La commande à payer
     * @param array $data Données additionnelles (phone, email, etc.)
     * @return array [
     *     'success' => bool,
     *     'payment_url' => string|null,
     *     'transaction_id' => string|null,
     *     'pay_token' => string|null,
     *     'message' => string,
     *     'raw' => array|null,
     * ]
     */
    public function initialize(Order $order, array $data = []): array;

    /**
     * Vérifier le statut d'un paiement
     */
    public function verify(string $transactionId): array;

    /**
     * Rembourser un paiement
     */
    public function refund(string $transactionId, ?float $amount = null): array;

    /**
     * Traiter un webhook reçu du gateway
     */
    public function handleWebhook(array $payload, array $headers = []): array;

    /**
     * Envoyer un payout (retrait vendeur)
     */
    public function payout(array $data): array;

    /**
     * Vérifier la signature d'un webhook
     */
    public function verifyWebhookSignature(array $payload, array $headers): bool;

    /**
     * Code unique du gateway (ex: orange, cinetpay, stripe)
     */
    public function getCode(): string;

    /**
     * Nom affiché (ex: "Orange Money Mali")
     */
    public function getName(): string;
}
