@extends('admin.layouts.app')

@section('title', 'Signalements')

@section('content')

{{-- STATS --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs text-gray-500 uppercase">En attente</div>
                <div class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['pending'] }}</div>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full">
                <i class="fas fa-clock text-yellow-500"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs text-gray-500 uppercase">En cours</div>
                <div class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['reviewed'] }}</div>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-search text-blue-500"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs text-gray-500 uppercase">Résolus</div>
                <div class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['resolved'] }}</div>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <i class="fas fa-check-circle text-green-500"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs text-gray-500 uppercase">Rejetés</div>
                <div class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['rejected'] }}</div>
            </div>
            <div class="bg-red-100 p-3 rounded-full">
                <i class="fas fa-times-circle text-red-500"></i>
            </div>
        </div>
    </div>
</div>

{{-- ONGLETS --}}
<div class="bg-white rounded-lg shadow mb-6">
    <div class="flex flex-wrap border-b">
        <a href="{{ route('admin.reports.index') }}"
           class="px-6 py-3 font-medium border-b-2 {{ !request('status') && !request('type') ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Tous <span class="ml-1 text-xs bg-gray-200 px-2 py-0.5 rounded-full">{{ $stats['total'] }}</span>
        </a>
        <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'pending' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ⏳ En attente <span class="ml-1 text-xs bg-yellow-100 text-yellow-600 px-2 py-0.5 rounded-full">{{ $stats['pending'] }}</span>
        </a>
        <a href="{{ route('admin.reports.index', ['status' => 'resolved']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'resolved' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ✅ Résolus <span class="ml-1 text-xs bg-green-100 text-green-600 px-2 py-0.5 rounded-full">{{ $stats['resolved'] }}</span>
        </a>
        <a href="{{ route('admin.reports.index', ['status' => 'rejected']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'rejected' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ❌ Rejetés <span class="ml-1 text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">{{ $stats['rejected'] }}</span>
        </a>
        <a href="{{ route('admin.reports.blocked') }}"
           class="px-6 py-3 font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
            🚫 Bloqués <span class="ml-1 text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">{{ $stats['blocked_users'] }}</span>
        </a>
    </div>

    <div class="p-4 flex gap-2 flex-wrap">
        <a href="{{ route('admin.reports.index', array_merge(request()->query(), ['type' => 'product'])) }}"
           class="px-3 py-1 rounded-lg text-sm {{ request('type') === 'product' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            📦 Produits
        </a>
        <a href="{{ route('admin.reports.index', array_merge(request()->query(), ['type' => 'user'])) }}"
           class="px-3 py-1 rounded-lg text-sm {{ request('type') === 'user' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            👤 Utilisateurs
        </a>
        @if(request()->anyFilled(['status', 'type']))
            <a href="{{ route('admin.reports.index') }}"
               class="ml-auto px-3 py-1 rounded-lg text-sm bg-gray-200 hover:bg-gray-300 text-gray-700">
                <i class="fas fa-times"></i> Effacer les filtres
            </a>
        @endif
    </div>
</div>

{{-- LISTE --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">ID</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Raison</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Signalé par</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($reports as $report)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-mono text-gray-500">#{{ $report->id }}</td>
                    <td class="px-6 py-4">
                        @if(str_contains($report->reportable_type, 'Product'))
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                📦 Produit
                            </span>
                        @elseif(str_contains($report->reportable_type, 'User'))
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-700">
                                👤 Utilisateur
                            </span>
                        @endif
                        <div class="text-xs text-gray-500 mt-1">ID: {{ $report->reportable_id }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $reasonLabels = [
                                'spam' => ['📢 Spam', 'yellow'],
                                'abuse' => ['⚠️ Abus', 'orange'],
                                'inappropriate' => ['🚫 Inapproprié', 'red'],
                                'scam' => ['💸 Arnaque', 'red'],
                                'fake' => ['🎭 Faux', 'purple'],
                                'other' => ['❓ Autre', 'gray'],
                            ];
                            $reason = $reasonLabels[$report->reason] ?? [$report->reason, 'gray'];
                        @endphp
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-{{ $reason[1] }}-100 text-{{ $reason[1] }}-700">
                            {{ $reason[0] }}
                        </span>
                        @if($report->description)
                            <div class="text-xs text-gray-500 mt-1 truncate max-w-xs" title="{{ $report->description }}">
                                {{ $report->description }}
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-800">{{ $report->reporter->name ?? 'Inconnu' }}</div>
                        <div class="text-xs text-gray-500">{{ $report->reporter->email ?? '' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($report->status === 'pending')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">
                                ⏳ En attente
                            </span>
                        @elseif($report->status === 'resolved')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                ✅ Résolu
                            </span>
                        @elseif($report->status === 'rejected')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">
                                ❌ Rejeté
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $report->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.reports.show', $report->id) }}"
                           class="text-blue-500 hover:text-blue-700" title="Voir détails">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-flag text-4xl mb-3"></i>
                        <div>Aucun signalement trouvé</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($reports->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $reports->appends(request()->query())->links() }}
        </div>
    @endif
</div>

@endsection
