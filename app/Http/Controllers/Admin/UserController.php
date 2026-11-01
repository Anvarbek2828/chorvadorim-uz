<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\AiUsageLog;
use App\Models\Farm;
use App\Models\Plan;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\AiUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request, AiUsageService $usage)
    {
        $search = trim((string) $request->query('q', ''));
        $monthStart = $usage->monthStart();

        $users = User::query()
            ->with('plan')
            ->withCount('animals')
            ->withCount([
                'aiUsageLogs as month_chat' => fn ($q) => $q->where('turi', AiUsageLog::TYPE_CHAT)->where('created_at', '>=', $monthStart),
                'aiUsageLogs as month_vision' => fn ($q) => $q->where('turi', AiUsageLog::TYPE_VISION)->where('created_at', '>=', $monthStart),
            ])
            ->withSum(['aiUsageLogs as month_cost' => fn ($q) => $q->where('created_at', '>=', $monthStart)], 'narx_usd')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';

                // users jadvalida telefon ustuni yo'q: ism, email, Telegram ID va ID bo'yicha qidiriladi
                $query->where(function ($q) use ($like, $search) {
                    $q->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('telegram_chat_id', $search);

                    if (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'plans' => Plan::orderBy('max_hayvon')->get(),
            'rate' => (float) config('ai.usd_to_som'),
        ]);
    }

    public function updatePlan(Request $request, User $user, AdminAuditService $audit)
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'plan_tugash_sanasi' => ['nullable', 'date'],
        ]);

        // Tarif fermaga tegishli: foydalanuvchi egasi bo'lgan ferma yangilanadi
        $farm = $user->ownedFarms()->orderBy('id')->first();

        if (!$farm) {
            return back()->with('error', __("Bu foydalanuvchining o'z fermasi yo'q."));
        }

        $farm->load('plan');
        $old = $this->planState($farm);

        // users dagi eski ustunlar ham bir xil yangilanadi: admin statistikasi ularga tayanadi
        // va kod orqaga qaytarilsa, oxirgi berilgan tarif yo'qolmaydi
        DB::transaction(function () use ($farm, $user, $validated) {
            foreach ([$farm, $user] as $model) {
                $model->plan_id = (int) $validated['plan_id'];
                $model->plan_tugash_sanasi = $validated['plan_tugash_sanasi'] ?? null;
                $model->save();
            }
        });

        $farm->load('plan');

        $audit->log(AdminAuditLog::ACTION_USER_PLAN_CHANGED, 'user', $user->id, $old, $this->planState($farm), null, null, $farm->id);

        return back()->with('success', __(':name uchun tarif saqlandi.', ['name' => $user->name]));
    }

    public function toggleBlock(User $user, AdminAuditService $audit)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', __("O'zingizni bloklay olmaysiz."));
        }

        if ($user->is_admin && !$user->is_blocked) {
            return back()->with('error', __("Adminni bloklab bo'lmaydi. Avval admin huquqini olib tashlang."));
        }

        $wasBlocked = $user->is_blocked;
        $user->is_blocked = !$wasBlocked;
        $user->save();

        $audit->log(
            $wasBlocked ? AdminAuditLog::ACTION_USER_UNBLOCKED : AdminAuditLog::ACTION_USER_BLOCKED,
            'user',
            $user->id,
            ['is_blocked' => $wasBlocked],
            ['is_blocked' => $user->is_blocked],
            null,
            null,
            $user->ownedFarms()->orderBy('id')->value('id')
        );

        return back()->with('success', $user->is_blocked
            ? __(':name bloklandi.', ['name' => $user->name])
            : __(':name blokdan chiqarildi.', ['name' => $user->name]));
    }

    private function planState(Farm $farm): array
    {
        return [
            'plan' => $farm->plan?->kod,
            'plan_tugash_sanasi' => $farm->plan_tugash_sanasi?->toDateString(),
        ];
    }
}
