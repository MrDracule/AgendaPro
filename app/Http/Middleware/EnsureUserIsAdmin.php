<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
    // Se for admin, pode passar
    if (Auth::check() && Auth::user()->role === 'admin') {
        return $next($request);
    }

    // Se NÃO for admin, só redireciona se ele NÃO estiver tentando acessar o dashboard comum
    // Isso evita o loop infinito
    if (!$request->is('dashboard')) {
        return redirect()->route('dashboard')->with('error', 'Acesso negado.');
    }

    return $next($request);
    }
}
