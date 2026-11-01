<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsNotBlocked
{
    /**
     * Bloklangan foydalanuvchining ochiq sessiyasi (yoki "eslab qolish" cookie'si) yopiladi.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->is_blocked) {
            return $next($request);
        }

        $locale = $request->session()->get('locale');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($locale) {
            $request->session()->put('locale', $locale);
        }

        $message = __('Hisobingiz bloklangan. Batafsil ma\'lumot uchun administratsiyaga murojaat qiling.');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
