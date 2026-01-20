<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('vendor.{vendorId}', function ($user, $vendorId) {
    return (int) $user->company_id === (int) $vendorId;
});

Broadcast::channel('chat.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});



