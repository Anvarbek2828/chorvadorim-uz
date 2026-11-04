<?php

namespace App\Http\Middleware;

use App\Services\CurrentFarm;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;

class EnsureFarmWritable
{
    // Banner ichida ko'rsatiladigan flash kaliti (har sahifada xato bloki bo'lmagani uchun alohida)
    public const FLASH = 'faqat_oqish_xato';

    // Faqat o'qish rejimida ham ochiq yozish marshrutlari. Admin panel bu cheklovdan tashqarida.
    private const ALLOWED_ROUTES = [
        'logout',
        'password.update',
        'password.confirm',
        'password.force.update',
        'verification.send',
        'profile.update',
        'farm.switch',
        'lang.switch',
        'telegram.generate-code',
        'reminders.complete',
        // Xavfsizlik amallari: xodimni to'xtatish, chiqarish, vaqtinchalik parol (faollashtirish — StaffController da yopiq)
        'staff.status',
        'staff.destroy',
        'staff.password',
        'admin.*',
    ];

    public function __construct(private SubscriptionService $subscriptions)
    {
    }

    /**
     * Joriy ferma faqat o'qish rejimida bo'lsa, POST/PUT/PATCH/DELETE so'rovlari (oq ro'yxatdan tashqari) bloklanadi.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $request->isMethodSafe() || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        $farm = app(CurrentFarm::class)->farm($user);

        if (!$farm || !$this->subscriptions->isReadOnly($farm)) {
            return $next($request);
        }

        $message = __("Bu amalni hozir bajarib bo'lmaydi: to'lov qilinmagani sababli faoliyat cheklangan.");

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return back()->with(self::FLASH, $message);
    }
}
