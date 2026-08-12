<?php

namespace App\Http\Controllers;

use App\Models\Farmer;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TelegramWebhookController
 *
 * @chorvadorim_bot uchun webhook handler.
 * Fermer hisobini bog'lash, hayvon qidirish va bank monitoring
 * xabarnomalarini boshqaradi.
 *
 * Xavfsizlik: har bir so'rov maxfiy webhook token orqali tekshiriladi.
 */
class TelegramWebhookController extends Controller
{
    protected string $botToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Telegramdan kelgan har bir yangilanishni qabul qiladi.
     */
    public function handle(Request $request)
    {
        // Webhook maxfiy tokenini tekshiramiz - faqat Telegram serverlaridan
        // kelgan haqiqiy so'rovlarni qabul qilamiz
        if ($request->header('X-Telegram-Bot-Api-Secret-Token') !== config('services.telegram.webhook_secret')) {
            Log::warning('Telegram webhook: noto\'g\'ri secret token');
            abort(403);
        }

        $update = $request->all();

        if (isset($update['message'])) {
            return $this->handleMessage($update['message']);
        }

        if (isset($update['callback_query'])) {
            return $this->handleCallback($update['callback_query']);
        }

        return response()->json(['ok' => true]);
    }

    protected function handleMessage(array $message)
    {
        $chatId = $message['chat']['id'];
        $text = trim($message['text'] ?? '');

        return match (true) {
            $text === '/start' => $this->sendWelcome($chatId),
            $text === '/bogla' => $this->startBinding($chatId),
            str_starts_with($text, '/hayvon') => $this->searchAnimal($chatId, $text),
            default => $this->routeByState($chatId, $text),
        };
    }

    /**
     * Fermer o'zining Chorvadorim.uz hisobini Telegram akkountiga bog'laydi.
     * Bog'lash kodi web-panelda generatsiya qilinadi, bot orqali tasdiqlanadi.
     */
    protected function startBinding(int $chatId)
    {
        $this->sendMessage($chatId,
            "Hisobingizni bog'lash uchun chorvadorim.uz saytidagi profilingizdan " .
            "olingan 6 xonali kodni yuboring."
        );

        return response()->json(['ok' => true]);
    }

    protected function confirmBinding(int $chatId, string $code)
    {
        $farmer = Farmer::where('telegram_bind_code', $code)
            ->where('telegram_bind_expires_at', '>', now())
            ->first();

        if (!$farmer) {
            $this->sendMessage($chatId, "Kod noto'g'ri yoki muddati o'tgan. Qaytadan urinib ko'ring.");
            return response()->json(['ok' => true]);
        }

        $farmer->update([
            'telegram_chat_id' => $chatId,
            'telegram_bind_code' => null,
        ]);

        $this->sendMessage($chatId, "✅ Hisobingiz muvaffaqiyatli bog'landi, {$farmer->name}!");

        return response()->json(['ok' => true]);
    }

    /**
     * Fermer o'z hayvonini raqami yoki nomi orqali qidiradi.
     */
    protected function searchAnimal(int $chatId, string $text)
    {
        $query = trim(str_replace('/hayvon', '', $text));
        $farmer = Farmer::where('telegram_chat_id', $chatId)->first();

        if (!$farmer) {
            $this->sendMessage($chatId, "Avval hisobingizni bog'lang: /bogla");
            return response()->json(['ok' => true]);
        }

        $animal = Animal::where('farmer_id', $farmer->id)
            ->where(function ($q) use ($query) {
                $q->where('tag_number', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%");
            })
            ->first();

        if (!$animal) {
            $this->sendMessage($chatId, "Hayvon topilmadi: {$query}");
            return response()->json(['ok' => true]);
        }

        $this->sendMessage($chatId, $this->formatAnimalCard($animal));

        return response()->json(['ok' => true]);
    }

    protected function formatAnimalCard(Animal $animal): string
    {
        return "🐄 *{$animal->name}* (#{$animal->tag_number})\n"
            . "Yoshi: {$animal->age_months} oy\n"
            . "So'nggi vaksinatsiya: {$animal->last_vaccination_date}\n"
            . "Holati: {$animal->health_status}";
    }

    /**
     * Telegram Bot API orqali xabar yuboradi.
     */
    protected function sendMessage(int $chatId, string $text): void
    {
        Http::post("{$this->apiUrl}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    protected function sendWelcome(int $chatId)
    {
        $this->sendMessage($chatId,
            "Assalomu alaykum! 👋\n\n" .
            "Men Chorvadorim.uz botiman. Yordam bera olaman:\n" .
            "/bogla - hisobingizni bog'lash\n" .
            "/hayvon [nomi] - hayvon qidirish"
        );

        return response()->json(['ok' => true]);
    }

    protected function routeByState(int $chatId, string $text)
    {
        // Agar foydalanuvchi 6 xonali kod yuborgan bo'lsa - bog'lashni tasdiqlaymiz
        if (preg_match('/^\d{6}$/', $text)) {
            return $this->confirmBinding($chatId, $text);
        }

        $this->sendMessage($chatId, "Buyruqni tushunmadim. /start ni bosing.");
        return response()->json(['ok' => true]);
    }

    protected function handleCallback(array $callbackQuery)
    {
        // Inline tugmalar orqali kelgan javoblarni shu yerda qayta ishlaymiz
        return response()->json(['ok' => true]);
    }
}
