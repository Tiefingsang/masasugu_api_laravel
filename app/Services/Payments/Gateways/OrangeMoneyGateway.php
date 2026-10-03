<?php

namespace App\Services\Payments\Gateways;

use App\Models\Order;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;

/**
 * OrangeMoneyGateway — Intégration Orange Money Mali
 *
 * ⚠️ VERSION PLACEHOLDER
 * La logique complète sera implémentée dans la Session 3.
 */
class OrangeMoneyGateway implements PaymentGatewayInterface
{
    public function getCode(): string
    {
        return 'orange';
    }

    public function getName(): string
    {
        return 'Orange Money Mali';
    }

    public function initialize(Order $order, array $data = []): array
    {
        Log::info('🟠 OrangeMoneyGateway::initialize', [
            'order_id' => $order->id,
            'amount' => $order->total,
        ]);

        return [
            'success' => false,
            'message' => 'Orange Money Gateway sera implémenté dans la Session 3',
            'payment_url' => null,
            'transaction_id' => null,
            'raw' => null,
        ];
    }

    public function verify(string $transactionId): array
    {
        return ['success' => false, 'status' => 'not_implemented'];
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        return ['success' => false, 'message' => 'Non implémenté'];
    }

    public function handleWebhook(array $payload, array $headers = []): array
    {
        return ['success' => false, 'message' => 'Non implémenté'];
    }

    public function payout(array $data): array
    {
        return ['success' => false, 'message' => 'Non implémenté'];
    }

    public function verifyWebhookSignature(array $payload, array $headers): bool
    {
        return false;
    }
}
