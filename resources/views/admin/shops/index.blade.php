@extends('admin.layouts.app')

@section('title', 'Boutiques')

@section('content')

{{-- ONGLETS DE FILTRE --}}
<div class="bg-white rounded-lg shadow mb-6">
    <div class="flex flex-wrap border-b">
        <a href="{{ route('admin.shops.index') }}"
           class="px-6 py-3 font-medium border-b-2 {{ !request('status') ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Toutes <span class="ml-1 text-xs bg-gray-200 px-2 py-0.5 rounded-full">{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.shops.index', ['status' => 'pending']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'pending' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ⏳ En attente <span class="ml-1 text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full">{{ $counts['pending'] }}</span>
        </a>
        <a href="{{ route('admin.shops.index', ['status' => 'approved']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'approved' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ✅ Approuvées <span class="ml-1 text-xs bg-green-100 text-green-600 px-2 py-0.5 rounded-full">{{ $counts['approved'] }}</span>
        </a>
        <a href="{{ route('admin.shops.index', ['status' => 'rejected']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'rejected' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ❌ Refusées <span class="ml-1 text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">{{ $counts['rejected'] }}</span>
        </a>
        <a href="{{ route('admin.shops.index', ['active' => 'no']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('active') === 'no' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ⏸️ Suspendues <span class="ml-1 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $counts['suspended'] }}</span>
        </a>
    </div>

    {{-- RECHERCHE --}}
    <div class="p-4">
        <form method="GET" action="{{ route('admin.shops.index') }}" class="flex gap-2">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if(request('active'))
                <input type="hidden" name="active" value="{{ request('active') }}">
            @endif
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Rechercher par nom, email ou téléphone..."
                   class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg">
                <i class="fas fa-search"></i> Rechercher
            </button>
            @if(request('search') || request('status') || request('active'))
                <a href="{{ route('admin.shops.index') }}"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                    <i class="fas fa-times"></i> Effacer
                </a>
            @endif
        </form>
    </div>
</div>

{{-- LISTE --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Boutique</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Vendeur</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Ville</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($shops as $shop)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if($shop->logo)
                                <img src="{{ str_starts_with($shop->logo, 'http') ? $shop->logo : 'https://api.masasugu.com/storage/' . ltrim($shop->logo, '/') }}"
                                     class="w-10 h-10 rounded-full object-cover bg-gray-200"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-10 h-10 rounded-full bg-orange-100 items-center justify-center hidden">
                                    <i class="fas fa-store text-orange-500"></i>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                                    <i class="fas fa-store text-orange-500"></i>
                                </div>
                            @endif
                            <div>
                                <div class="font-semibold text-gray-800 flex items-center gap-2">
                                    {{ $shop->name }}
                                    @if($shop->is_verified)
                                        <i class="fas fa-check-circle text-blue-500 text-xs" title="Vérifiée"></i>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">{{ $shop->slug }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-800">{{ $shop->user->name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $shop->contact_phone ?? '—' }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $shop->city ?? $shop->address ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $shop->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4">
                        @if($shop->status === 'pending')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-700">
                                ⏳ En attente
                            </span>
                        @elseif($shop->status === 'approved')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                ✅ Approuvée
                            </span>
                        @elseif($shop->status === 'rejected')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">
                                ❌ Refusée
                            </span>
                        @endif

                        @if(!$shop->is_active)
                            <span class="inline-flex items-center ml-1 px-2 py-1 text-xs font-medium rounded-full bg-gray-200 text-gray-700">
                                ⏸️ Suspendue
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end items-center gap-3">
                            {{-- Voir --}}
                            <a href="{{ route('admin.shops.show', $shop->id) }}"
                               class="text-blue-500 hover:text-blue-700" title="Voir détails">
                                <i class="fas fa-eye"></i>
                            </a>

                            {{-- Approuver --}}
                            @if($shop->status !== 'approved')
                                <form method="POST" action="{{ route('admin.shops.approve', $shop->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-500 hover:text-green-700" title="Approuver">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            @endif

                            {{-- Suspendre / Réactiver --}}
                            @if($shop->is_active)
                                <form method="POST" action="{{ route('admin.shops.suspend', $shop->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-yellow-500 hover:text-yellow-700" title="Suspendre">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.shops.activate', $shop->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-blue-500 hover:text-blue-700" title="Réactiver">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </form>
                            @endif

                            {{-- Supprimer --}}
                            <form method="POST" action="{{ route('admin.shops.destroy', $shop->id) }}" class="inline"
                                  onsubmit="return confirm('⚠️ Supprimer définitivement la boutique « {{ $shop->name }} » ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-store text-4xl mb-3"></i>
                        <div>Aucune boutique trouvée</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- PAGINATION --}}
    @if($shops->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $shops->appends(request()->query())->links() }}
        </div>
    @endif
</div>

@endsection
