<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class LandingController extends Controller
{
    /**
     * Bosh sahifa: mehmon va kirgan foydalanuvchi uchun bir xil, faqat tugmalar farq qiladi.
     * Tariflar /tariflar sahifasidagi kabi: faol tariflar, hayvon soni bo'yicha tartibda.
     */
    public function index()
    {
        return view('landing', [
            'plans' => Plan::where('faol', true)->orderBy('max_hayvon')->get(),
            'freePlan' => Plan::free(),
        ]);
    }
}
