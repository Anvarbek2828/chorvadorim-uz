<?php

namespace App\Services;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Telegram Bot API ga so'rovlar va foydalanuvchi tilidagi matnlar.
 */
class TelegramService
{
    /**
     * Qaytaradi: null — muvaffaqiyatli, aks holda xato matni. Istisno tashqariga chiqmaydi.
     */
    public function request(string $method, array $params): ?string
    {
        $token = config('services.telegram.bot_token');

        try {
            $response = Http::timeout(15)->post("https://api.telegram.org/bot{$token}/{$method}", $params);
        } catch (Throwable $e) {
            return mb_substr($e->getMessage(), 0, 500);
        }

        if ($response->successful()) {
            return null;
        }

        return mb_substr('HTTP ' . $response->status() . ': ' . ($response->json('description') ?? $response->body()), 0, 500);
    }

    public function sendMessage(string $chatId, string $text, ?array $replyMarkup = null): ?string
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyMarkup) {
            $params['reply_markup'] = $replyMarkup;
        }

        return $this->request('sendMessage', $params);
    }

    /**
     * Foydalanuvchi tilida (users.locale). Til noma'lum bo'lsa — uch tilda.
     */
    public function textFor(?User $user, string $key, array $replace = []): string
    {
        $locale = $this->localeOf($user);

        return $locale !== null ? __($key, $replace, $locale) : $this->trilingual($key, $replace);
    }

    /**
     * Tugma yozuvi: foydalanuvchi tilida yoki qisqa uch tilli ("A / B / C").
     */
    public function labelFor(?User $user, string $key): string
    {
        $locale = $this->localeOf($user);

        return $locale !== null ? __($key, [], $locale) : $this->trilingualLabel($key);
    }

    public function trilingual(string $key, array $replace = []): string
    {
        return collect(SetLocale::LOCALES)
            ->map(fn ($locale) => __($key, $replace, $locale))
            ->implode("\n\n");
    }

    public function trilingualLabel(string $key): string
    {
        return collect(SetLocale::LOCALES)
            ->map(fn ($locale) => __($key, [], $locale))
            ->implode(' / ');
    }

    private function localeOf(?User $user): ?string
    {
        return in_array($user?->locale, SetLocale::LOCALES, true) ? $user->locale : null;
    }
}
