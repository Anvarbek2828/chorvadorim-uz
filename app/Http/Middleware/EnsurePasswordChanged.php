<?php

namespace App\Http\Middleware;

use App\Services\CurrentFarm;
use Closure;
use Illuminate\Http\Request;

class EnsurePasswordChanged
{
    // Vaqtinchalik parol almashtirilmaguncha ochiq qoladigan marshrutlar
    private const ALLOWED_ROUTES = ['password.force', 'password.force.update', 'logout', 'lang.switch'];

    /**
     * Joriy fermadagi a'zolikda must_change_password=1 bo'lsa (egasi bergan vaqtinchalik parol),
     * faqat parolni almashtirish sahifasi va chiqish ishlaydi.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if (!app(CurrentFarm::class)->membership($user)?->must_change_password) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('Avval vaqtinchalik parolni almashtiring.')], 403);
        }

        return redirect()->route('password.force');
    }
}
