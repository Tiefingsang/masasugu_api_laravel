<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Http;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // 🆕 Envoyer l'email de vérification
        try {
            $user->sendEmailVerificationNotification();
            \Log::info(' Email de vérification envoyé à ' . $user->email);
        } catch (\Exception $e) {
            \Log::error(' Erreur envoi email vérification : ' . $e->getMessage());
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'email_verified' => false,
        ]);
    }

    public function login(Request $request){
        $request->validate([
            'email' => 'required|string|email|exists:users,email',
            'password' => 'required|string',
        ]);
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function saveFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'platform' => 'nullable|in:android,ios',
        ]);

        $user = $request->user();
        $user->update([
            'fcm_token' => $request->fcm_token,
            'fcm_platform' => $request->platform,
        ]);

        return response()->json(['success' => true]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    //profile update
    public function updateProfile(Request $request){
    $user = auth()->user();

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'avatar'=>'nullable|image|mimes:jpeg,png,jpg,gif',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'phone' => 'nullable|string|max:20',
        'country' => 'nullable|string|max:100',
        'city' => 'nullable|string|max:100',
        'address' => 'nullable|string|max:255',
    ]);

    $user->update($validated);

    return response()->json([
        'message' => 'Profil mis à jour avec succès',
        'user' => $user,
    ]);
    }



    public function updateAvatar(Request $request)
    {
        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $path = $file->store('avatars', 'public');

            $user->avatar = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'avatar_url' => asset('storage/'.$path),
            ]);
        }

        return response()->json(['error' => 'Aucune image reçue'], 400);
    }




    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Email envoyé avec succès.'])
            : response()->json(['message' => 'Aucun compte trouvé avec cet email.'], 404);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Mot de passe réinitialisé avec succès.'])
            : response()->json(['message' => 'Erreur lors de la réinitialisation.'], 400);
    }







public function googleLogin(Request $request)
{
    $request->validate([
        'id_token' => 'required|string',
    ]);

    // 1. Vérifier le token auprès de Google
    try {
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $request->id_token,
        ]);

        if (!$response->successful()) {
            return response()->json(['message' => 'Token Google invalide.'], 401);
        }

        $googleUser = $response->json();
    } catch (\Exception $e) {
        \Log::error('Google token error: ' . $e->getMessage());
        return response()->json(['message' => 'Erreur de vérification.'], 500);
    }

    $email = $googleUser['email'] ?? null;
    $name = $googleUser['name'] ?? null;
    $avatar = $googleUser['picture'] ?? null;

    if (!$email) {
        return response()->json(['message' => 'Email Google manquant.'], 400);
    }

    // 2. Chercher ou créer l'utilisateur
    $user = User::where('email', $email)->first();

    if (!$user) {
        $user = User::create([
            'name' => $name ?? explode('@', $email)[0],
            'email' => $email,
            'password' => Hash::make(\Illuminate\Support\Str::random(32)),
            'role' => 'buyer',
            'status' => 'active',
            'is_verified' => 1,
            'email_verified_at' => now(),
            'avatar' => $avatar,
        ]);
        \Log::info('🆕 Nouveau compte Google : ' . $email);
    } else {
        if (!$user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }
        \Log::info('✅ Connexion Google : ' . $email);
    }

    // 3. Générer le token Sanctum
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'success' => true,
        'user' => $user,
        'token' => $token,
    ]);
}



}
