@extends('admin.layouts.app')

@section('title', 'Commandes')

@section('content')

{{-- STATS GLOBALES --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
        <div class="text-xs text-gray-500 uppercase">Revenus totaux</div>
        <div class="text-xl font-bold text-green-600">
            {{ number_format($stats['total_revenue'], 0, ',', ' ') }} CFA
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
        <div class="text-xs text-gray-500 uppercase">Aujourd'hui</div>
        <div class="text-xl font-bold text-gray-800">{{ $stats['today_orders'] }} commandes</div>
        <div class="text-xs text-green-600">{{ number_format($stats['today_revenue'], 0, ',', ' ') }} CFA</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-orange-500">
        <div class="text-xs text-gray-500 uppercase">Panier moyen</div>
        <div class="text-xl font-bold text-orange-600">
            {{ number_format($stats['avg_order'], 0, ',', ' ') }} CFA
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
        <div class="text-xs text-gray-500 uppercase">En attente</div>
        <div class="text-xl font-bold text-red-600">{{ $counts['pending'] }}</div>
    </div>
</div>

{{-- ONGLETS STATUT --}}
<div class="bg-white rounded-lg shadow mb-6">
    <div class="flex flex-wrap border-b">
        <a href="{{ route('admin.orders.index') }}"
           class="px-6 py-3 font-medium border-b-2 {{ !request('status') ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Toutes <span class="ml-1 text-xs bg-gray-200 px-2 py-0.5 rounded-full">{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'pending' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ⏳ En attente <span class="ml-1 text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full">{{ $counts['pending'] }}</span>
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'confirmed']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'confirmed' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ✅ Confirmées <span class="ml-1 text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">{{ $counts['confirmed'] }}</span>
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'shipped']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'shipped' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            🚚 Expédiées <span class="ml-1 text-xs bg-yellow-100 text-yellow-600 px-2 py-0.5 rounded-full">{{ $counts['shipped'] }}</span>
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'delivered']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'delivered' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            📦 Livrées <span class="ml-1 text-xs bg-green-100 text-green-600 px-2 py-0.5 rounded-full">{{ $counts['delivered'] }}</span>
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'cancelled' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ❌ Annulées <span class="ml-1 text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">{{ $counts['cancelled'] }}</span>
        </a>
    </div>

    {{-- RECHERCHE + FILTRES --}}
    <div class="p-4">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-wrap gap-2">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif

            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Rechercher par #ID ou nom client..."
                   class="flex-1 min-w-[200px] px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">

            <select name="payment" class="px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
                <option value="">Tous paiements</option>
                <option value="orange" {{ request('payment') === 'orange' ? 'selected' : '' }}>Orange Money</option>
                <option value="moov" {{ request('payment') === 'moov' ? 'selected' : '' }}>Moov Money</option>
                <option value="visa" {{ request('payment') === 'visa' ? 'selected' : '' }}>Visa</option>
                <option value="delivery" {{ request('payment') === 'delivery' ? 'selected' : '' }}>À la livraison</option>
            </select>

            <input type="date" name="from" value="{{ request('from') }}" class="px-4 py-2 border rounded-lg">
            <input type="date" name="to" value="{{ request('to') }}" class="px-4 py-2 border rounded-lg">

            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg">
                <i class="fas fa-search"></i> Filtrer
            </button>

            @if(request()->anyFilled(['search', 'payment', 'from', 'to', 'status']))
                <a href="{{ route('admin.orders.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
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
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">#ID</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Articles</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Paiement</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-mono font-semibold text-sm">
                        #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-800">{{ $order->user->name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $order->user->email ?? '—' }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $order->items->count() }} article(s)
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-orange-500">
                        {{ number_format($order->total, 0, ',', ' ') }} CFA
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ ucfirst($order->payment_method ?? '—') }}
                    </td>
                    <td class="px-6 py-4">
                        @if($order->status === 'pending')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-700">⏳ En attente</span>
                        @elseif($order->status === 'confirmed')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">✅ Confirmée</span>
                        @elseif($order->status === 'shipped')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">🚚 Expédiée</span>
                        @elseif($order->status === 'delivered')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">📦 Livrée</span>
                        @elseif($order->status === 'cancelled')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">❌ Annulée</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $order->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.orders.show', $order->id) }}"
                               class="text-blue-500 hover:text-blue-700" title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>

                            @if($order->status === 'pending')
                                <form method="POST" action="{{ route('admin.orders.confirm', $order->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-500 hover:text-green-700" title="Confirmer">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            @endif

                            @if($order->status === 'confirmed')
                                <form method="POST" action="{{ route('admin.orders.ship', $order->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-yellow-500 hover:text-yellow-700" title="Expédier">
                                        <i class="fas fa-truck"></i>
                                    </button>
                                </form>
                            @endif

                            @if($order->status === 'shipped')
                                <form method="POST" action="{{ route('admin.orders.deliver', $order->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-500 hover:text-green-700" title="Livrer">
                                        <i class="fas fa-box"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-shopping-bag text-4xl mb-3"></i>
                        <div>Aucune commande trouvée</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($orders->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $orders->appends(request()->query())->links() }}
        </div>
    @endif
</div>

@endsection
