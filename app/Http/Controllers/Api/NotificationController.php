<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        return response()->json(Auth::user()->notifications);
    }

    public function unread()
    {
        return response()->json(Auth::user()->unreadNotifications);
    }

    public function markAsRead(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $notification = Auth::user()->notifications()->where('id', $request->id)->first();

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Notification non trouvée'], 404);
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }
}
