<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\AiUsageService;

class PlanController extends Controller
{
    /**
     * Foydalanuvchi uchun: joriy tarif, shu oydagi ishlatish va mavjud tariflar.
     */
    public function index(AiUsageService $usage)
    {
        // Tarif fermaga tegishli
        $farm = $this->currentFarm();

        return view('plans.index', [
            'summary' => $usage->summary($farm),
            // Muddati o'tgan pullik tarif limitlari saqlanadi; holat banner orqali ko'rsatiladi
            'planExpired' => $farm->plan_id !== null && $farm->plan?->kod !== Plan::FREE && $farm->isPlanExpired(),
            'planEndsAt' => $farm->plan_tugash_sanasi,
            'plans' => Plan::where('faol', true)->orderBy('max_hayvon')->get(),
        ]);
    }
}
