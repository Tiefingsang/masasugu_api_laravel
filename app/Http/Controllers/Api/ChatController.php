<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller{
    // Liste conversations de l'utilisateur (client) ou de la boutique (vendeur)
    /* public function index(){
        $user = Auth::user();

        if ($user->isSeller() && $user->company) {
            return Conversation::where('company_id', $user->company->id)
                ->orderByDesc('last_at')
                ->get();
        }

        return Conversation::where('user_id', $user->id)
            ->orderByDesc('last_at')
            ->get();
    } */

    public function index(){
        $user = Auth::user();

        $conversations = Conversation::with([
            'user',
            'company.user',
            'messages' => function ($q) {
                $q->whereNull('read_at');
            }

        ])
        ->where(function ($q) use ($user) {
            if ($user->isSeller() && $user->company) {
                $q->where('company_id', $user->company->id);
            } else {
                $q->where('user_id', $user->id);
            }
        })
        ->orderByDesc('last_at')
        ->get();

        return response()->json(
            $conversations->map(function ($c) use ($user) {

                $isSeller = $user->isSeller();

                $receiver = $isSeller
                    ? $c->user
                    : $c->company->user;

                return [
                    'id' => $c->id,
                    'title' => $receiver->name,
                    'avatar' => $receiver->avatar ?? null,
                    'last_message' => $c->last_message,
                    'last_at' => $c->last_at,
                    'receiver_id' => $receiver->id,
                    'unread_count' => $c->messages->count(),
                ];
            })
        );
    }



     public function createOrGetConversation(Request $request){
        Log::info(array('request'=> $request->all()));
        $request->validate([
            'receiver_id' => 'required|exists:companies,id',
        ]);

        $user = Auth::user();
        $companyId = $request->receiver_id;

        $conversation = Conversation::where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'last_message' => null,
                'last_at' => now(),
            ]);
        }

        return response()->json($conversation, $conversation->wasRecentlyCreated ? 201 : 200);


        //return response()->json($conversation, 201);
    } 
   /*  public function createOrGetConversation(Request $request){
        $request->validate([
            'receiver_id' => 'required|exists:companies,id',
        ]);

        return response()->json([
            'ok' => true,
            'receiver_id' => $request->receiver_id,
        ], 200);
    } */

        /* public function createOrGetConversation(Request $request){
            \Log::info('headers', $request->headers->all());
            \Log::info('all', $request->all());

            return response()->json([
                'ok' => true,
                'data' => $request->all(),
            ], 200);
        } */





    // Récupérer messages d'une conversation
    public function messages($conversationId){
        $conversation = Conversation::with(['messages.sender'])
            ->findOrFail($conversationId);

        return response()->json($conversation->messages);
    }


    // Envoyer message
    /* public function send(Request $request){
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string',
        ]);

        $user = Auth::user();
        $conversation = Conversation::with('company.user')
            ->findOrFail($request->conversation_id);

        $receiverId = $conversation->user_id == $user->id
            ? $conversation->company->user_id
            : $conversation->user_id;

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'content' => $request->content,
            'type' => 'text',
        ]);

        $conversation->update([
            'last_message' => $msg->content,
            'last_at' => now(),
        ]);

        broadcast(new MessageSent($msg))->toOthers();

        return response()->json($msg, 201);
    } */

    public function send(Request $request){
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string',
            'receiver_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();

        $conversation = Conversation::findOrFail($request->conversation_id);

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
            'type' => 'text',
        ]);

        $conversation->update([
            'last_message' => $msg->content,
            'last_at' => now(),
        ]);

        broadcast(new MessageSent($msg))->toOthers();

        //return response()->json(['message' => $msg], 201);
        return response()->json($msg, 201);

    }


    public function markAsRead($conversationId){
        $user = Auth::user();

        Message::where('conversation_id', $conversationId)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);


        return response()->json(['success' => true]);
    }



}
