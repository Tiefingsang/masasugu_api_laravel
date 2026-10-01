@extends('admin.layouts.app')

@section('title', 'Utilisateur : ' . $user->name)

@section('content')

{{-- BOUTON RETOUR --}}
<div class="mb-4">
    <a href="{{ route('admin.users.index') }}" class="text-orange-500 hover:text-orange-700">
        <i class="fas fa-arrow-left"></i> Retour à la liste
    </a>
</div>

{{-- STATS --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
        <div class="text-xs text-gray-500 uppercase">Commandes</div>
        <div class="text-2xl font-bold text-gray-800">{{ $stats['orders_count'] }}</div>
        <div class="text-xs text-gray-500">{{ number_format($stats['orders_total'], 0, ',', ' ') }} CFA</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-orange-500">
        <div class="text-xs text-gray-500 uppercase">Produits</div>
        <div class="text-2xl font-bold text-gray-800">{{ $stats['products_count'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-gray-500">
        <div class="text-xs text-gray-500 uppercase">Inscrit le</div>
        <div class="text-lg font-bold text-gray-800">{{ $user->created_at->format('d/m/Y') }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- COLONNE GAUCHE --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- CARTE PROFIL --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start gap-4">
                @php
                    $avatarUrl = null;
                    if ($user->avatar) {
                        $avatarUrl = str_starts_with($user->avatar, 'http')
                            ? $user->avatar
                            : asset('storage/' . ltrim($user->avatar, '/'));
                    }
                @endphp
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" class="w-20 h-20 rounded-full object-cover bg-gray-200">
                @else
                    <div class="w-20 h-20 rounded-full bg-orange-100 flex items-center justify-center">
                        <span class="text-orange-500 font-bold text-3xl">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    </div>
                @endif
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        {{ $user->name }}
                        @if($user->is_verified)
                            <i class="fas fa-check-circle text-blue-500" title="Vérifié"></i>
                        @endif
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $user->email }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($user->role === 'admin')
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-red-100 text-red-700">👑 Admin</span>
                        @elseif($user->role === 'seller')
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-purple-100 text-purple-700">🏪 Vendeur</span>
                        @else
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-700">🛒 Acheteur</span>
                        @endif

                        @if($user->status === 'banned')
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-red-100 text-red-700">🚫 Banni</span>
                        @else
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-700">✅ Actif</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('admin.users.edit', $user->id) }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-edit"></i> Éditer
                </a>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-6 pt-6 border-t">
                <div>
                    <div class="text-xs text-gray-500 uppercase">Téléphone</div>
                    <div class="text-sm font-medium">{{ $user->phone ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Pays</div>
                    <div class="text-sm font-medium">{{ $user->country ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Ville</div>
                    <div class="text-sm font-medium">{{ $user->city ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Adresse</div>
                    <div class="text-sm font-medium">{{ $user->address ?? '—' }}</div>
                </div>
            </div>

            @if($user->bio)
                <div class="mt-6 pt-6 border-t">
                    <div class="text-xs text-gray-500 uppercase mb-2">Bio</div>
                    <p class="text-sm text-gray-700">{{ $user->bio }}</p>
                </div>
            @endif
        </div>

        {{-- COMMANDES RÉCENTES --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h2 class="font-bold text-gray-800">
                    <i class="fas fa-shopping-bag text-orange-500"></i> Commandes récentes
                </h2>
            </div>
            <div class="p-4">
                @if($recentOrders->count() > 0)
                    <div class="space-y-2">
                        @foreach($recentOrders as $order)
                            <div class="flex justify-between items-center p-3 border rounded-lg hover:bg-gray-50">
                                <div>
                                    <div class="font-medium text-sm">Commande #{{ $order->id }}</div>
                                    <div class="text-xs text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-sm text-orange-500">
                                        {{ number_format($order->total, 0, ',', ' ') }} CFA
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $order->status }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-gray-400 py-6">Aucune commande</div>
                @endif
            </div>
        </div>

        {{-- BOUTIQUE (si seller) --}}
        @if($user->company)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="font-bold text-gray-800 mb-4">
                    <i class="fas fa-store text-orange-500"></i> Boutique
                </h2>
                <div class="flex items-center gap-3">
                    <div>
                        <div class="font-medium">{{ $user->company->name }}</div>
                        <div class="text-xs text-gray-500">{{ $user->company->slug }}</div>
                    </div>
                    <a href="{{ route('admin.shops.show', $user->company->id) }}"
                       class="ml-auto text-orange-500 hover:text-orange-700 text-sm">
                        Voir la boutique <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        @endif

    </div>

    {{-- COLONNE DROITE --}}
    <div class="space-y-6">

        {{-- ACTIONS RAPIDES --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-bold text-gray-800 mb-4">
                <i class="fas fa-gavel text-orange-500"></i> Actions
            </h2>

            {{-- Vérifier / Dévérifier --}}
            <form method="POST" action="{{ route('admin.users.verify', $user->id) }}" class="mb-3">
                @csrf
                <button type="submit"
                        class="w-full {{ $user->is_verified ? 'bg-gray-500 hover:bg-gray-600' : 'bg-blue-500 hover:bg-blue-600' }} text-white font-bold py-3 rounded-lg">
                    @if($user->is_verified)
                        <i class="fas fa-user-times"></i> Dévérifier
                    @else
                        <i class="fas fa-user-check"></i> Vérifier
                    @endif
                </button>
            </form>

            {{-- Bannir / Débannir --}}
            @if($user->role !== 'admin')
                @if($user->status === 'banned')
                    <form method="POST" action="{{ route('admin.users.unban', $user->id) }}" class="mb-3">
                        @csrf
                        <button type="submit"
                                class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-lg">
                            <i class="fas fa-unlock"></i> Débannir
                        </button>
                    </form>
                @else
                    <button type="button"
                            onclick="document.getElementById('ban-form').classList.toggle('hidden')"
                            class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-lg mb-3">
                        <i class="fas fa-ban"></i> Bannir
                    </button>

                    <form id="ban-form" method="POST" action="{{ route('admin.users.ban', $user->id) }}"
                          class="hidden mb-3 space-y-2">
                        @csrf
                        <textarea name="reason" placeholder="Raison du bannissement..."
                                  class="w-full px-3 py-2 border rounded-lg text-sm" rows="2"></textarea>
                        <button type="submit"
                                class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg text-sm">
                            Confirmer le bannissement
                        </button>
                    </form>
                @endif
            @endif

            {{-- Changer le rôle --}}
            <form method="POST" action="{{ route('admin.users.change-role', $user->id) }}" class="mb-3">
                @csrf
                <label class="text-xs text-gray-500 mb-1 block">Changer le rôle</label>
                <select name="role" class="w-full px-3 py-2 border rounded-lg text-sm mb-2">
                    <option value="buyer" {{ $user->role === 'buyer' ? 'selected' : '' }}>🛒 Acheteur</option>
                    <option value="seller" {{ $user->role === 'seller' ? 'selected' : '' }}>🏪 Vendeur</option>
                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>👑 Admin</option>
                </select>
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2 rounded-lg text-sm">
                    Changer le rôle
                </button>
            </form>

            {{-- Supprimer --}}
            @if($user->role !== 'admin')
                <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}"
                      onsubmit="return confirm('⚠️ Supprimer définitivement « {{ $user->name }} » ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full bg-gray-800 hover:bg-black text-white font-bold py-3 rounded-lg">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </form>
            @endif
        </div>

        {{-- RÉINITIALISER MOT DE PASSE --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-bold text-gray-800 mb-4">
                <i class="fas fa-key text-orange-500"></i> Réinitialiser mot de passe
            </h2>

            <form method="POST" action="{{ route('admin.users.reset-password', $user->id) }}">
                @csrf
                <div class="mb-3">
                    <label class="text-xs text-gray-500 block mb-1">Nouveau mot de passe</label>
                    <input type="password" name="new_password" required minlength="8"
                           class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div class="mb-3">
                    <label class="text-xs text-gray-500 block mb-1">Confirmer</label>
                    <input type="password" name="new_password_confirmation" required minlength="8"
                           class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <button type="submit" class="w-full bg-purple-500 hover:bg-purple-600 text-white py-2 rounded-lg text-sm">
                    <i class="fas fa-key"></i> Réinitialiser
                </button>
            </form>
        </div>

    </div>

</div>

@endsection
