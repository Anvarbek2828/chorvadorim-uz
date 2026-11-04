<?php

namespace App\Http\Middleware;

use App\Services\DailySubscriptionRunner;
use Closure;
use Illuminate\Http\Request;
use Throwable;

class RunDailyTasks
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Javob foydalanuvchiga yuborilgandan keyin (PHP-FPM: fastcgi_finish_request) kunlik ishlarni tekshiradi.
     * Xato so'rovni buzmaydi — faqat jurnalga yoziladi.
     */
    public function terminate(Request $request, $response): void
    {
        if (!config('services.obuna.auto_run', true)) {
            return;
        }

        try {
            app(DailySubscriptionRunner::class)->runIfDue();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
