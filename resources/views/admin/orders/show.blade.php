@extends('admin.layouts.app')

@section('title', 'Commande #' . str_pad($order->id, 5, '0', STR_PAD_LEFT))

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.orders.index') }}" class="text-orange-500 hover:text-orange-700">
        <i class="fas fa-arrow-left"></i> Retour aux commandes
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- COLONNE GAUCHE --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- EN-TÊTE --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        Commande #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $order->created_at->format('d/m/Y à H:i') }}
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($order->status === 'pending')
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-orange-100 text-orange-700">⏳ En attente</span>
                        @elseif($order->status === 'confirmed')
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-700">✅ Confirmée</span>
                        @elseif($order->status === 'shipped')
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-yellow-100 text-yellow-700">🚚 Expédiée</span>
                        @elseif($order->status === 'delivered')
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-700">📦 Livrée</span>
                        @elseif($order->status === 'cancelled')
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-red-100 text-red-700">❌ Annulée</span>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-orange-500">
                        {{ number_format($order->total, 0, ',', ' ') }} CFA
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ ucfirst($order->payment_method ?? '—') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- ARTICLES --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h2 class="font-bold text-gray-800">
                    <i class="fas fa-shopping-basket text-orange-500"></i>
                    Articles ({{ $order->items->count() }})
                </h2>
            </div>
            <div class="p-4 space-y-3">
                @foreach($order->items as $item)
                    @php
                        $product = $item->product;
                        $imageUrl = null;
                        if ($product && $product->main_image) {
                            $imageUrl = str_starts_with($product->main_image, 'http')
                                ? $product->main_image
                                : asset('storage/' . ltrim($product->main_image, '/'));
                        }
                    @endphp
                    <div class="flex items-center gap-4 p-3 border rounded-lg">
                        <div class="w-16 h-16 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" class="w-full h-full object-cover" onerror="this.style.display='none';">
                            @else
                                <i class="fas fa-image text-gray-400"></i>
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-gray-800">{{ $product->name ?? 'Produit supprimé' }}</div>
                            <div class="text-xs text-gray-500">
                                Boutique : {{ $product->company->name ?? '—' }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-gray-800">
                                {{ number_format($item->price ?? 0, 0, ',', ' ') }} CFA
                            </div>
                            <div class="text-xs text-gray-500">
                                Qté : {{ $item->quantity ?? 1 }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- COLONNE DROITE --}}
    <div class="space-y-6">

        {{-- CLIENT --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-user text-orange-500"></i> Client</h2>
            <div class="text-sm space-y-2">
                <div>
                    <div class="text-xs text-gray-500">Nom</div>
                    <div class="font-medium">{{ $order->user->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Email</div>
                    <div class="font-medium break-all">{{ $order->user->email ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Téléphone</div>
                    <div class="font-medium">{{ $order->user->phone ?? '—' }}</div>
                </div>
            </div>
            @if($order->user)
                <a href="{{ route('admin.users.show', $order->user->id) }}"
                   class="inline-block mt-3 text-orange-500 hover:text-orange-700 text-sm">
                    Voir le profil <i class="fas fa-arrow-right"></i>
                </a>
            @endif
        </div>

        {{-- CHANGER STATUT --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-sync text-orange-500"></i> Changer le statut</h2>

            <form method="POST" action="{{ route('admin.orders.change-status', $order->id) }}">
                @csrf
                <select name="status" class="w-full px-3 py-2 border rounded-lg mb-3">
                    <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>⏳ En attente</option>
                    <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>✅ Confirmée</option>
                    <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>🚚 Expédiée</option>
                    <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>📦 Livrée</option>
                    <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>❌ Annulée</option>
                </select>
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2 rounded-lg">
                    <i class="fas fa-check"></i> Appliquer
                </button>
            </form>
        </div>

        {{-- ACTIONS RAPIDES --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-bolt text-orange-500"></i> Actions rapides</h2>

            @if($order->status === 'pending')
                <form method="POST" action="{{ route('admin.orders.confirm', $order->id) }}" class="mb-2">
                    @csrf
                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 rounded-lg">
                        <i class="fas fa-check"></i> Confirmer
                    </button>
                </form>
            @endif

            @if($order->status === 'confirmed')
                <form method="POST" action="{{ route('admin.orders.ship', $order->id) }}" class="mb-2">
                    @csrf
                    <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 rounded-lg">
                        <i class="fas fa-truck"></i> Marquer expédiée
                    </button>
                </form>
            @endif

            @if($order->status === 'shipped')
                <form method="POST" action="{{ route('admin.orders.deliver', $order->id) }}" class="mb-2">
                    @csrf
                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-lg">
                        <i class="fas fa-box"></i> Marquer livrée
                    </button>
                </form>
            @endif

            @if($order->status !== 'cancelled' && $order->status !== 'delivered')
                <button type="button"
                        onclick="document.getElementById('cancel-form').classList.toggle('hidden')"
                        class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-lg mb-2">
                    <i class="fas fa-times"></i> Annuler la commande
                </button>
                <form id="cancel-form" method="POST" action="{{ route('admin.orders.cancel', $order->id) }}"
                      class="hidden mb-2 space-y-2">
                    @csrf
                    <textarea name="reason" placeholder="Raison de l'annulation..."
                              class="w-full px-3 py-2 border rounded-lg text-sm" rows="2"></textarea>
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg text-sm">
                        Confirmer l'annulation
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.orders.destroy', $order->id) }}"
                  onsubmit="return confirm('⚠️ Supprimer définitivement la commande #{{ $order->id }} ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full bg-gray-800 hover:bg-black text-white py-3 rounded-lg mt-2">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </form>
        </div>

    </div>

</div>

@endsection
