<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmPayment;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

/**
 * Admin "To'lovlar" bo'limi: fermalar holat bo'yicha filtr va to'lovlar ro'yxati; to'lovni bekor qilish.
 */
class PaymentController extends Controller
{
    public function index(Request $request, SubscriptionService $subscriptions)
    {
        $filter = in_array($request->query('filtr'), SubscriptionService::FILTERS, true) ? $request->query('filtr') : 'hammasi';

        // Holat filtri tanlanganda: mos fermalar ro'yxati va faqat ularning to'lovlari
        $farms = $filter === 'hammasi'
            ? null
            : $subscriptions->farmsQuery($filter)->with(['plan', 'owner'])->orderBy('plan_tugash_sanasi')->orderBy('id')->get();

        $payments = FarmPayment::with(['plan', 'admin', 'farm'])
            ->when($farms !== null, fn ($q) => $q->whereIn('farm_id', $farms->pluck('id')))
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.payments.index', [
            'filter' => $filter,
            'farms' => $farms,
            'payments' => $payments,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Faqat fermaning oxirgi bekor qilinmagan yozuvi; sabab majburiy.
     */
    public function cancel(Request $request, FarmPayment $payment, SubscriptionService $subscriptions)
    {
        $validated = $request->validate([
            'sabab' => ['required', 'string', 'max:2000'],
        ]);

        $error = $subscriptions->cancel($payment, trim($validated['sabab']));

        if ($error !== null) {
            return back()->with('error', $error);
        }

        return back()->with('success', __("To'lov bekor qilindi. Tarif va tugash sanasi avvalgi holatga qaytarildi."));
    }
}
