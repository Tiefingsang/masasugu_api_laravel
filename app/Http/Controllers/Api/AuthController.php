<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
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

    //avatar update
    /* public function updateAvatar(Request $request)
{
    $user = auth()->user();

    if (!$request->hasFile('avatar')) {
        return response()->json(['error' => 'Aucune image reçue'], 400);
    }

    $file = $request->file('avatar');
    $filename = time() . '.' . $file->getClientOriginalExtension();
    $path = $file->storeAs('avatars', $filename, 'public');

    $user->avatar = '/storage/' . $path;
    $user->save();

    return response()->json([
        'message' => 'Avatar mis à jour avec succès',
        'avatar' => $user->avatar
    ]);
} */

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



}
