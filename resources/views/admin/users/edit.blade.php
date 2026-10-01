@extends('admin.layouts.app')

@section('title', 'Éditer : ' . $user->name)

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.users.show', $user->id) }}" class="text-orange-500 hover:text-orange-700">
        <i class="fas fa-arrow-left"></i> Retour au profil
    </a>
</div>

<div class="bg-white rounded-lg shadow p-6 max-w-3xl">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-user-edit text-orange-500"></i> Éditer l'utilisateur
    </h1>

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4">
            @foreach($errors->all() as $error)
                <div class="text-sm">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-4">
            <div class="mb-4 col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nom complet *</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Téléphone</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Rôle *</label>
                <select name="role" required
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
                    <option value="buyer" {{ old('role', $user->role) === 'buyer' ? 'selected' : '' }}>🛒 Acheteur</option>
                    <option value="seller" {{ old('role', $user->role) === 'seller' ? 'selected' : '' }}>🏪 Vendeur</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>👑 Admin</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Statut *</label>
                <select name="status" required
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
                    <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>✅ Actif</option>
                    <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>⏸️ Inactif</option>
                    <option value="banned" {{ old('status', $user->status) === 'banned' ? 'selected' : '' }}>🚫 Banni</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Pays</label>
                <input type="text" name="country" value="{{ old('country', $user->country) }}"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Ville</label>
                <input type="text" name="city" value="{{ old('city', $user->city) }}"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>

            <div class="mb-4 col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Adresse</label>
                <input type="text" name="address" value="{{ old('address', $user->address) }}"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>

            <div class="mb-4 col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Bio</label>
                <textarea name="bio" rows="3"
                          class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">{{ old('bio', $user->bio) }}</textarea>
            </div>
        </div>

        <div class="flex gap-3 mt-4">
            <button type="submit"
                    class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-lg">
                <i class="fas fa-save"></i> Enregistrer
            </button>
            <a href="{{ route('admin.users.show', $user->id) }}"
               class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-3 px-6 rounded-lg">
                Annuler
            </a>
        </div>
    </form>
</div>

@endsection
