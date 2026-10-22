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
        $user = auth()->user();

        return view('plans.index', [
            'summary' => $usage->summary($user),
            'planExpired' => $user->plan_id !== null && $user->isPlanExpired(),
            'planEndsAt' => $user->isPlanExpired() ? null : $user->plan_tugash_sanasi,
            'plans' => Plan::where('faol', true)->orderBy('max_hayvon')->get(),
        ]);
    }
}
