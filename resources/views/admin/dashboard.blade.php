@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- STATS PRINCIPALES --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-gray-500">Utilisateurs</div>
                <div class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['users']['total'] }}</div>
                <div class="text-xs text-gray-500 mt-2">
                    <span class="text-blue-500">{{ $stats['users']['buyers'] }}</span> acheteurs ·
                    <span class="text-purple-500">{{ $stats['users']['sellers'] }}</span> vendeurs
                </div>
            </div>
            <div class="bg-blue-100 p-4 rounded-full">
                <i class="fas fa-users text-blue-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-orange-500 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-gray-500">Boutiques</div>
                <div class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['shops']['total'] }}</div>
                <div class="text-xs mt-2">
                    @if($stats['shops']['pending'] > 0)
                        <span class="text-orange-500 font-semibold">
                            <i class="fas fa-clock"></i> {{ $stats['shops']['pending'] }} en attente
                        </span>
                    @else
                        <span class="text-green-500"><i class="fas fa-check"></i> Toutes validées</span>
                    @endif
                </div>
            </div>
            <div class="bg-orange-100 p-4 rounded-full">
                <i class="fas fa-store text-orange-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-gray-500">Produits</div>
                <div class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['products']['total'] }}</div>
                <div class="text-xs mt-2">
                    @if($stats['products']['pending'] > 0)
                        <span class="text-yellow-500 font-semibold">
                            <i class="fas fa-clock"></i> {{ $stats['products']['pending'] }} en attente
                        </span>
                    @else
                        <span class="text-green-500"><i class="fas fa-check"></i> Tous approuvés</span>
                    @endif
                </div>
            </div>
            <div class="bg-yellow-100 p-4 rounded-full">
                <i class="fas fa-box text-yellow-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-green-500 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-gray-500">Commandes</div>
                <div class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['orders']['total'] }}</div>
                <div class="text-xs text-green-500 mt-2 font-semibold">
                    <i class="fas fa-coins"></i>
                    {{ number_format($stats['orders']['revenue'], 0, ',', ' ') }} CFA
                </div>
            </div>
            <div class="bg-green-100 p-4 rounded-full">
                <i class="fas fa-shopping-bag text-green-500 text-2xl"></i>
            </div>
        </div>
    </div>

</div>

{{-- SIGNALEMENTS EN ATTENTE --}}
@php
    $pendingReports = \App\Models\Report::where('status', 'pending')->count();
    $recentReports = \App\Models\Report::with('reporter:id,name')
        ->where('status', 'pending')
        ->latest()
        ->take(5)
        ->get();
@endphp

@if($pendingReports > 0)
<div class="bg-white rounded-xl shadow p-6 mb-8 border-l-4 border-red-500">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-flag text-red-500 mr-3"></i>
            Signalements en attente
            <span class="ml-3 bg-red-500 text-white text-xs px-2 py-1 rounded-full">{{ $pendingReports }}</span>
        </h2>
        <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}"
           class="text-orange-500 hover:text-orange-700 text-sm font-medium">
            Voir tout <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <div class="space-y-2">
        @foreach($recentReports as $report)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-flag text-red-500"></i>
                    </div>
                    <div>
                        <div class="font-medium text-gray-800">
                            Signalement #{{ $report->id }}
                            <span class="text-xs text-gray-500 ml-2">
                                ({{ str_contains($report->reportable_type, 'Product') ? 'Produit' : 'Utilisateur' }})
                            </span>
                        </div>
                        <div class="text-xs text-gray-500">
                            Par {{ $report->reporter->name ?? 'Inconnu' }} · {{ $report->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.reports.show', $report->id) }}"
                   class="text-blue-500 hover:text-blue-700 text-sm">
                    Traiter <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- ACTIONS RAPIDES --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <a href="{{ route('admin.users.index', ['verified' => 'no']) }}"
       class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition flex items-center gap-4">
        <div class="bg-blue-100 p-4 rounded-full">
            <i class="fas fa-user-check text-blue-500 text-2xl"></i>
        </div>
        <div>
            <div class="font-bold text-gray-800">Vérifier utilisateurs</div>
            <div class="text-xs text-gray-500">{{ $stats['users']['pending'] ?? 0 }} en attente</div>
        </div>
    </a>

    <a href="{{ route('admin.shops.index') }}"
       class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition flex items-center gap-4">
        <div class="bg-orange-100 p-4 rounded-full">
            <i class="fas fa-store text-orange-500 text-2xl"></i>
        </div>
        <div>
            <div class="font-bold text-gray-800">Valider boutiques</div>
            <div class="text-xs text-gray-500">{{ $stats['shops']['pending'] }} en attente</div>
        </div>
    </a>

    <a href="{{ route('admin.products.index') }}"
       class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition flex items-center gap-4">
        <div class="bg-yellow-100 p-4 rounded-full">
            <i class="fas fa-box text-yellow-500 text-2xl"></i>
        </div>
        <div>
            <div class="font-bold text-gray-800">Approuver produits</div>
            <div class="text-xs text-gray-500">{{ $stats['products']['pending'] }} en attente</div>
        </div>
    </a>
</div>

{{-- BIENVENUE --}}
<div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl shadow p-6 text-white">
    <h2 class="text-2xl font-bold mb-2">🎉 Bienvenue, {{ auth()->user()->name }} !</h2>
    <p class="text-orange-100">Ton dashboard admin est opérationnel.</p>
</div>

@endsection
