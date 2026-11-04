<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\Animal;
use App\Models\Plan;
use App\Models\User;
use App\Services\AiUsageService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(AiUsageService $usage)
    {
        $tz = config('ai.timezone');
        $periods = [
            'today' => $usage->dayStart(),
            'week' => now($tz)->startOfDay()->subDays(6)->utc(),
            'month' => now($tz)->startOfDay()->subDays(29)->utc(),
        ];

        $activeUsers = [];
        $newUsers = [];

        foreach ($periods as $key => $since) {
            $activeUsers[$key] = User::where('last_active_at', '>=', $since)->count();
            $newUsers[$key] = User::where('created_at', '>=', $since)->count();
        }

        $plans = Plan::withCount('users')->orderBy('max_hayvon')->get();
        $free = $plans->firstWhere('kod', Plan::FREE);

        // Tarifi belgilanmagan foydalanuvchilar bepul tarifda hisoblanadi
        if ($free) {
            $free->users_count += User::whereNull('plan_id')->count();
        }

        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'activeUsers' => $activeUsers,
            'newUsers' => $newUsers,
            'blockedUsers' => User::where('is_blocked', true)->count(),
            'animals' => Animal::count(),
            'archivedAnimals' => Animal::onlyTrashed()->count(),
            'botLinked' => User::whereNotNull('telegram_chat_id')->count(),
            'costToday' => (float) AiUsageLog::where('created_at', '>=', $usage->dayStart())->sum('narx_usd'),
            'costMonth' => $usage->platformMonthCost(),
            'budget' => (float) config('ai.platform_monthly_budget_usd'),
            'rate' => (float) config('ai.usd_to_som'),
            'plans' => $plans,
            'expiredPlans' => User::whereNotNull('plan_tugash_sanasi')
                ->where('plan_tugash_sanasi', '<', now($tz)->toDateString())
                ->count(),
            // Kunlik ishlar (obuna xabarlari, eslatmalar): oxirgi ishga tushish va natija
            'systemRuns' => DB::table('system_runs')->orderBy('nom')->get(),
        ]);
    }
}
