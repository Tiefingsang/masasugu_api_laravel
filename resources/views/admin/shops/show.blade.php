@extends('admin.layouts.app')

@section('title', 'Boutique : ' . $shop->name)

@section('content')

{{-- BOUTON RETOUR --}}
<div class="mb-4">
    <a href="{{ route('admin.shops.index') }}" class="text-orange-500 hover:text-orange-700">
        <i class="fas fa-arrow-left"></i> Retour à la liste
    </a>
</div>

{{-- STATS RAPIDES --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
        <div class="text-xs text-gray-500 uppercase">Produits</div>
        <div class="text-2xl font-bold text-gray-800">{{ $stats['products_count'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-orange-500">
        <div class="text-xs text-gray-500 uppercase">Commandes</div>
        <div class="text-2xl font-bold text-gray-800">{{ $stats['orders_count'] }}</div>
        @if($stats['pending_orders'] > 0)
            <div class="text-xs text-orange-500 mt-1">⏳ {{ $stats['pending_orders'] }} en attente</div>
        @endif
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
        <div class="text-xs text-gray-500 uppercase">Revenus</div>
        <div class="text-xl font-bold text-green-600">{{ number_format($stats['revenue'], 0, ',', ' ') }} CFA</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-gray-500">
        <div class="text-xs text-gray-500 uppercase">Créée le</div>
        <div class="text-sm font-bold text-gray-800">{{ $shop->created_at->format('d/m/Y') }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- COLONNE GAUCHE --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- CARTE BOUTIQUE --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start gap-4">
                @if($shop->logo)
                    <img src="{{ str_starts_with($shop->logo, 'http') ? $shop->logo : 'https://api.masasugu.com/storage/' . ltrim($shop->logo, '/') }}"
                        class="w-20 h-20 rounded-lg object-cover bg-gray-200">
                @else
                    <div class="w-20 h-20 rounded-lg bg-orange-100 flex items-center justify-center">
                        <i class="fas fa-store text-orange-500 text-3xl"></i>
                    </div>
                @endif
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        {{ $shop->name }}
                        @if($shop->is_verified)
                            <i class="fas fa-check-circle text-blue-500 text-lg" title="Vérifiée"></i>
                        @endif
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $shop->slug }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($shop->status === 'pending')
                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-orange-100 text-orange-700">
                                ⏳ En attente de validation
                            </span>
                        @elseif($shop->status === 'approved')
                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-700">
                                ✅ Approuvée
                            </span>
                        @elseif($shop->status === 'rejected')
                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-red-100 text-red-700">
                                ❌ Refusée
                            </span>
                        @endif

                        @if($shop->is_active)
                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-700">
                                🟢 Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-gray-200 text-gray-700">
                                ⏸️ Suspendue
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-6 pt-6 border-t">
                <div>
                    <div class="text-xs text-gray-500 uppercase">Pays</div>
                    <div class="text-sm font-medium">{{ $shop->country ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Ville</div>
                    <div class="text-sm font-medium">{{ $shop->city ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Adresse</div>
                    <div class="text-sm font-medium">{{ $shop->address ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Téléphone</div>
                    <div class="text-sm font-medium">{{ $shop->contact_phone ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Email</div>
                    <div class="text-sm font-medium">{{ $shop->contact_email ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Date inscription</div>
                    <div class="text-sm font-medium">{{ $shop->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>

            @if($shop->description)
                <div class="mt-6 pt-6 border-t">
                    <div class="text-xs text-gray-500 uppercase mb-2">Description</div>
                    <p class="text-sm text-gray-700">{{ $shop->description }}</p>
                </div>
            @endif
        </div>

        {{-- PRODUITS --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h2 class="font-bold text-gray-800">
                    <i class="fas fa-box text-orange-500"></i>
                    Produits récents ({{ $stats['products_count'] }})
                </h2>
            </div>
            <div class="p-4">
                @if($shop->products->count() > 0)
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($shop->products as $product)
                            <div class="border rounded-lg p-3 hover:shadow">
                                @if($product->main_image)
                                    <img src="https://api.masasugu.com/storage/{{ $product->main_image }}"
                                        class="w-full h-24 object-cover rounded mb-2 bg-gray-100"
                                        onerror="this.style.display='none'">
                                @endif
                                <div class="text-sm font-medium truncate">{{ $product->name }}</div>
                                <div class="text-xs text-orange-500 font-semibold">
                                    {{ number_format($product->price, 0, ',', ' ') }} CFA
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-gray-400 py-6">Aucun produit</div>
                @endif
            </div>
        </div>

    </div>

    {{-- COLONNE DROITE --}}
    <div class="space-y-6">

        {{-- VENDEUR --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-bold text-gray-800 mb-4">
                <i class="fas fa-user text-orange-500"></i> Vendeur
            </h2>
            <div class="text-sm space-y-3">
                <div>
                    <div class="text-xs text-gray-500">Nom</div>
                    <div class="font-medium">{{ $shop->user->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Email</div>
                    <div class="font-medium break-all">{{ $shop->user->email ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Rôle</div>
                    <div class="font-medium">{{ $shop->user->role ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Vérifié</div>
                    <div class="font-medium">
                        @if($shop->user->is_verified ?? false)
                            <span class="text-green-500">✅ Oui</span>
                        @else
                            <span class="text-orange-500">⚠️ Non</span>
                        @endif
                    </div>
                </div>
            </div>

            @if($shop->user)
                <form method="POST" action="{{ route('admin.shops.verify-user', $shop->id) }}" class="mt-4">
                    @csrf
                    <button type="submit"
                            class="w-full {{ $shop->user->is_verified ? 'bg-gray-500 hover:bg-gray-600' : 'bg-blue-500 hover:bg-blue-600' }} text-white font-medium py-2 rounded-lg text-sm">
                        @if($shop->user->is_verified)
                            <i class="fas fa-user-times"></i> Dévérifier
                        @else
                            <i class="fas fa-user-check"></i> Vérifier ce vendeur
                        @endif
                    </button>
                </form>
            @endif
        </div>

        {{-- ACTIONS --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-bold text-gray-800 mb-4">
                <i class="fas fa-gavel text-orange-500"></i> Actions admin
            </h2>

            {{-- Bouton Approuver --}}
            @if($shop->status !== 'approved')
                <form method="POST" action="{{ route('admin.shops.approve', $shop->id) }}" class="mb-3">
                    @csrf
                    <button type="submit"
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-lg">
                        <i class="fas fa-check"></i> Approuver cette boutique
                    </button>
                </form>
            @else
                <div class="mb-3 p-3 bg-green-50 border border-green-200 rounded-lg text-center text-sm text-green-700">
                    <i class="fas fa-check-circle"></i> Boutique approuvée
                </div>
            @endif

            {{-- Bouton Refuser --}}
            @if($shop->status !== 'rejected')
                <button type="button"
                        onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                        class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-lg mb-3">
                    <i class="fas fa-times"></i> Refuser
                </button>

                <form id="reject-form" method="POST" action="{{ route('admin.shops.reject', $shop->id) }}"
                    class="hidden mb-3 space-y-2">
                    @csrf
                    <textarea name="reason" required placeholder="Raison du refus (visible par le vendeur)..."
                            class="w-full px-3 py-2 border rounded-lg text-sm" rows="3"></textarea>
                    <button type="submit"
                            class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg text-sm">
                        Confirmer le refus
                    </button>
                </form>
            @endif

            {{-- Bouton Suspendre / Réactiver --}}
            @if($shop->is_active)
                <form method="POST" action="{{ route('admin.shops.suspend', $shop->id) }}" class="mb-3">
                    @csrf
                    <button type="submit"
                            class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 rounded-lg">
                        <i class="fas fa-pause"></i> Suspendre la boutique
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.shops.activate', $shop->id) }}" class="mb-3">
                    @csrf
                    <button type="submit"
                            class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 rounded-lg">
                        <i class="fas fa-play"></i> Réactiver la boutique
                    </button>
                </form>
            @endif

            {{-- Bouton Supprimer --}}
            <form method="POST" action="{{ route('admin.shops.destroy', $shop->id) }}"
                onsubmit="return confirm('⚠️ ATTENTION : Supprimer définitivement la boutique « {{ $shop->name }} » et tous ses produits ? Cette action est IRRÉVERSIBLE.');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full bg-gray-800 hover:bg-black text-white font-bold py-3 rounded-lg">
                    <i class="fas fa-trash"></i> Supprimer la boutique
                </button>
            </form>
        </div>

    </div>

</div>

@endsection
