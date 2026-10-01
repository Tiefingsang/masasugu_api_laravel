@extends('admin.layouts.app')

@section('title', 'Produit : ' . $product->name)

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.products.index') }}" class="text-orange-500 hover:text-orange-700">
        <i class="fas fa-arrow-left"></i> Retour aux produits
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- COLONNE GAUCHE --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- IMAGE + TITRE --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start gap-6">
                @php
                    $imageUrl = null;
                    if ($product->main_image) {
                        $imageUrl = str_starts_with($product->main_image, 'http')
                            ? $product->main_image
                            : asset('storage/' . ltrim($product->main_image, '/'));
                    }
                @endphp
                <div class="w-32 h-32 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" class="w-full h-full object-cover" onerror="this.style.display='none';">
                    @else
                        <i class="fas fa-image text-gray-400 text-4xl"></i>
                    @endif
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-800">{{ $product->name }}</h1>
                    <p class="text-sm text-gray-500 mt-1">SKU: {{ $product->sku ?? '—' }}</p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($product->status === 'pending')
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-orange-100 text-orange-700">⏳ En attente</span>
                        @elseif($product->status === 'approved')
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-700">✅ Approuvé</span>
                        @elseif($product->status === 'rejected')
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-red-100 text-red-700">❌ Refusé</span>
                        @endif

                        @if($product->is_available)
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-700">🟢 Disponible</span>
                        @else
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-gray-200 text-gray-700">🚫 Indisponible</span>
                        @endif

                        @if($product->is_featured ?? false)
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-yellow-100 text-yellow-700">⭐ Mis en avant</span>
                        @endif
                    </div>

                    <div class="mt-4">
                        <div class="text-2xl font-bold text-orange-500">
                            {{ number_format($product->price, 0, ',', ' ') }} {{ $product->currency ?? 'CFA' }}
                        </div>
                        @if($product->discount_price > 0)
                            <div class="text-sm text-gray-400 line-through">
                                {{ number_format($product->discount_price, 0, ',', ' ') }} CFA
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- INFORMATIONS --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-info-circle text-orange-500"></i> Informations</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-xs text-gray-500 uppercase">Catégorie</div>
                    <div class="text-sm font-medium">{{ $product->category->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Marque</div>
                    <div class="text-sm font-medium">{{ $product->brand ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Stock</div>
                    <div class="text-sm font-medium {{ $product->stock > 0 ? 'text-green-600' : 'text-red-500' }}">
                        {{ $product->stock }} unités
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Vues</div>
                    <div class="text-sm font-medium">{{ $product->views ?? 0 }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Ventes</div>
                    <div class="text-sm font-medium">{{ $product->sales_count ?? 0 }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Note</div>
                    <div class="text-sm font-medium">⭐ {{ $product->rating ?? 0 }}/5</div>
                </div>
            </div>

            @if($product->description)
                <div class="mt-6 pt-6 border-t">
                    <div class="text-xs text-gray-500 uppercase mb-2">Description</div>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $product->description }}</p>
                </div>
            @endif
        </div>

        {{-- IMAGES SUPPLÉMENTAIRES --}}
        @if($product->images && $product->images->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="font-bold text-gray-800 mb-4">
                    <i class="fas fa-images text-orange-500"></i>
                    Images supplémentaires ({{ $product->images->count() }})
                </h2>
                <div class="grid grid-cols-3 md:grid-cols-4 gap-3">
                    @foreach($product->images as $img)
                        @php
                            $imgUrl = str_starts_with($img->image_path, 'http')
                                ? $img->image_path
                                : asset('storage/' . ltrim($img->image_path, '/'));
                        @endphp
                        <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                            <img src="{{ $imgUrl }}" class="w-full h-full object-cover" onerror="this.style.display='none';">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>

    {{-- COLONNE DROITE --}}
    <div class="space-y-6">

        {{-- BOUTIQUE --}}
        @if($product->company)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-store text-orange-500"></i> Boutique</h2>
                <div class="text-sm">
                    <div class="font-medium">{{ $product->company->name }}</div>
                    <div class="text-xs text-gray-500">{{ $product->company->user->name ?? '—' }}</div>
                    <a href="{{ route('admin.shops.show', $product->company->id) }}"
                       class="inline-block mt-3 text-orange-500 hover:text-orange-700 text-sm">
                        Voir la boutique <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        @endif

        {{-- ACTIONS --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-gavel text-orange-500"></i> Actions</h2>

            {{-- Approuver --}}
            @if($product->status !== 'approved')
                <form method="POST" action="{{ route('admin.products.approve', $product->id) }}" class="mb-3">
                    @csrf
                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-lg">
                        <i class="fas fa-check"></i> Approuver
                    </button>
                </form>
            @endif

            {{-- Refuser --}}
            @if($product->status !== 'rejected')
                <button type="button"
                        onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                        class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-lg mb-3">
                    <i class="fas fa-times"></i> Refuser
                </button>
                <form id="reject-form" method="POST" action="{{ route('admin.products.reject', $product->id) }}"
                      class="hidden mb-3 space-y-2">
                    @csrf
                    <textarea name="reason" required placeholder="Raison du refus..."
                              class="w-full px-3 py-2 border rounded-lg text-sm" rows="3"></textarea>
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg text-sm">
                        Confirmer le refus
                    </button>
                </form>
            @endif

            {{-- Activer / Désactiver --}}
            @if($product->is_available)
                <form method="POST" action="{{ route('admin.products.disable', $product->id) }}" class="mb-3">
                    @csrf
                    <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 rounded-lg">
                        <i class="fas fa-eye-slash"></i> Désactiver
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.products.enable', $product->id) }}" class="mb-3">
                    @csrf
                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 rounded-lg">
                        <i class="fas fa-eye"></i> Activer
                    </button>
                </form>
            @endif

            {{-- Mettre en avant --}}
            <form method="POST" action="{{ route('admin.products.feature', $product->id) }}" class="mb-3">
                @csrf
                <button type="submit" class="w-full bg-purple-500 hover:bg-purple-600 text-white font-bold py-3 rounded-lg">
                    <i class="fas fa-star"></i> {{ ($product->is_featured ?? false) ? 'Retirer la mise en avant' : 'Mettre en avant' }}
                </button>
            </form>

            {{-- Supprimer --}}
            <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}"
                  onsubmit="return confirm('⚠️ Supprimer définitivement « {{ $product->name }} » ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full bg-gray-800 hover:bg-black text-white font-bold py-3 rounded-lg">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </form>
        </div>

    </div>

</div>

@endsection
