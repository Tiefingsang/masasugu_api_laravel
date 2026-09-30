<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Masasugu — @yield('title', 'Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">

<div class="flex h-screen">

    <aside class="w-64 bg-gray-900 text-white flex flex-col flex-shrink-0">
        <div class="p-4 text-2xl font-bold border-b border-gray-800 text-orange-400">
            🛒 Masasugu
        </div>

        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-orange-500 text-white' : 'text-gray-300' }}">
                <i class="fas fa-home w-6"></i> Dashboard
            </a>
            <a href="#" class="flex items-center px-4 py-3 rounded hover:bg-gray-800 text-gray-300">
                <i class="fas fa-users w-6"></i> Utilisateurs
            </a>
            <a href="{{ route('admin.shops.index') }}"
            class="flex items-center px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.shops.*') ? 'bg-orange-500 text-white' : 'text-gray-300' }}">
                <i class="fas fa-store w-6"></i> Boutiques
    @php
        $pendingShops = \App\Models\Company::where('status', 'pending')->count();
    @endphp
    @if($pendingShops > 0)
        <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
            {{ $pendingShops }}
        </span>
    @endif
</a>
            <a href="#" class="flex items-center px-4 py-3 rounded hover:bg-gray-800 text-gray-300">
                <i class="fas fa-box w-6"></i> Produits
            </a>
            <a href="#" class="flex items-center px-4 py-3 rounded hover:bg-gray-800 text-gray-300">
                <i class="fas fa-shopping-bag w-6"></i> Commandes
            </a>
        </nav>

        <div class="p-4 border-t border-gray-800">
            <div class="text-xs text-gray-400">Connecté</div>
            <div class="text-sm font-semibold">{{ auth()->user()->name ?? '' }}</div>
            <form method="POST" action="{{ route('admin.logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="text-xs text-red-400 hover:text-red-300">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">@yield('title', 'Dashboard')</h1>
            <div class="text-sm text-gray-500">
                <i class="far fa-clock"></i> {{ now()->format('d/m/Y H:i') }}
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6">
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

            @yield('content')
        </div>
    </main>

</div>

</body>
</html>
