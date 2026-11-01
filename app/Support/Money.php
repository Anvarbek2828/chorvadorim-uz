<?php

namespace App\Support;

class Money
{
    /**
     * AI xarajatlari juda kichik bo'lishi mumkin, shuning uchun 1 dollardan kam summalar 4 xonali ko'rsatiladi.
     */
    public static function usd(float $amount): string
    {
        $decimals = $amount > 0 && $amount < 1 ? 4 : 2;

        return '$' . number_format($amount, $decimals, '.', ' ');
    }

    public static function som(float $usdAmount): string
    {
        $som = $usdAmount * (float) config('ai.usd_to_som');

        return number_format($som, 0, '.', ' ') . ' ' . __("so'm");
    }
}
