@extends('admin.layouts.app')

@section('title', 'Produits')

@section('content')

{{-- ONGLETS DE FILTRE --}}
<div class="bg-white rounded-lg shadow mb-6">
    <div class="flex flex-wrap border-b">
        <a href="{{ route('admin.products.index') }}"
           class="px-6 py-3 font-medium border-b-2 {{ !request()->anyFilled(['status', 'category', 'company', 'available']) ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Tous <span class="ml-1 text-xs bg-gray-200 px-2 py-0.5 rounded-full">{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.products.index', ['status' => 'pending']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'pending' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ⏳ En attente <span class="ml-1 text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full">{{ $counts['pending'] }}</span>
        </a>
        <a href="{{ route('admin.products.index', ['status' => 'approved']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'approved' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ✅ Approuvés <span class="ml-1 text-xs bg-green-100 text-green-600 px-2 py-0.5 rounded-full">{{ $counts['approved'] }}</span>
        </a>
        <a href="{{ route('admin.products.index', ['status' => 'rejected']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('status') === 'rejected' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            ❌ Refusés <span class="ml-1 text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">{{ $counts['rejected'] }}</span>
        </a>
        <a href="{{ route('admin.products.index', ['available' => 'no']) }}"
           class="px-6 py-3 font-medium border-b-2 {{ request('available') === 'no' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            🚫 Indisponibles
        </a>
    </div>

    {{-- RECHERCHE + FILTRES --}}
    <div class="p-4">
        <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-wrap gap-2">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            @if(request('available'))<input type="hidden" name="available" value="{{ request('available') }}">@endif

            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Rechercher par nom, SKU ou marque..."
                   class="flex-1 min-w-[200px] px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">

            <select name="category" class="px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
                <option value="">Toutes catégories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg">
                <i class="fas fa-search"></i> Rechercher
            </button>

            @if(request()->anyFilled(['search', 'category', 'status', 'available']))
                <a href="{{ route('admin.products.index') }}"
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
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Produit</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Boutique</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Catégorie</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Prix</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Stock</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($products as $product)
                @php
                    $imageUrl = null;
                    if ($product->main_image) {
                        $imageUrl = str_starts_with($product->main_image, 'http')
                            ? $product->main_image
                            : asset('storage/' . ltrim($product->main_image, '/'));
                    }
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <i class="fas fa-image text-gray-400 hidden"></i>
                                @else
                                    <i class="fas fa-image text-gray-400"></i>
                                @endif
                            </div>
                            <div>
                                <div class="font-semibold text-gray-800 flex items-center gap-2">
                                    {{ Str::limit($product->name, 40) }}
                                    @if($product->is_featured ?? false)
                                        <i class="fas fa-star text-yellow-500 text-xs" title="Mis en avant"></i>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">SKU: {{ $product->sku ?? '—' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $product->company->name ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $product->category->name ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-orange-500">
                        {{ number_format($product->price, 0, ',', ' ') }} CFA
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($product->stock > 0)
                            <span class="text-green-600">{{ $product->stock }}</span>
                        @else
                            <span class="text-red-500">Épuisé</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($product->status === 'pending')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-700">⏳ En attente</span>
                        @elseif($product->status === 'approved')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">✅ Approuvé</span>
                        @elseif($product->status === 'rejected')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">❌ Refusé</span>
                        @endif

                        @if(!$product->is_available)
                            <span class="inline-flex items-center ml-1 px-2 py-1 text-xs font-medium rounded-full bg-gray-200 text-gray-700">🚫 Indispo</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end items-center gap-3">
                            <a href="{{ route('admin.products.show', $product->id) }}"
                               class="text-blue-500 hover:text-blue-700" title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>

                            @if($product->status !== 'approved')
                                <form method="POST" action="{{ route('admin.products.approve', $product->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-500 hover:text-green-700" title="Approuver">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            @endif

                            @if($product->is_available)
                                <form method="POST" action="{{ route('admin.products.disable', $product->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-yellow-500 hover:text-yellow-700" title="Désactiver">
                                        <i class="fas fa-eye-slash"></i>
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.products.enable', $product->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-blue-500 hover:text-blue-700" title="Activer">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}" class="inline"
                                  onsubmit="return confirm('⚠️ Supprimer « {{ $product->name }} » ?');">
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
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-box text-4xl mb-3"></i>
                        <div>Aucun produit trouvé</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($products->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $products->appends(request()->query())->links() }}
        </div>
    @endif
</div>

@endsection
