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

    // public function send(Request $request){
    //     $request->validate([
    //         'conversation_id' => 'required|exists:conversations,id',
    //         'content' => 'required|string',
    //         'receiver_id' => 'required|exists:users,id',
    //     ]);

    //     $user = Auth::user();

    //     $conversation = Conversation::findOrFail($request->conversation_id);

    //     $msg = Message::create([
    //         'conversation_id' => $conversation->id,
    //         'sender_id' => $user->id,
    //         'receiver_id' => $request->receiver_id,
    //         'content' => $request->content,
    //         'type' => 'text',
    //     ]);

    //     $conversation->update([
    //         'last_message' => $msg->content,
    //         'last_at' => now(),
    //     ]);

    //     broadcast(new MessageSent($msg))->toOthers();

    //     //return response()->json(['message' => $msg], 201);
    //     return response()->json($msg, 201);

    // }

    public function send(Request $request)
{
    $request->validate([
        'conversation_id' => 'required|exists:conversations,id',
        'content'         => 'required|string',
    ]);

    $user = Auth::user();
    $conversation = Conversation::with('company.user')->findOrFail($request->conversation_id);

    // ✅ CALCULER LE VRAI DESTINATAIRE CÔTÉ BACKEND
    // Si l'expéditeur est l'acheteur → destinataire = vendeur (user_id de la company)
    // Si l'expéditeur est le vendeur → destinataire = acheteur (user_id de la conversation)
    if ($conversation->user_id == $user->id) {
        // L'acheteur envoie → au vendeur
        $receiverId = $conversation->company->user_id;
    } else {
        // Le vendeur envoie → à l'acheteur
        $receiverId = $conversation->user_id;
    }

    if (!$receiverId) {
        return response()->json([
            'error' => 'Destinataire introuvable',
        ], 422);
    }

    $msg = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id'       => $user->id,
        'receiver_id'     => $receiverId,   // ✅ Toujours un users.id
        'content'         => $request->content,
        'type'            => 'text',
    ]);

    $conversation->update([
        'last_message' => $msg->content,
        'last_at'      => now(),
    ]);

    // 🔔 Broadcast SANS toOthers (le vendeur DOIT recevoir)
    broadcast(new MessageSent($msg));

    return response()->json($msg, 201);
}

    /**
 * 📎 Upload d'une pièce jointe (image, vidéo, fichier)
 * Le fichier est stocké dans `storage/app/public/chat/{type}/`
 * Les métadonnées sont stockées dans la colonne `metadata` (JSON).
 */
    // public function upload(Request $request)
    // {
    //     $request->validate([
    //         'conversation_id' => 'required|exists:conversations,id',
    //         'receiver_id'     => 'required|exists:users,id',
    //         'file'            => 'required|file|max:10240',
    //         'type'            => 'required|in:image,video,file,audio',
    //         'caption'         => 'nullable|string|max:500',
    //     ]);

    //     $user = Auth::user();
    //     $conversation = Conversation::findOrFail($request->conversation_id);

    //     // Dossier selon le type
    //     $folder = match ($request->type) {
    //         'image' => 'chat/images',
    //         'video' => 'chat/videos',
    //         'audio' => 'chat/audios',
    //         default => 'chat/files',
    //     };

    //     $file = $request->file('file');
    //     $path = $file->store($folder, 'public');


    //     $msg = Message::create([
    //         'conversation_id' => $conversation->id,
    //         'sender_id'       => $user->id,
    //         'receiver_id'     => $request->receiver_id,
    //         'content'         => $request->caption ?? '',
    //         'type'            => $request->type,
    //         'metadata'        => [
    //             'path' => $path,
    //             'url'  => asset('storage/' . $path),
    //             'name' => $file->getClientOriginalName(),
    //             'size' => $file->getSize(),
    //             'mime' => $file->getMimeType(),
    //         ],
    //     ]);


    //     $conversation->update([
    //         'last_message' => $request->caption ?? "📎 Pièce jointe",
    //         'last_at'      => now(),
    //     ]);

    //     // Broadcast temps réel
    //     broadcast(new MessageSent($msg))->toOthers();

    //     return response()->json($msg, 201);
    // }

    public function upload(Request $request)
{
    $request->validate([
        'conversation_id' => 'required|exists:conversations,id',
        'file'            => 'required|file|max:10240',
        'type'            => 'required|in:image,video,file,audio',
        'caption'         => 'nullable|string|max:500',
    ]);

    $user = Auth::user();
    $conversation = Conversation::with('company.user')->findOrFail($request->conversation_id);

    // ✅ Même calcul côté backend
    if ($conversation->user_id == $user->id) {
        $receiverId = $conversation->company->user_id;
    } else {
        $receiverId = $conversation->user_id;
    }

    if (!$receiverId) {
        return response()->json(['error' => 'Destinataire introuvable'], 422);
    }

    $folder = match ($request->type) {
        'image' => 'chat/images',
        'video' => 'chat/videos',
        'audio' => 'chat/audios',
        default => 'chat/files',
    };

    $file = $request->file('file');
    $path = $file->store($folder, 'public');

    $msg = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id'       => $user->id,
        'receiver_id'     => $receiverId,   // ✅
        'content'         => $request->caption ?? '',
        'type'            => $request->type,
        'metadata'        => [
            'path' => $path,
            'url'  => asset('storage/' . $path),
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ],
    ]);

    $conversation->update([
        'last_message' => $request->caption ?? "📎 Pièce jointe",
        'last_at'      => now(),
    ]);

    // ✅ Sans toOthers
    broadcast(new MessageSent($msg));

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
