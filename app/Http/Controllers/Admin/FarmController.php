<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmPayment;
use App\Models\FarmUser;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Admin: ferma sahifasi — obuna bloki (To'landi, +1 oy bonus, to'lovsiz tarif almashtirish, Bekor qilish), to'lovlar tarixi, a'zolar (faqat ko'rish).
 */
class FarmController extends Controller
{
    public function show(Farm $farm, SubscriptionService $subscriptions)
    {
        $farm->load(['plan', 'owner']);

        $payments = $farm->payments()->with(['plan', 'admin', 'cancelledBy'])->orderByDesc('id')->get();

        return view('admin.farms.show', [
            'farm' => $farm,
            'status' => $subscriptions->status($farm),
            'payments' => $payments,
            // Faqat oxirgi bekor qilinmagan yozuvni bekor qilish mumkin
            'cancellableId' => $payments->first(fn (FarmPayment $payment) => !$payment->isCancelled())?->id,
            'members' => $farm->memberships()
                ->with('user')
                ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [FarmUser::ROLE_OWNER])
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(),
            'paidPlans' => $this->paidPlans(),
            'allPlans' => Plan::orderBy('max_hayvon')->get(),
        ]);
    }

    /**
     * "Tarifni almashtirish (to'lovsiz)": tarif (Free ham), tugash sanasi (bo'sh — muddatsiz), sabab majburiy.
     */
    public function changePlan(Request $request, Farm $farm, SubscriptionService $subscriptions)
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'plan_tugash_sanasi' => ['nullable', 'date_format:Y-m-d'],
            'sabab' => ['required', 'string', 'max:2000'],
        ]);

        $suspended = $subscriptions->changePlan(
            $farm,
            Plan::findOrFail($validated['plan_id']),
            $validated['plan_tugash_sanasi'] ?? null,
            trim($validated['sabab']),
        );

        $message = __('Tarif almashtirildi');

        if ($suspended > 0) {
            $message .= '. ' . __("Tarif limitidan ortiq :count ta xodim to'xtatildi.", ['count' => $suspended]);
        }

        return redirect()->route('admin.farms.show', $farm)->with('success', $message);
    }

    /**
     * "To'landi": tarif (Free emas), oylar, summa, usul, hujjat raqami, izoh.
     */
    public function storePayment(Request $request, Farm $farm, SubscriptionService $subscriptions)
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', $this->paidPlanRule()],
            'oylar' => ['nullable', 'integer', Rule::in(SubscriptionService::MONTH_OPTIONS)],
            'summa' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            'usul' => ['required', Rule::in(FarmPayment::METHODS)],
            'hujjat_raqami' => ['nullable', 'string', 'max:100'],
            'izoh' => ['nullable', 'string', 'max:2000'],
        ]);

        $months = (int) ($validated['oylar'] ?? 1);

        $payment = $subscriptions->extend(
            $farm,
            Plan::findOrFail($validated['plan_id']),
            $months,
            FarmPayment::TYPE_PAYMENT,
            (int) $validated['summa'],
            $validated['usul'],
            $this->nullIfBlank($validated['hujjat_raqami'] ?? null),
            $this->nullIfBlank($validated['izoh'] ?? null),
        );

        return redirect()->route('admin.farms.show', $farm)->with('success', __("To'lov saqlandi. Yangi tugash sanasi: :date", [
            'date' => $payment->yangi_tugash->format('d.m.Y'),
        ]));
    }

    /**
     * "+1 oy bonus": sabab majburiy, summa 0. Ferma bepul yoki faqat o'qishda bo'lsa — tarif tanlanadi, aks holda joriy tarif.
     */
    public function storeBonus(Request $request, Farm $farm, SubscriptionService $subscriptions)
    {
        $farm->load('plan');
        $status = $subscriptions->status($farm);

        // Muddatsiz fermaga bonus uni 1 oylik muddatli qilib qo'yardi
        if ($status === SubscriptionService::STATUS_UNLIMITED) {
            return back()->with('error', __("Muddatsiz tarifdagi fermaga bonus berilmaydi."));
        }

        $needsPlan = in_array($status, [SubscriptionService::STATUS_FREE, SubscriptionService::STATUS_READ_ONLY], true);

        $validated = $request->validate([
            'sabab' => ['required', 'string', 'max:2000'],
            'plan_id' => [$needsPlan ? 'required' : 'nullable', 'integer', $this->paidPlanRule()],
        ]);

        $plan = $needsPlan ? Plan::findOrFail($validated['plan_id']) : $farm->plan;

        if (!$plan) {
            throw ValidationException::withMessages(['plan_id' => __('Tarifni tanlang.')]);
        }

        $payment = $subscriptions->extend($farm, $plan, 1, FarmPayment::TYPE_BONUS, 0, null, null, trim($validated['sabab']));

        return redirect()->route('admin.farms.show', $farm)->with('success', __("Bonus berildi. Yangi tugash sanasi: :date", [
            'date' => $payment->yangi_tugash->format('d.m.Y'),
        ]));
    }

    private function paidPlans()
    {
        return Plan::where('kod', '!=', Plan::FREE)->orderBy('max_hayvon')->get();
    }

    private function paidPlanRule()
    {
        return Rule::exists('plans', 'id')->where(fn ($q) => $q->where('kod', '!=', Plan::FREE));
    }

    private function nullIfBlank(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
