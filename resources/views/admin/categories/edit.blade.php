@extends('admin.layouts.app')

@section('title', 'Éditer : ' . $category->name)

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.categories.index') }}" class="text-orange-500 hover:text-orange-700">
        <i class="fas fa-arrow-left"></i> Retour aux catégories
    </a>
</div>

<div class="bg-white rounded-lg shadow p-6 max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-edit text-orange-500"></i> Éditer la catégorie
    </h1>

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4">
            @foreach($errors->all() as $error)
                <div class="text-sm">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categories.update', $category->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nom *</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" required
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3"
                      class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">{{ old('description', $category->description) }}</textarea>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Icône Font Awesome</label>
            <input type="text" name="icon" value="{{ old('icon', $category->icon) }}"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
        </div>

        @if($category->image)
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Image actuelle</label>
                @php
                    $imageUrl = str_starts_with($category->image, 'http')
                        ? $category->image
                        : asset('storage/' . ltrim($category->image, '/'));
                @endphp
                <img src="{{ $imageUrl }}" class="w-32 h-32 object-cover rounded-lg">
            </div>
        @endif

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-1">
                Nouvelle image (laisser vide pour garder)
            </label>
            <input type="file" name="image" accept="image/*"
                   class="w-full px-3 py-2 border rounded-lg">
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-lg">
                <i class="fas fa-save"></i> Enregistrer
            </button>
            <a href="{{ route('admin.categories.index') }}"
               class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-3 px-6 rounded-lg">
                Annuler
            </a>
        </div>
    </form>
</div>

@endsection
