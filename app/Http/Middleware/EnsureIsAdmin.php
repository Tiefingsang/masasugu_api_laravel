<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            Auth::logout();

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => 'Accès refusé. Compte admin requis.'
                ], 403);
            }

            return redirect()->route('admin.login')
                ->with('error', 'Accès refusé. Compte admin requis.');
        }

        return $next($request);
    }
}
