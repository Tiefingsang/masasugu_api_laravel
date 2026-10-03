<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;

/**
 * CommissionEngine — Calcul des commissions et frais
 *
 * Calcule pour chaque item d'une commande :
 * - Le montant total de l'item
 * - La commission Masasugu
 * - Les frais du prestataire
 * - Le montant net pour le vendeur
 */
class CommissionEngine
{
    protected float $platformFeePercent;
    protected float $gatewayFeePercent;

    public function __construct()
    {
        $this->platformFeePercent = (float) Setting::get('platform_fee_percent', 5);
        $this->gatewayFeePercent = (float) Setting::get('gateway_fee_percent', 2);
    }

    /**
     * Calculer la commission + frais pour un item
     */
    public function calculateForItem(OrderItem $item): array
    {
        $itemTotal = $this->getItemTotal($item);

        $platformFee = round($itemTotal * $this->platformFeePercent / 100, 2);
        $gatewayFee = round($itemTotal * $this->gatewayFeePercent / 100, 2);
        $sellerAmount = round($itemTotal - $platformFee - $gatewayFee, 2);

        return [
            'item_total' => $itemTotal,
            'platform_fee' => $platformFee,
            'gateway_fee' => $gatewayFee,
            'seller_amount' => $sellerAmount,
            'platform_fee_percent' => $this->platformFeePercent,
            'gateway_fee_percent' => $this->gatewayFeePercent,
        ];
    }

    /**
     * Calculer pour toute une commande (multi-vendeurs)
     */
    public function calculateForOrder(Order $order): array
    {
        $result = [
            'subtotal' => 0,
            'platform_fee_total' => 0,
            'gateway_fee_total' => 0,
            'sellers' => [],
        ];

        foreach ($order->items as $item) {
            $calc = $this->calculateForItem($item);

            $result['subtotal'] += $calc['item_total'];
            $result['platform_fee_total'] += $calc['platform_fee'];
            $result['gateway_fee_total'] += $calc['gateway_fee'];

            $sellerId = $item->seller_id ?? $item->company_id ?? 0;

            if (!isset($result['sellers'][$sellerId])) {
                $result['sellers'][$sellerId] = [
                    'seller_id' => $sellerId,
                    'items' => [],
                    'total' => 0,
                    'platform_fee' => 0,
                    'gateway_fee' => 0,
                    'seller_amount' => 0,
                ];
            }

            $result['sellers'][$sellerId]['items'][] = $item->id;
            $result['sellers'][$sellerId]['total'] += $calc['item_total'];
            $result['sellers'][$sellerId]['platform_fee'] += $calc['platform_fee'];
            $result['sellers'][$sellerId]['gateway_fee'] += $calc['gateway_fee'];
            $result['sellers'][$sellerId]['seller_amount'] += $calc['seller_amount'];
        }

        // Arrondir
        $result['subtotal'] = round($result['subtotal'], 2);
        $result['platform_fee_total'] = round($result['platform_fee_total'], 2);
        $result['gateway_fee_total'] = round($result['gateway_fee_total'], 2);

        foreach ($result['sellers'] as &$seller) {
            $seller['total'] = round($seller['total'], 2);
            $seller['platform_fee'] = round($seller['platform_fee'], 2);
            $seller['gateway_fee'] = round($seller['gateway_fee'], 2);
            $seller['seller_amount'] = round($seller['seller_amount'], 2);
        }

        return $result;
    }

    /**
     * Calculer le montant total d'un item
     */
    protected function getItemTotal(OrderItem $item): float
    {
        return round(($item->price ?? 0) * ($item->quantity ?? 1), 2);
    }

    /**
     * Appliquer les calculs à une commande (en base)
     */
    public function applyToOrder(Order $order): void
    {
        $calc = $this->calculateForOrder($order);

        $order->update([
            'subtotal' => $calc['subtotal'],
            'platform_fee_total' => $calc['platform_fee_total'],
            'gateway_fee_total' => $calc['gateway_fee_total'],
        ]);

        foreach ($order->items as $item) {
            $itemCalc = $this->calculateForItem($item);
            $item->update([
                'platform_fee' => $itemCalc['platform_fee'],
                'gateway_fee' => $itemCalc['gateway_fee'],
                'seller_amount' => $itemCalc['seller_amount'],
            ]);
        }
    }

    public function getPlatformFeePercent(): float
    {
        return $this->platformFeePercent;
    }

    public function getGatewayFeePercent(): float
    {
        return $this->gatewayFeePercent;
    }
}
