@extends('admin.layouts.app')

@section('title', 'Statistiques')

@section('content')

{{-- FILTRE PÉRIODE --}}
<div class="bg-white rounded-lg shadow p-4 mb-6 flex flex-wrap justify-between items-center gap-3">
    <div class="text-sm font-semibold text-gray-700">
        <i class="fas fa-calendar text-orange-500"></i> Période :
    </div>
    <div class="flex flex-wrap gap-2">
        @foreach(['7' => '7 jours', '30' => '30 jours', '90' => '90 jours', '365' => '1 an', 'all' => 'Tout'] as $key => $label)
            <a href="{{ route('admin.stats.index', ['period' => $key]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium {{ $period === $key ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

{{-- 4 GRANDES CARTES --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Utilisateurs --}}
    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-blue-500">
        <div class="flex justify-between items-start">
            <div>
                <div class="text-xs text-gray-500 uppercase font-semibold">Utilisateurs</div>
                <div class="text-3xl font-bold text-gray-800 mt-1">{{ number_format($globalStats['users']['total']) }}</div>
            </div>
            <div class="bg-blue-100 p-2 rounded-lg">
                <i class="fas fa-users text-blue-500"></i>
            </div>
        </div>
        <div class="text-sm text-green-600 mt-2">
            <i class="fas fa-arrow-up"></i> +{{ $globalStats['users']['new_period'] }} cette période
        </div>
        <div class="text-xs text-gray-500 mt-1">
            {{ $globalStats['users']['buyers'] }} acheteurs · {{ $globalStats['users']['sellers'] }} vendeurs · {{ $globalStats['users']['verified'] }} vérifiés
        </div>
    </div>

    {{-- Boutiques --}}
    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-purple-500">
        <div class="flex justify-between items-start">
            <div>
                <div class="text-xs text-gray-500 uppercase font-semibold">Boutiques</div>
                <div class="text-3xl font-bold text-gray-800 mt-1">{{ number_format($globalStats['shops']['total']) }}</div>
            </div>
            <div class="bg-purple-100 p-2 rounded-lg">
                <i class="fas fa-store text-purple-500"></i>
            </div>
        </div>
        <div class="text-sm text-green-600 mt-2">
            <i class="fas fa-arrow-up"></i> +{{ $globalStats['shops']['new_period'] }} cette période
        </div>
        <div class="text-xs text-gray-500 mt-1">
            {{ $globalStats['shops']['approved'] }} validées
            @if($globalStats['shops']['pending'] > 0)
                · <span class="text-orange-500 font-semibold">{{ $globalStats['shops']['pending'] }} en attente</span>
            @endif
        </div>
    </div>

    {{-- Produits --}}
    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-yellow-500">
        <div class="flex justify-between items-start">
            <div>
                <div class="text-xs text-gray-500 uppercase font-semibold">Produits</div>
                <div class="text-3xl font-bold text-gray-800 mt-1">{{ number_format($globalStats['products']['total']) }}</div>
            </div>
            <div class="bg-yellow-100 p-2 rounded-lg">
                <i class="fas fa-box text-yellow-500"></i>
            </div>
        </div>
        <div class="text-sm text-green-600 mt-2">
            <i class="fas fa-arrow-up"></i> +{{ $globalStats['products']['new_period'] }} cette période
        </div>
        <div class="text-xs text-gray-500 mt-1">
            {{ $globalStats['products']['approved'] }} approuvés
            @if($globalStats['products']['pending'] > 0)
                · <span class="text-orange-500 font-semibold">{{ $globalStats['products']['pending'] }} en attente</span>
            @endif
        </div>
    </div>

    {{-- Revenus --}}
    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-green-500">
        <div class="flex justify-between items-start">
            <div>
                <div class="text-xs text-gray-500 uppercase font-semibold">Revenus</div>
                <div class="text-2xl font-bold text-green-600 mt-1">
                    {{ number_format($globalStats['revenue']['total'], 0, ',', ' ') }}
                </div>
                <div class="text-xs text-gray-500">CFA</div>
            </div>
            <div class="bg-green-100 p-2 rounded-lg">
                <i class="fas fa-coins text-green-500"></i>
            </div>
        </div>
        <div class="text-sm text-green-600 mt-2">
            +{{ number_format($globalStats['revenue']['period'], 0, ',', ' ') }} CFA
        </div>
        <div class="text-xs text-gray-500 mt-1">
            Panier moyen : {{ number_format($globalStats['revenue']['avg_order'], 0, ',', ' ') }} CFA
        </div>
    </div>

