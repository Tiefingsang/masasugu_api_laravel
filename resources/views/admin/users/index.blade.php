@extends('admin.layouts.app')

@section('title', 'Utilisateurs')

@section('content')

{{-- ONGLETS DE FILTRE --}}
<div class="bg-white rounded-lg shadow mb-6">
    <div class="flex flex-wrap border-b">
        <a href="{{ route('admin.users.index') }}"
           class="px-6 py-3 font-medium border-b-2 {{ !request('role') && !request('status') && !request('verified') ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Tous <span class="ml-1 text-xs bg-gray-200 px-2 py-0.5 rounded-full">{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'buyer']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('role') === 'buyer' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            🛒 Acheteurs <span class="ml-1 text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">{{ $counts['buyers'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'seller']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('role') === 'seller' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            🏪 Vendeurs <span class="ml-1 text-xs bg-purple-100 text-purple-600 px-2 py-0.5 rounded-full">{{ $counts['sellers'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['verified' => 'no']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('verified') === 'no' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ⏳ Non vérifiés <span class="ml-1 text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full">{{ $counts['pending'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['status' => 'banned']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'banned' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            🚫 Bannis <span class="ml-1 text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">{{ $counts['banned'] }}</span>
        </a>
    </div>

    {{-- RECHERCHE --}}
    <div class="p-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-2">
            @if(request('role'))<input type="hidden" name="role" value="{{ request('role') }}">@endif
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            @if(request('verified'))<input type="hidden" name="verified" value="{{ request('verified') }}">@endif
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Rechercher par nom, email ou téléphone..."
                   class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg">
                <i class="fas fa-search"></i> Rechercher
            </button>
            @if(request()->anyFilled(['search', 'role', 'status', 'verified']))
                <a href="{{ route('admin.users.index') }}"
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
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Utilisateur</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Rôle</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Contact</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Inscription</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($users as $user)
                @php
                    $avatarUrl = null;
                    if ($user->avatar) {
                        $avatarUrl = str_starts_with($user->avatar, 'http')
                            ? $user->avatar
                            : asset('storage/' . ltrim($user->avatar, '/'));
                    }
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if($avatarUrl)
                                <img src="{{ $avatarUrl }}"
                                     class="w-10 h-10 rounded-full object-cover bg-gray-200"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-10 h-10 rounded-full bg-orange-100 items-center justify-center hidden">
                                    <span class="text-orange-500 font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                                    <span class="text-orange-500 font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                </div>
                            @endif
                            <div>
                                <div class="font-semibold text-gray-800 flex items-center gap-2">
                                    {{ $user->name }}
                                    @if($user->is_verified)
                                        <i class="fas fa-check-circle text-blue-500 text-xs" title="Vérifié"></i>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($user->role === 'admin')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">
                                👑 Admin
                            </span>
                        @elseif($user->role === 'seller')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-700">
                                🏪 Vendeur
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                🛒 Acheteur
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $user->phone ?? '—' }}
                    </td>
                    <td class="px-6 py-4">
                        @if($user->status === 'banned')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">
                                🚫 Banni
                            </span>
                        @elseif($user->status === 'inactive')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-200 text-gray-700">
                                ⏸️ Inactif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                ✅ Actif
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $user->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end items-center gap-3">
                            <a href="{{ route('admin.users.show', $user->id) }}"
                               class="text-blue-500 hover:text-blue-700" title="Voir détails">
                                <i class="fas fa-eye"></i>
                            </a>

                            @if(!$user->is_verified)
                                <form method="POST" action="{{ route('admin.users.verify', $user->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-blue-500 hover:text-blue-700" title="Vérifier">
                                        <i class="fas fa-user-check"></i>
                                    </button>
                                </form>
                            @endif

                            @if($user->status === 'banned')
                                <form method="POST" action="{{ route('admin.users.unban', $user->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-500 hover:text-green-700" title="Débannir">
                                        <i class="fas fa-unlock"></i>
                                    </button>
                                </form>
                            @elseif($user->role !== 'admin')
                                <form method="POST" action="{{ route('admin.users.ban', $user->id) }}" class="inline"
                                      onsubmit="return confirm('🚫 Bannir « {{ $user->name }} » ?');">
                                    @csrf
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Bannir">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-users text-4xl mb-3"></i>
                        <div>Aucun utilisateur trouvé</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($users->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $users->appends(request()->query())->links() }}
        </div>
    @endif
</div>

@endsection
