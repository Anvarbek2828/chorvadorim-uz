<?php

namespace App\Support;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * 419 (CSRF token eskirgan) — "Page Expired" sahifasi o'rniga tushunarli javob.
 * bootstrap/app.php dagi withExceptions dan chaqiriladi.
 *
 *  - JSON so'rov: null (Laravel odatdagi 419 JSON javobini beradi).
 *  - Chiqish (logout): foydalanuvchi baribir chiqariladi va bosh sahifaga yo'naltiriladi.
 *  - Boshqa formalar: oldingi sahifaga (bo'lmasa — kirgan bo'lsa bosh sahifa, aks holda kirish)
 *    xabar bilan qaytariladi, kiritilgan ma'lumot saqlanadi (parollardan tashqari).
 */
final class PageExpired
{
    public const FLASH_KEY = 'page_expired';

    // Sessiyaga qaytarilmaydigan maydonlar
    private const SECRET_FIELDS = ['_token', '_method', 'password', 'password_confirmation', 'current_password'];

    public static function respond(Request $request): ?RedirectResponse
    {
        if ($request->expectsJson() || ! $request->hasSession()) {
            return null;
        }

        // CSRF tekshiruvi web guruhida SetLocale dan oldin ishlaydi — til bu yerda o'rnatiladi
        $locale = $request->session()->get('locale');
        if (in_array($locale, SetLocale::LOCALES, true)) {
            App::setLocale($locale);
        }

        if ($request->routeIs('logout') || $request->is('logout')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/');
        }

        return redirect()->to(self::backUrl($request))
            ->withInput(Arr::except($request->input(), self::SECRET_FIELDS))
            ->with(self::FLASH_KEY, __("Sahifa eskirgan edi. Iltimos, qayta urinib ko'ring."));
    }

    /**
     * Forma turgan sahifa (Referer), faqat shu saytdan bo'lsa. Aks holda — bosh sahifa yoki kirish.
     * Qaytish har doim GET, shuning uchun Referer forma yuborilgan manzil bilan bir xil bo'lsa ham (/profile) to'g'ri.
     */
    private static function backUrl(Request $request): string
    {
        $referer = (string) $request->headers->get('referer', '');
        $host = parse_url($referer, PHP_URL_HOST);

        if ($referer !== '' && $host === $request->getHost()) {
            return $referer;
        }

        return Auth::check() ? url('/') : route('login');
    }
}
