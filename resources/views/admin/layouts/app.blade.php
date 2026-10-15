<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Masasugu — @yield('title', 'Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @stack('styles')
</head>
<body class="bg-gray-100">

<div class="flex h-screen">

    {{-- SIDEBAR --}}
    <aside class="w-64 bg-gray-900 text-white flex flex-col flex-shrink-0">
        <div class="p-4 text-2xl font-bold border-b border-gray-800 text-orange-400">
            🛒 Masasugu
        </div>

        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">

            {{-- DASHBOARD --}}
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-orange-500 text-white' : 'text-gray-300' }}">
                <i class="fas fa-home w-6"></i> Dashboard
            </a>

            {{-- SÉPARATEUR --}}
            <div class="pt-3 pb-1">
                <div class="text-xs text-gray-500 uppercase px-2">Modération</div>
            </div>

            {{-- SIGNALEMENTS --}}
            <a href="{{ route('admin.reports.index') }}"
               class="flex items-center px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.reports.index') ? 'bg-orange-500 text-white' : 'text-gray-300' }}">
                <i class="fas fa-flag w-6"></i> Signalements
                @php $pendingReports = \App\Models\Report::where('status', 'pending')->count(); @endphp
                @if($pendingReports > 0)
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                        {{ $pendingReports }}
                    </span>
                @endif
            </a>

            {{-- UTILISATEURS BLOQUÉS --}}
            <a href="{{ route('admin.reports.blocked') }}"
               class="flex items-center px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.reports.blocked') ? 'bg-orange-500 text-white' : 'text-gray-300' }}">
                <i class="fas fa-user-slash w-6"></i> Bloqués
                @php $blockedCount = \App\Models\BlockedUser::count(); @endphp
                @if($blockedCount > 0)
                    <span class="ml-auto bg-gray-600 text-white text-xs px-2 py-0.5 rounded-full">
                        {{ $blockedCount }}
                    </span>
                @endif
            </a>

            {{-- SÉPARATEUR --}}
            <div class="pt-3 pb-1">
                <div class="text-xs text-gray-500 uppercase px-2">Gestion</div>
            </div>

            {{-- UTILISATEURS --}}
            <a href="{{ route('admin.users.index') }}"
               class="flex items-center px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.users.*') ? 'bg-orange-500 text-white' : 'text-gray-300' }}">
                <i class="fas fa-users w-6"></i> Utilisateurs
                @php $pendingUsers = \App\Models\User::where('is_verified', 0)->where('role', '!=', 'admin')->count(); @endphp
                @if($pendingUsers > 0)
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                        {{ $pendingUsers }}
                    </span>
                @endif
            </a>

            {{-- BOUTIQUES --}}
            <a href="{{ route('admin.shops.index') }}"
               class="flex items-center px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.shops.*') ? 'bg-orange-500 text-white' : 'text-gray-300' }}">
                <i class="fas fa-store w-6"></i> Boutiques
                @php $pendingShops = \App\Models\Company::where('status', 'pending')->count(); @endphp
                @if($pendingShops > 0)
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                        {{ $pendingShops }}
                    </span>
                @endif
            </a>

            {{-- PRODUITS --}}
            <a href="{{ route('admin.products.index') }}"
               class="flex items-center px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.products.*') ? 'bg-orange-500 text-white' : 'text-gray-300' }}">
                <i class="fas fa-box w-6"></i> Produits
                @php $pendingProducts = \App\Models\Product::where('status', 'pending')->count(); @endphp
                @if($pendingProducts > 0)
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                        {{ $pendingProducts }}
                    </span>
                @endif
            </a>

            {{-- COMMANDES --}}
            <a href="{{ route('admin.orders.index') }}"
               class="flex items-center px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.orders.*') ? 'bg-orange-500 text-white' : 'text-gray-300' }}">
                <i class="fas fa-shopping-bag w-6"></i> Commandes
                @php $pendingOrders = \App\Models\Order::where('status', 'pending')->count(); @endphp
                @if($pendingOrders > 0)
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                        {{ $pendingOrders }}
                    </span>
                @endif
            </a>

            {{-- CATÉGORIES --}}
            <a href="{{ route('admin.categories.index') }}"
               class="flex items-center px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.categories.*') ? 'bg-orange-500 text-white' : 'text-gray-300' }}">
                <i class="fas fa-tags w-6"></i> Catégories
            </a>

            {{-- SÉPARATEUR --}}
            <div class="pt-3 pb-1">
                <div class="text-xs text-gray-500 uppercase px-2">Communication</div>
            </div>

            {{-- NOTIFICATIONS --}}
            <a href="{{ route('admin.notifications.index') }}"
               class="flex items-center px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.notifications.*') ? 'bg-orange-500 text-white' : 'text-gray-300' }}">
                <i class="fas fa-bullhorn w-6"></i> Notifications
            </a>

            {{-- STATISTIQUES --}}
            <a href="{{ route('admin.stats.index') }}"
               class="flex items-center px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.stats.*') ? 'bg-orange-500 text-white' : 'text-gray-300' }}">
                <i class="fas fa-chart-bar w-6"></i> Statistiques
            </a>

        </nav>

        <div class="p-4 border-t border-gray-800">
            <div class="text-xs text-gray-400">Connecté</div>
            <div class="text-sm font-semibold">{{ auth()->user()->name ?? '' }}</div>
            <div class="text-xs text-gray-500 truncate">{{ auth()->user()->email ?? '' }}</div>
            <form method="POST" action="{{ route('admin.logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="text-xs text-red-400 hover:text-red-300">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </button>
            </form>
        </div>
    </aside>

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">@yield('title', 'Dashboard')</h1>
            <div class="text-sm text-gray-500">
                <i class="far fa-clock"></i> {{ now()->format('d/m/Y H:i') }}
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6">
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 px-4 py-3 rounded mb-4 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('warning') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

</div>

@stack('scripts')
</body>
</html>
