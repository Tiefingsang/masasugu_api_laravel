<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('vendor.{vendorId}', function ($user, $vendorId) {
    return (int) $user->company_id === (int) $vendorId;
});

Broadcast::channel('chat.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// ═══════════════════════════════════════════════
// CANAUX DE PAIEMENT (temps réel)
// ═══════════════════════════════════════════════

// Canal privé vendeur : notifications de vente/wallet
Broadcast::channel('seller.{sellerId}', function ($user, $sellerId) {
    return (int) $user->id === (int) $sellerId;
});

// Canal privé acheteur : notifications de paiement
Broadcast::channel('buyer.{buyerId}', function ($user, $buyerId) {
    return (int) $user->id === (int) $buyerId;
});



