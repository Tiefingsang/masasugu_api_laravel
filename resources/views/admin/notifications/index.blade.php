@extends('admin.layouts.app')

@section('title', 'Notifications')

@section('content')

{{-- STATS --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
        <div class="text-xs text-gray-500 uppercase">Utilisateurs</div>
        <div class="text-2xl font-bold text-gray-800">{{ $stats['total_users'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
        <div class="text-xs text-gray-500 uppercase">Vendeurs</div>
        <div class="text-2xl font-bold text-gray-800">{{ $stats['sellers'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-300">
        <div class="text-xs text-gray-500 uppercase">Acheteurs</div>
        <div class="text-2xl font-bold text-gray-800">{{ $stats['buyers'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
        <div class="text-xs text-gray-500 uppercase">Avec FCM</div>
        <div class="text-2xl font-bold text-green-600">{{ $stats['with_fcm'] }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- FORMULAIRE BROADCAST --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-bullhorn text-orange-500"></i> Envoyer une notification
        </h2>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.notifications.send') }}">
            @csrf

            {{-- CIBLE --}}
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Destinataires *</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="target" value="all" checked
                               onchange="document.getElementById('specific-user').classList.add('hidden')"
                               class="mr-2">
                        <span>🌍 Tous les utilisateurs ({{ $stats['with_fcm'] }} avec FCM)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="target" value="buyers"
                               onchange="document.getElementById('specific-user').classList.add('hidden')"
                               class="mr-2">
                        <span>🛒 Acheteurs uniquement</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="target" value="sellers"
                               onchange="document.getElementById('specific-user').classList.add('hidden')"
                               class="mr-2">
                        <span>🏪 Vendeurs uniquement</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="target" value="specific"
                               onchange="document.getElementById('specific-user').classList.remove('hidden')"
                               class="mr-2">
                        <span>👤 Un utilisateur spécifique</span>
                    </label>
                </div>
            </div>

            {{-- USER ID (si spécifique) --}}
            <div id="specific-user" class="hidden mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">ID de l'utilisateur</label>
                <input type="number" name="user_id" placeholder="Ex: 5"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>

            {{-- TYPE --}}
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Type *</label>
                <select name="type" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
                    <option value="info">ℹ️ Information</option>
                    <option value="promo">🎁 Promotion</option>
                    <option value="alert">⚠️ Alerte</option>
                    <option value="update">🔄 Mise à jour</option>
                </select>
            </div>

            {{-- TITRE --}}
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Titre *</label>
                <input type="text" name="title" required maxlength="100"
                       placeholder="Ex: Nouvelle fonctionnalité disponible !"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>

            {{-- MESSAGE --}}
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Message *</label>
                <textarea name="message" required maxlength="500" rows="4"
                          placeholder="Contenu de la notification..."
                          class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500"></textarea>
            </div>

            <button type="submit"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-lg"
                    onclick="return confirm('Envoyer cette notification ?')">
                <i class="fas fa-paper-plane"></i> Envoyer
            </button>
        </form>
    </div>

    {{-- TEST UN UTILISATEUR --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-vial text-orange-500"></i> Tester sur un utilisateur
        </h2>

        <p class="text-sm text-gray-500 mb-4">
            Envoyer une notification de test à un utilisateur spécifique pour vérifier que le push fonctionne.
        </p>

        <form method="POST" action="{{ route('admin.notifications.test') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">ID utilisateur *</label>
                <input type="number" name="user_id" required placeholder="Ex: 2"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
                <div class="text-xs text-gray-500 mt-1">
                    Trouve l'ID dans la page <a href="{{ route('admin.users.index') }}" class="text-orange-500">Utilisateurs</a>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Titre</label>
                <input type="text" name="title" value="Test Masasugu" maxlength="100"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Message</label>
                <input type="text" name="message" value="Ceci est un test de notification" maxlength="500"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>

            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 rounded-lg">
                <i class="fas fa-paper-plane"></i> Envoyer le test
            </button>
        </form>
    </div>

</div>

{{-- INFO --}}
<div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
    <div class="flex items-start">
        <i class="fas fa-info-circle text-blue-500 text-xl mt-0.5 mr-3"></i>
        <div class="text-sm text-blue-800">
            <div class="font-bold mb-1">Comment ça marche ?</div>
            <ul class="list-disc ml-5 space-y-1">
                <li>Les notifications sont envoyées via <strong>Firebase Cloud Messaging (FCM)</strong></li>
                <li>Seuls les utilisateurs avec un <strong>token FCM</strong> reçoivent les notifications</li>
                <li>Le token est enregistré quand l'utilisateur ouvre l'app mobile</li>
                <li>Les notifications apparaissent même si l'app est fermée</li>
            </ul>
        </div>
    </div>
</div>

@endsection
