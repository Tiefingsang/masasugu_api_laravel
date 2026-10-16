@extends('admin.layouts.app')

@section('title', 'Signalement #' . $report->id)

@section('content')

<div class="mb-6">
    <a href="{{ route('admin.reports.index') }}" class="text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left"></i> Retour aux signalements
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- COLONNE PRINCIPALE --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- HEADER --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Signalement #{{ $report->id }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Créé le {{ $report->created_at->format('d/m/Y à H:i') }}
                    </p>
                </div>
                @if($report->status === 'pending')
                    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 font-medium text-sm">⏳ En attente</span>
                @elseif($report->status === 'resolved')
                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-medium text-sm">✅ Résolu</span>
                @elseif($report->status === 'rejected')
                    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 font-medium text-sm">❌ Rejeté</span>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Type :</span>
                    <span class="font-medium">
                        @if(str_contains($report->reportable_type, 'Product'))
                            📦 Produit
                        @elseif(str_contains($report->reportable_type, 'User'))
                            👤 Utilisateur
                        @endif
                    </span>
                </div>
                <div>
                    <span class="text-gray-500">Raison :</span>
                    <span class="font-medium">{{ $report->reason }}</span>
                </div>
            </div>

            @if($report->description)
                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <div class="text-xs text-gray-500 uppercase mb-2">Description</div>
                    <p class="text-gray-700">{{ $report->description }}</p>
                </div>
            @endif
        </div>

        {{-- CONTENU SIGNALÉ --}}
        @if($target)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-exclamation-triangle text-orange-500"></i>
                    Contenu signalé
                </h2>

                @if(str_contains($report->reportable_type, 'Product'))
                    <div class="flex gap-4">
                        @if($target->main_image)
                            <img src="https://api.masasugu.com/storage/{{ $target->main_image }}"
                                 class="w-32 h-32 object-cover rounded-lg"
                                 onerror="this.src='https://via.placeholder.com/128'">
                        @endif
                        <div>
                            <div class="font-bold text-lg">{{ $target->name }}</div>
                            <div class="text-orange-500 font-bold">{{ number_format($target->price, 0, ',', ' ') }} FCFA</div>
                            <div class="text-sm text-gray-500 mt-2">{{ $target->description }}</div>
                            <div class="mt-2">
                                <a href="{{ route('admin.products.show', $target->id) }}"
                                   class="text-blue-500 hover:underline text-sm">
                                    <i class="fas fa-external-link-alt"></i> Voir le produit complet
                                </a>
                            </div>
                        </div>
                    </div>
                @elseif(str_contains($report->reportable_type, 'User'))
                    <div class="flex gap-4 items-center">
                        <div class="w-16 h-16 rounded-full bg-orange-100 flex items-center justify-center">
                            <span class="text-orange-500 font-bold text-xl">
                                {{ strtoupper(substr($target->name, 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <div class="font-bold text-lg">{{ $target->name }}</div>
                            <div class="text-sm text-gray-500">{{ $target->email }}</div>
                            <div class="mt-2">
                                <a href="{{ route('admin.users.show', $target->id) }}"
                                   class="text-blue-500 hover:underline text-sm">
                                    <i class="fas fa-external-link-alt"></i> Voir le profil complet
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- NOTE ADMIN --}}
        @if($report->admin_note)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-2">
                    <i class="fas fa-sticky-note text-blue-500"></i> Note admin
                </h2>
                <p class="text-gray-700">{{ $report->admin_note }}</p>
                @if($report->reviewed_at)
                    <p class="text-xs text-gray-500 mt-2">
                        Traité le {{ $report->reviewed_at->format('d/m/Y à H:i') }}
                    </p>
                @endif
            </div>
        @endif

    </div>

    {{-- COLONNE ACTIONS --}}
    <div class="space-y-6">

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-bold text-gray-800 mb-3">
                <i class="fas fa-user text-orange-500"></i> Signalé par
            </h3>
            <div class="text-sm">
                <div class="font-medium">{{ $report->reporter->name ?? 'Inconnu' }}</div>
                <div class="text-gray-500">{{ $report->reporter->email ?? '' }}</div>
            </div>
        </div>

        @if($report->status === 'pending')
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-bold text-gray-800 mb-3">
                    <i class="fas fa-gavel text-orange-500"></i> Actions
                </h3>

                <form method="POST" action="{{ route('admin.reports.resolve', $report->id) }}" class="mb-3">
                    @csrf
                    <textarea name="admin_note" rows="2" placeholder="Note (optionnel)"
                              class="w-full px-3 py-2 border rounded-lg text-sm mb-2"></textarea>
                    <button type="submit"
                            class="w-full bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg font-medium">
                        <i class="fas fa-check"></i> Marquer comme résolu
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.reports.reject', $report->id) }}" class="mb-3">
                    @csrf
                    <button type="submit"
                            class="w-full bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg font-medium">
                        <i class="fas fa-times"></i> Rejeter le signalement
                    </button>
                </form>

                <hr class="my-4">

                <form method="POST" action="{{ route('admin.reports.delete-content', $report->id) }}"
                      onsubmit="return confirm('⚠️ Supprimer définitivement ce contenu ?');">
                    @csrf
                    <button type="submit"
                            class="w-full bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg font-medium">
                        <i class="fas fa-trash"></i>
                        Supprimer le contenu signalé
                    </button>
                </form>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('admin.reports.destroy', $report->id) }}"
                  onsubmit="return confirm('Supprimer ce signalement ?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full text-red-500 hover:text-red-700 text-sm">
                    <i class="fas fa-trash"></i> Supprimer ce signalement
                </button>
            </form>
        </div>

    </div>

</div>

@endsection
xflmnwwwjamfjauv
