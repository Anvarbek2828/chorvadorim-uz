<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\Plan;
use App\Models\User;
use App\Services\AiUsageService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AiCostController extends Controller
{
    private const DAYS = 30;

    public function index(AiUsageService $usage)
    {
        $tz = config('ai.timezone');
        $since = now($tz)->startOfDay()->subDays(self::DAYS - 1)->utc();

        $monthStart = $usage->monthStart();
        $prevMonthStart = now($tz)->startOfMonth()->subMonthNoOverflow()->utc();

        return view('admin.ai-costs', [
            'days' => $this->dailyTotals($since, $tz),
            'topUsers' => $this->topUsers($since),
            'planAverages' => [
                'current' => $this->planAverages($monthStart, now()->addMinute()),
                'previous' => $this->planAverages($prevMonthStart, $monthStart),
            ],
            'rate' => (float) config('ai.usd_to_som'),
            'periodDays' => self::DAYS,
        ]);
    }

    /**
     * Oxirgi 30 kun, eng yangisi birinchi. Kunlar config('ai.timezone') bo'yicha.
     */
    private function dailyTotals(Carbon $since, string $tz): array
    {
        $dayExpr = $this->localDayExpression($tz);

        $rows = AiUsageLog::where('created_at', '>=', $since)
            ->selectRaw("{$dayExpr} as kun, turi, COUNT(*) as soni, SUM(narx_usd) as narx")
            ->groupByRaw("{$dayExpr}, turi")
            ->get();

        $days = [];
        $empty = ['soni' => 0, 'narx' => 0.0];

        for ($i = 0; $i < self::DAYS; $i++) {
            $date = now($tz)->startOfDay()->subDays($i)->toDateString();
            $days[$date] = [AiUsageLog::TYPE_CHAT => $empty, AiUsageLog::TYPE_VISION => $empty, 'jami' => 0.0];
        }

        foreach ($rows as $row) {
            $date = Carbon::parse($row->kun)->toDateString();

            if (!isset($days[$date][$row->turi])) {
                continue;
            }

            $days[$date][$row->turi] = ['soni' => (int) $row->soni, 'narx' => (float) $row->narx];
            $days[$date]['jami'] += (float) $row->narx;
        }

        return $days;
    }

    private function topUsers(Carbon $since)
    {
        return AiUsageLog::where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as soni, SUM(CASE WHEN turi = ? THEN 1 ELSE 0 END) as vision_soni, SUM(narx_usd) as narx', [AiUsageLog::TYPE_VISION])
            ->groupBy('user_id')
            ->orderByDesc('narx')
            ->limit(10)
            ->with('user.plan')
            ->get();
    }

    /**
     * Har bir tarif bo'yicha bitta foydalanuvchiga o'rtacha AI xarajati (foydalanuvchining hozirgi tarifi bo'yicha).
     */
    private function planAverages(Carbon $from, Carbon $to): array
    {
        $plans = Plan::withCount('users')->orderBy('max_hayvon')->get();
        $freeId = $plans->firstWhere('kod', Plan::FREE)?->id;

        $costs = DB::table('ai_usage_logs')
            ->join('users', 'users.id', '=', 'ai_usage_logs.user_id')
            ->where('ai_usage_logs.created_at', '>=', $from)
            ->where('ai_usage_logs.created_at', '<', $to)
            ->groupBy('users.plan_id')
            ->selectRaw('users.plan_id, SUM(ai_usage_logs.narx_usd) as narx, COUNT(DISTINCT ai_usage_logs.user_id) as faol')
            ->get();

        $byPlan = [];

        foreach ($costs as $row) {
            // Tarifi belgilanmaganlar bepul tarifga qo'shiladi
            $planId = $row->plan_id ?? $freeId;
            $byPlan[$planId]['narx'] = ($byPlan[$planId]['narx'] ?? 0) + (float) $row->narx;
            $byPlan[$planId]['faol'] = ($byPlan[$planId]['faol'] ?? 0) + (int) $row->faol;
        }

        $withoutPlan = User::whereNull('plan_id')->count();

        return $plans->map(function (Plan $plan) use ($byPlan, $freeId, $withoutPlan) {
            $users = $plan->users_count + ($plan->id === $freeId ? $withoutPlan : 0);
            $cost = $byPlan[$plan->id]['narx'] ?? 0.0;
            $active = $byPlan[$plan->id]['faol'] ?? 0;

            return [
                'plan' => $plan,
                'users' => $users,
                'active' => $active,
                'cost' => $cost,
                'avg_all' => $users > 0 ? $cost / $users : 0.0,
                'avg_active' => $active > 0 ? $cost / $active : 0.0,
            ];
        })->all();
    }

    /**
     * created_at UTC da saqlanadi; mahalliy kunga o'tkazish uchun bazaga mos ifoda.
     * O'zbekistonda yozgi vaqt yo'q, shuning uchun doimiy siljish yetarli.
     */
    private function localDayExpression(string $tz): string
    {
        $offset = (int) now($tz)->utcOffset();

        return match (DB::connection()->getDriverName()) {
            'sqlite' => sprintf("date(created_at, '%+d minutes')", $offset),
            'pgsql' => sprintf("date(created_at + interval '%d minutes')", $offset),
            default => sprintf('DATE(DATE_ADD(created_at, INTERVAL %d MINUTE))', $offset),
        };
    }
}
