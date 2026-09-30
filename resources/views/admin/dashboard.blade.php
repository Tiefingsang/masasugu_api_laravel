@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
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

    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-orange-500">
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

    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500">
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

    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
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

<div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl shadow p-6 text-white">
    <h2 class="text-2xl font-bold mb-2">🎉 Bienvenue, {{ auth()->user()->name }} !</h2>
    <p class="text-orange-100">Ton dashboard admin est opérationnel.</p>
</div>

@endsection
