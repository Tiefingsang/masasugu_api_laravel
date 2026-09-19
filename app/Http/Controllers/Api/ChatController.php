<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\FcmService;

class ChatController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    // 📋 LISTE DES CONVERSATIONS
    // ✅ CORRIGÉ : filtre par receiver_id dans messages
    // ═══════════════════════════════════════════════════════════
    public function index()
    {
        $user = Auth::user();

        $conversations = Conversation::with([
            'user',
            'company.user',
            'messages' => function ($q) use ($user) {   // ✅ use ($user)
                $q->where('receiver_id', $user->id)      // ✅ NOUVEAU
                  ->whereNull('read_at');
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
                    'unread_count' => $c->messages->count(),   // ✅ Maintenant = messages reçus non lus
                ];
            })
        );
    }

    // ═══════════════════════════════════════════════════════════
    // 🆕 CRÉER OU RÉCUPÉRER UNE CONVERSATION
    // ═══════════════════════════════════════════════════════════
    public function createOrGetConversation(Request $request)
    {
        Log::info(['request' => $request->all()]);

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

        return response()->json(
            $conversation,
            $conversation->wasRecentlyCreated ? 201 : 200
        );
    }

    // ═══════════════════════════════════════════════════════════
    // 💬 MESSAGES D'UNE CONVERSATION
    // ═══════════════════════════════════════════════════════════
    public function messages($conversationId)
    {
        $conversation = Conversation::with(['messages.sender'])
            ->findOrFail($conversationId);

        return response()->json($conversation->messages);
    }

    // ═══════════════════════════════════════════════════════════
    // 📤 ENVOYER UN MESSAGE
    // ═══════════════════════════════════════════════════════════
    public function send(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content'         => 'required|string',
        ]);

        $user = Auth::user();
        $conversation = Conversation::with('company.user')
            ->findOrFail($request->conversation_id);

        // Calculer le destinataire (users.id)
        if ($conversation->user_id == $user->id) {
            $receiverId = $conversation->company->user_id ?? null;
        } else {
            $receiverId = $conversation->user_id;
        }

        if (!$receiverId) {
            return response()->json(['error' => 'Destinataire introuvable'], 422);
        }

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'receiver_id'     => $receiverId,
            'content'         => $request->content,
            'type'            => 'text',
        ]);

        $conversation->update([
            'last_message' => $msg->content,
            'last_at'      => now(),
        ]);

        $msg->load('sender');

        // 🔔 Notifications
        $receiver = \App\Models\User::find($receiverId);

        if ($receiver) {
            $title = $user->name ?? 'Masasugu';
            $body  = mb_strlen($msg->content) > 120
                ? mb_substr($msg->content, 0, 120) . '...'
                : $msg->content;

            $notifData = [
                'type'            => 'message',
                'conversation_id' => (string) $conversation->id,
                'sender_id'       => (string) $user->id,
                'receiver_id'     => (string) $receiverId,
                'content'         => (string) $msg->content,
                'sender_name'     => (string) ($user->name ?? 'Masasugu'),
            ];

            try {
                $fcm = new FcmService();
                $fcm->sendToUser($receiver, $title, $body, $notifData);
                Log::info('✅ FCM envoyé au destinataire ID: ' . $receiverId);
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur FCM: ' . $e->getMessage());
            }

            try {
                broadcast(new MessageSent($msg));
                Log::info('✅ Broadcast Reverb envoyé');
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur broadcast: ' . $e->getMessage());
            }
        }

        return response()->json($msg, 201);
    }

    // ═══════════════════════════════════════════════════════════
    // 📎 UPLOAD PIÈCE JOINTE
    // ═══════════════════════════════════════════════════════════
    public function upload(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'file'            => 'required|file|max:10240',
            'type'            => 'required|in:image,video,file,audio',
            'caption'         => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $conversation = Conversation::with('company.user')
            ->findOrFail($request->conversation_id);

        if ($conversation->user_id == $user->id) {
            $receiverId = $conversation->company->user_id ?? null;
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
            'receiver_id'     => $receiverId,
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

        $msg->load('sender');

        // 🔔 Notifications
        $receiver = \App\Models\User::find($receiverId);

        if ($receiver) {
            $title = $user->name ?? 'Masasugu';

            $body = match ($request->type) {
                'image' => '📸 Photo' . ($request->caption ? ' : ' . $request->caption : ''),
                'video' => '🎥 Vidéo' . ($request->caption ? ' : ' . $request->caption : ''),
                'audio' => '🎵 Audio' . ($request->caption ? ' : ' . $request->caption : ''),
                default => '📎 Fichier' . ($request->caption ? ' : ' . $request->caption : ''),
            };

            $notifData = [
                'type'            => 'message',
                'conversation_id' => (string) $conversation->id,
                'sender_id'       => (string) $user->id,
                'receiver_id'     => (string) $receiverId,
                'content'         => (string) ($request->caption ?? ''),
                'sender_name'     => (string) ($user->name ?? 'Masasugu'),
                'attachment_type' => (string) $request->type,
                'attachment_url'  => (string) asset('storage/' . $path),
            ];

            try {
                $fcm = new FcmService();
                $fcm->sendToUser($receiver, $title, $body, $notifData);
                Log::info('✅ FCM envoyé (pièce jointe) au destinataire ID: ' . $receiverId);
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur FCM upload: ' . $e->getMessage());
            }

            try {
                broadcast(new MessageSent($msg));
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur broadcast upload: ' . $e->getMessage());
            }
        }

        return response()->json($msg, 201);
    }

    // ═══════════════════════════════════════════════════════════
    // ✅ MARQUER COMME LU
    // ═══════════════════════════════════════════════════════════
    public function markAsRead($conversationId)
    {
        $user = Auth::user();

        Message::where('conversation_id', $conversationId)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Log::info('✅ Messages marqués comme lus', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
        ]);

        return response()->json(['success' => true]);
    }
}
