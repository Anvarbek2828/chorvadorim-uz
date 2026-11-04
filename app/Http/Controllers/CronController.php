<?php

namespace App\Http\Controllers;

use App\Services\DailySubscriptionRunner;

class CronController extends Controller
{
    /**
     * Kunlik ishning zaxira chaqiruvi. Sana va 08:00 sharti o'z kuchida: kuniga bir martadan ko'p ishlamaydi.
     */
    public function obuna(string $token, DailySubscriptionRunner $runner)
    {
        $expected = (string) config('services.obuna.cron_token');

        // Token sozlanmagan bo'lsa ham URL yopiq (fail-closed); mavjudligi oshkor qilinmaydi
        if ($expected === '' || !hash_equals($expected, $token)) {
            abort(404);
        }

        return response()->json([
            'ok' => true,
            'natija' => $runner->runIfDue(),
        ]);
    }
}
