@extends('admin.layouts.app')

@section('title', 'Nouvelle catégorie')

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.categories.index') }}" class="text-orange-500 hover:text-orange-700">
        <i class="fas fa-arrow-left"></i> Retour aux catégories
    </a>
</div>

<div class="bg-white rounded-lg shadow p-6 max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-plus text-orange-500"></i> Nouvelle catégorie
    </h1>

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4">
            @foreach($errors->all() as $error)
                <div class="text-sm">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nom *</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500"
                   placeholder="Ex: Électronique, Mode Femme...">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3"
                      class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500"
                      placeholder="Description de la catégorie...">{{ old('description') }}</textarea>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">
                Icône (Font Awesome)
            </label>
            <input type="text" name="icon" value="{{ old('icon') }}"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500"
                   placeholder="Ex: mobile, shirt, home...">
            <div class="text-xs text-gray-500 mt-1">
                Nom de l'icône Font Awesome (sans "fa-"). Ex: <code>mobile</code>, <code>shirt</code>, <code>home</code>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Image (optionnel)</label>
            <input type="file" name="image" accept="image/*"
                   class="w-full px-3 py-2 border rounded-lg">
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-lg">
                <i class="fas fa-save"></i> Créer
            </button>
            <a href="{{ route('admin.categories.index') }}"
               class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-3 px-6 rounded-lg">
                Annuler
            </a>
        </div>
    </form>
</div>

@endsection
