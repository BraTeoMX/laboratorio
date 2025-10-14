<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectBasedOnRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtenemos el usuario autenticado
        $user = Auth::user();

        // Verificamos el rol y la ruta actual
        $currentRouteName = $request->route()->getName();

        // Lógica de redirección
        // Si el rol es 1 y no está en 'dashboard', lo mandamos ahí.
        if ($user->role_id == 1 && $currentRouteName !== 'dashboard') {
            return redirect()->route('dashboard');
        }

        // Si el rol es 2 o 4 y no está en 'dashboard2', lo mandamos ahí.
        if (in_array($user->role_id, [2, 4]) && $currentRouteName !== 'dashboard2') {
            return redirect()->route('dashboard2');
        }

        // Si el rol es 3 (Gestor) y no está en 'vistaGestor', lo mandamos ahí.
        if ($user->role_id == 3 && $currentRouteName !== 'vistaGestor') {
            return redirect()->route('vistaGestor');
        }

        // Si el rol es 5 (Auditor) y no está en 'vistaAuditor', lo mandamos ahí.
        if ($user->role_id == 5 && $currentRouteName !== 'vistaAuditor') {
            return redirect()->route('vistaAuditor');
        }

        // Si no se cumple ninguna condición de redirección, dejamos que la petición continúe.
        return $next($request);
    }
}