</div>

{{-- 3 PETITES CARTES STATS COMMANDES --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-500 uppercase">Commandes totales</div>
        <div class="text-xl font-bold text-gray-800">{{ number_format($globalStats['orders']['total']) }}</div>
        <div class="text-xs text-green-600">+{{ $globalStats['orders']['new_period'] }} cette période</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-500 uppercase">Livrées</div>
        <div class="text-xl font-bold text-green-600">{{ $globalStats['orders']['delivered'] }}</div>
        @php
            $rate = $globalStats['orders']['total'] > 0 ? round(($globalStats['orders']['delivered'] / $globalStats['orders']['total']) * 100, 1) : 0;
        @endphp
        <div class="text-xs text-gray-500">Taux : {{ $rate }}%</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-500 uppercase">En attente</div>
        <div class="text-xl font-bold text-orange-500">{{ $globalStats['orders']['pending'] }}</div>
        <div class="text-xs text-gray-500">{{ number_format($globalStats['revenue']['pending'], 0, ',', ' ') }} CFA en jeu</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-500 uppercase">Annulées</div>
        <div class="text-xl font-bold text-red-500">{{ $globalStats['orders']['cancelled'] }}</div>
        <div class="text-xs text-gray-500">Taux : {{ $globalStats['orders']['total'] > 0 ? round(($globalStats['orders']['cancelled'] / $globalStats['orders']['total']) * 100, 1) : 0 }}%</div>
    </div>
</div>

{{-- GRAPHIQUES PRINCIPAUX --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Commandes par jour --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-line text-orange-500"></i> Commandes par jour
        </h2>
        <div style="height: 280px;">
            <canvas id="ordersChart"></canvas>
        </div>
    </div>

    {{-- Revenus par jour --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-bar text-orange-500"></i> Revenus par jour (CFA)
        </h2>
        <div style="height: 280px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

</div>

{{-- DEUXIÈME LIGNE GRAPHIQUES --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- Répartition rôles --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="font-bold text-gray-800 mb-4">
            <i class="fas fa-users text-orange-500"></i> Rôles utilisateurs
        </h2>
        <div style="height: 250px;">
            <canvas id="rolesChart"></canvas>
        </div>
    </div>

    {{-- Statut commandes --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="font-bold text-gray-800 mb-4">
            <i class="fas fa-shopping-bag text-orange-500"></i> Statut commandes
        </h2>
        <div style="height: 250px;">
            <canvas id="ordersStatusChart"></canvas>
        </div>
    </div>

    {{-- Top catégories --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="font-bold text-gray-800 mb-4">
            <i class="fas fa-tags text-orange-500"></i> Top catégories
        </h2>
        <div style="height: 250px;">
            <canvas id="categoriesChart"></canvas>
        </div>
    </div>

</div>

{{-- NOUVEAUX UTILISATEURS PAR MOIS --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="font-bold text-gray-800 mb-4">
        <i class="fas fa-user-plus text-orange-500"></i> Nouveaux utilisateurs (6 derniers mois)
    </h2>
    <div style="height: 200px;">
        <canvas id="usersMonthChart"></canvas>
    </div>
</div>

{{-- TOP VENDEURS / PRODUITS --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Top 5 vendeurs --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="font-bold text-gray-800">
                <i class="fas fa-trophy text-orange-500"></i> Top 5 vendeurs
            </h2>
        </div>
        <div class="p-4">
            @forelse($topSellers as $index => $seller)
                <div class="flex items-center gap-3 p-3 border-b last:border-0">
                    <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center font-bold text-orange-500">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-sm">{{ $seller->name }}</div>
                        <div class="text-xs text-gray-500">{{ $seller->company_name }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-sm text-green-600">
                            {{ number_format($seller->revenue, 0, ',', ' ') }} CFA
                        </div>
                        <div class="text-xs text-gray-500">{{ $seller->orders_count }} commandes</div>
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-400 py-6">Aucune donnée</div>
            @endforelse
        </div>
    </div>

    {{-- Top 5 produits --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="font-bold text-gray-800">
                <i class="fas fa-star text-orange-500"></i> Top 5 produits
            </h2>
        </div>
        <div class="p-4">
            @forelse($topProducts as $index => $product)
                <div class="flex items-center gap-3 p-3 border-b last:border-0">
                    <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center font-bold text-yellow-600">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-sm truncate">{{ $product->name }}</div>
                        <div class="text-xs text-gray-500">{{ $product->company->name ?? '—' }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-sm text-orange-500">
                            {{ number_format($product->price, 0, ',', ' ') }} CFA
                        </div>
                        <div class="text-xs text-gray-500">{{ $product->sales_count ?? 0 }} ventes</div>
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-400 py-6">Aucune donnée</div>
            @endforelse
        </div>
    </div>

</div>

{{-- TOP VILLES --}}
@if($topCities->count() > 0)
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="font-bold text-gray-800 mb-4">
        <i class="fas fa-map-marker-alt text-orange-500"></i> Top villes
    </h2>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        @foreach($topCities as $city)
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <div class="font-bold text-lg text-gray-800">{{ $city->count }}</div>
                <div class="text-xs text-gray-500">{{ $city->city }}</div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- CHART.JS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Configuration globale Chart.js
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#6b7280';

    // ─── 1. COMMANDES PAR JOUR ───
    new Chart(document.getElementById('ordersChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($ordersByDay->pluck('date')) !!},
            datasets: [{
                label: 'Commandes',
                data: {!! json_encode($ordersByDay->pluck('count')) !!},
                borderColor: '#f97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointBackgroundColor: '#f97316',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });

    // ─── 2. REVENUS PAR JOUR ───
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($ordersByDay->pluck('date')) !!},
            datasets: [{
                label: 'Revenus',
                data: {!! json_encode($ordersByDay->pluck('revenue')) !!},
                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                borderColor: '#22c55e',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return (value / 1000).toFixed(0) + 'K';
                        }
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });

    // ─── 3. RÔLES UTILISATEURS ───
    new Chart(document.getElementById('rolesChart'), {
        type: 'doughnut',
        data: {
            labels: ['Acheteurs', 'Vendeurs', 'Admins'],
            datasets: [{
                data: [
                    {{ $rolesDistribution['buyers'] }},
                    {{ $rolesDistribution['sellers'] }},
                    {{ $rolesDistribution['admins'] }}
                ],
                backgroundColor: ['#3b82f6', '#a855f7', '#ef4444'],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // ─── 4. STATUT COMMANDES ───
    new Chart(document.getElementById('ordersStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['En attente', 'Confirmées', 'Expédiées', 'Livrées', 'Annulées'],
            datasets: [{
                data: [
                    {{ $ordersDistribution['pending'] }},
                    {{ $ordersDistribution['confirmed'] }},
                    {{ $ordersDistribution['shipped'] }},
                    {{ $ordersDistribution['delivered'] }},
                    {{ $ordersDistribution['cancelled'] }}
                ],
                backgroundColor: ['#f97316', '#3b82f6', '#eab308', '#22c55e', '#ef4444'],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // ─── 5. TOP CATÉGORIES ───
    new Chart(document.getElementById('categoriesChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($topCategories->pluck('name')) !!},
            datasets: [{
                label: 'Produits',
                data: {!! json_encode($topCategories->pluck('products_count')) !!},
                backgroundColor: [
                    '#f97316', '#3b82f6', '#22c55e', '#eab308',
                    '#a855f7', '#ec4899', '#06b6d4', '#64748b'
                ],
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y', // ← Barres horizontales
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 } },
                y: { grid: { display: false } }
            }
        }
    });

    // ─── 6. UTILISATEURS PAR MOIS ───
    new Chart(document.getElementById('usersMonthChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($usersByMonth->pluck('month')) !!},
            datasets: [{
                label: 'Nouveaux utilisateurs',
                data: {!! json_encode($usersByMonth->pluck('count')) !!},
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

@endsection
