<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackLastActivity
{
    // Bazaga har so'rovda yozmaslik uchun faollik shu oraliqda bir marta yangilanadi
    private const UPDATE_EVERY_MINUTES = 5;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && (!$user->last_active_at || $user->last_active_at->lt(now()->subMinutes(self::UPDATE_EVERY_MINUTES)))) {
            // updated_at o'zgarmasligi uchun to'g'ridan-to'g'ri so'rov
            DB::table('users')->where('id', $user->id)->update(['last_active_at' => now()]);
            $user->last_active_at = now();
            $user->syncOriginalAttribute('last_active_at');
        }

        return $next($request);
    }
}
