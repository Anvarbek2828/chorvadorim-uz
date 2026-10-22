<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    /**
     * Admin bo'lmaganlarga (mehmonlarga ham) 404 — admin panel borligi oshkor qilinmaydi.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->is_admin || $user->is_blocked) {
            abort(404);
        }

        return $next($request);
    }
}
