<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->must_change_password) {
            $exemptRoutes = [
                'auth.password.change',
                'auth.password.change.update',
                'filament.admin.auth.logout',
            ];

            $currentRoute = (string) $request->route()?->getName();

            if (! in_array($currentRoute, $exemptRoutes, true) && ! $request->is('auth/password/change*') && ! $request->is('admin/logout')) {
                return redirect()->route('auth.password.change');
            }
        }

        return $next($request);
    }
}
