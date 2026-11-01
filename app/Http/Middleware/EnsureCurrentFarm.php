<?php

namespace App\Http\Middleware;

use App\Services\CurrentFarm;
use Closure;
use Illuminate\Http\Request;

class EnsureCurrentFarm
{
    /**
     * Ferma ma'lumotlari sahifalari: foydalanuvchining faol a'zoligi bo'lmasa — 403 matnli sahifa.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && !app(CurrentFarm::class)->membership($user)) {
            abort(403, __("Siz hech qaysi fermaga a'zo emassiz yoki a'zoligingiz to'xtatilgan. Ferma egasiga yoki administratsiyaga murojaat qiling."));
        }

        return $next($request);
    }
}
