<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Masasugu — Connexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 h-screen flex items-center justify-center">

<div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4">
    <div class="text-center mb-8">
        <div class="text-5xl mb-2">🛒</div>
        <h1 class="text-3xl font-bold text-orange-500">Masasugu</h1>
        <p class="text-gray-500 text-sm mt-2">Espace administrateur</p>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4">
            @foreach($errors->all() as $error)
                <div class="text-sm">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login') }}">
        @csrf

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                <i class="fas fa-envelope mr-1"></i> Email
            </label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500"
                   placeholder="admin@masasugu.com" required autofocus>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                <i class="fas fa-lock mr-1"></i> Mot de passe
            </label>
            <input type="password" name="password"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-500"
                   placeholder="••••••••" required>
        </div>

        <div class="mb-6">
            <label class="flex items-center text-sm text-gray-600">
                <input type="checkbox" name="remember" class="mr-2">
                Se souvenir de moi
            </label>
        </div>

        <button type="submit"
                class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-lg transition">
            <i class="fas fa-sign-in-alt mr-2"></i> Se connecter
        </button>
    </form>

    <div class="text-center mt-6 text-xs text-gray-400">Masasugu Admin v1.0</div>
</div>

</body>
</html>
