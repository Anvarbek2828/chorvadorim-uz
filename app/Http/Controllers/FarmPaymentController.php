<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;

/**
 * Ferma egasi uchun: o'z fermasining to'lovlar tarixi (faqat o'qish uchun, huquq: payments.view).
 */
class FarmPaymentController extends Controller
{
    public function index(SubscriptionService $subscriptions)
    {
        $farm = $this->currentFarm();
        $farm->load('plan');

        return view('farm.payments', [
            'farm' => $farm,
            'status' => $subscriptions->status($farm),
            'payments' => $farm->payments()->with('plan')->orderByDesc('id')->paginate(50),
        ]);
    }
}
