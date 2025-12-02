<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // Liste conversations de l'utilisateur (client) ou de la boutique (vendeur)
    public function index()
    {
        $user = Auth::user();
        // si user is vendor (has company) => return conversations where company_id = user's company
        // sinon return conversations where user_id = $user->id
        // adapte selon ton modèle Company relation
    }

    // Récupérer messages d'une conversation
    public function messages($conversationId)
    {
        $user = Auth::user();
        $conv = Conversation::with(['messages.sender'])->findOrFail($conversationId); 
        // vérifier que l'utilisateur a le droit d'accéder
        return response()->json(['conversation' => $conv]);
    }

    // Envoyer message
    public function send(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer|exists:conversations,id',
            'content' => 'nullable|string',
            'type' => 'nullable|string',
            'receiver_id' => 'required|integer|exists:users,id',
        ]);
        $user = Auth::user();

        $msg = Message::create([
            'conversation_id' => $request->conversation_id,
            'sender_id' => $user->id,
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
            'type' => $request->type ?? 'text',
            'metadata' => $request->metadata ?? null,
        ]);

        // Mettre à jour conversation
        $conv = Conversation::find($request->conversation_id);
        $conv->last_message = $msg->content;
        $conv->last_at = now();
        $conv->save();

        // broadcast
        broadcast(new MessageSent($msg))->toOthers();

        return response()->json(['message' => $msg], 201);
    }
}
