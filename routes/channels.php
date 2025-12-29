<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('vendor.{vendorId}', function ($user, $vendorId) {
    return (int) $user->company_id === (int) $vendorId;
});


