@extends('admin.layouts.app')

@section('title', 'Catégories')

@section('content')

<div class="flex justify-between items-center mb-6">
    <div class="text-sm text-gray-500">
        {{ $categories->total() }} catégorie(s)
    </div>
    <a href="{{ route('admin.categories.create') }}"
       class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg">
        <i class="fas fa-plus"></i> Nouvelle catégorie
    </a>
</div>

{{-- RECHERCHE --}}
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="{{ route('admin.categories.index') }}" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Rechercher une catégorie..."
               class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg">
            <i class="fas fa-search"></i> Rechercher
        </button>
        @if(request('search'))
            <a href="{{ route('admin.categories.index') }}"
               class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                <i class="fas fa-times"></i>
            </a>
        @endif
    </form>
</div>

{{-- LISTE --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Catégorie</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Slug</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Produits</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($categories as $category)
                @php
                    $imageUrl = null;
                    if ($category->image) {
                        $imageUrl = str_starts_with($category->image, 'http')
                            ? $category->image
                            : asset('storage/' . ltrim($category->image, '/'));
                    }
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <i class="fas fa-tag text-gray-400 hidden"></i>
                                @elseif($category->icon)
                                    <i class="fas fa-{{ $category->icon }} text-orange-500 text-xl"></i>
                                @else
                                    <i class="fas fa-tag text-gray-400"></i>
                                @endif
                            </div>
                            <div>
                                <div class="font-semibold text-gray-800">{{ $category->name }}</div>
                                @if($category->description)
                                    <div class="text-xs text-gray-500">{{ Str::limit($category->description, 60) }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm font-mono text-gray-600">
                        {{ $category->slug }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <a href="{{ route('admin.products.index', ['category' => $category->id]) }}"
                           class="text-orange-500 hover:text-orange-700 font-semibold">
                            {{ $category->products_count }} produit(s)
                        </a>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.categories.edit', $category->id) }}"
                               class="text-blue-500 hover:text-blue-700" title="Éditer">
                                <i class="fas fa-edit"></i>
                            </a>

                            @if($category->products_count > 0)
                                <span class="text-gray-300 cursor-not-allowed" title="Impossible : {{ $category->products_count }} produits associés">
                                    <i class="fas fa-trash"></i>
                                </span>
                            @else
                                <form method="POST" action="{{ route('admin.categories.destroy', $category->id) }}"
                                      class="inline" onsubmit="return confirm('⚠️ Supprimer « {{ $category->name }} » ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-tags text-4xl mb-3"></i>
                        <div>Aucune catégorie trouvée</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($categories->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $categories->appends(request()->query())->links() }}
        </div>
    @endif
</div>

@endsection
