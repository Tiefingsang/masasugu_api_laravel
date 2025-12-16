<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;

    
});


Broadcast::channel('vendor.{vendorId}', function ($user, $vendorId) {
    return $user->id == $vendorId; 
});

