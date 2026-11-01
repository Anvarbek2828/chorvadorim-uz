<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Plan;
use App\Services\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    private const FIELDS = ['nomi', 'narx_som', 'max_hayvon', 'max_staff', 'oylik_chat_limit', 'oylik_vision_limit', 'chat_model', 'vision_model', 'faol'];

    public function index()
    {
        return view('admin.plans', [
            'plans' => Plan::withCount('users')->orderBy('max_hayvon')->get(),
        ]);
    }

    public function update(Request $request, Plan $plan, AdminAuditService $audit)
    {
        // Faqat config('ai.pricing') da narxi bor modellar; tarifdagi joriy qiymat ro'yxatda bo'lmasa ham saqlab qolish mumkin
        $models = array_keys(config('ai.pricing', []));

        $validated = $request->validate([
            'nomi' => ['required', 'string', 'max:100'],
            'narx_som' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'max_hayvon' => ['required', 'integer', 'min:0', 'max:100000'],
            'max_staff' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'oylik_chat_limit' => ['required', 'integer', 'min:0', 'max:1000000'],
            'oylik_vision_limit' => ['required', 'integer', 'min:0', 'max:1000000'],
            'chat_model' => ['required', 'string', Rule::in(array_filter([...$models, $plan->chat_model]))],
            'vision_model' => ['required', 'string', Rule::in(array_filter([...$models, $plan->vision_model]))],
        ]);

        $validated['narx_som'] = isset($validated['narx_som']) ? (int) $validated['narx_som'] : null;
        // Bo'sh = cheksiz
        $validated['max_staff'] = isset($validated['max_staff']) ? (int) $validated['max_staff'] : null;
        $validated['faol'] = $request->boolean('faol');

        $old = $plan->only(self::FIELDS);

        $plan->update($validated);

        $audit->log(AdminAuditLog::ACTION_PLAN_UPDATED, 'plan', $plan->id, $old, $plan->fresh()->only(self::FIELDS));

        return back()->with('success', __(':name tarifi saqlandi.', ['name' => $plan->nomi]));
    }
}
