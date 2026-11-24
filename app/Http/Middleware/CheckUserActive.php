<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('Middleware CheckUserActive ejecutado', [
            'user_id' => auth()->id(),
            'active' => auth()->user()->active ?? 'no autenticado'
        ]);

        if (auth()->check() && ! auth()->user()->active) {
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta ha sido desactivada. Contacta al administrador.',
            ]);
        }
        return $next($request);
        }
}
