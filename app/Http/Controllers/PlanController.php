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
            'planExpired' => $farm->plan_id !== null && $farm->isPlanExpired(),
            'planEndsAt' => $farm->isPlanExpired() ? null : $farm->plan_tugash_sanasi,
            'plans' => Plan::where('faol', true)->orderBy('max_hayvon')->get(),
        ]);
    }
}
