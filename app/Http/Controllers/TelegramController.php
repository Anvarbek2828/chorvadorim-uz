<?php



namespace App\Http\Controllers;



use App\Models\Animal;

use App\Models\BankAnimalWatcher;

use App\Models\Farm;

use App\Models\TelegramLinkCode;

use App\Models\User;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\RateLimiter;



class TelegramController extends Controller

{

    private const LINK_CODE_TTL_MINUTES = 10;

    private const LINK_CODE_MAX_ATTEMPTS = 5;

    private const LINK_CODE_DECAY_SECONDS = 15 * 60;

    private const WATCH_REQUEST_DECAY_SECONDS = 60 * 60;

    public function webhook(Request $request)

    {

        $secret = (string) config('services.telegram.webhook_secret');
        $incoming = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        // Token sozlanmagan bo'lsa ham so'rov rad etiladi (fail-closed)
        if ($secret === '' || !hash_equals($secret, $incoming)) {
            return response()->json(['ok' => false], 403);
        }

        $callback = $request->input('callback_query');

        if (is_array($callback)) {
            $this->handleCallback($callback);
            return response()->json(['ok' => true]);
        }



        $message = $request->input('message');



        if (!$message || !isset($message['text']) || !isset($message['chat']['id'])) {

            return response()->json(['ok' => true]);

        }



        $chatId = (string) $message['chat']['id'];

        $text = trim($message['text']);



        if ($text === '/start') {

            $this->sendMessage($chatId, $this->welcomeText());

            return response()->json(['ok' => true]);

        }



        if ($text === '/hayvonlarim') {

            $this->handleMyAnimals($chatId);

            return response()->json(['ok' => true]);

        }



        if (str_starts_with($text, '/kuzatish')) {

            $this->handleBankWatch($chatId, trim(str_replace('/kuzatish', '', $text)), $message['from'] ?? []);

            return response()->json(['ok' => true]);

        }

        if ($text === '/kuzatuvchilar') {
            $this->handleWatcherList($chatId);
            return response()->json(['ok' => true]);
        }



        if (preg_match('/^\d{6}$/', $text)) {

            $this->handleLinkCode($chatId, $text);

            return response()->json(['ok' => true]);

        }



        $this->handleFreeTextQuery($chatId, $text);



        return response()->json(['ok' => true]);

    }



    public function generateLinkCode(Request $request)

    {

        $user = auth()->user();



        TelegramLinkCode::where('user_id', $user->id)->delete();

        // Muddati o'tgan kodlar ham tozalanadi ('code' ustuni unique)
        TelegramLinkCode::where('expires_at', '<=', now())->delete();



        do {

            $code = (string) random_int(100000, 999999);

        } while (TelegramLinkCode::where('code', $code)->exists());

        $expiresAt = now()->addMinutes(self::LINK_CODE_TTL_MINUTES);



        TelegramLinkCode::create([

            'code' => $code,

            'user_id' => $user->id,

            'expires_at' => $expiresAt,

        ]);



        return response()->json([

            'code' => $code,

            'expires_at' => $expiresAt->toIso8601String(),

        ]);

    }



    private function handleLinkCode(string $chatId, string $code)

    {

        $limiterKey = 'telegram-link-code:' . $chatId;

        if (RateLimiter::tooManyAttempts($limiterKey, self::LINK_CODE_MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($limiterKey) / 60);
            $this->sendMessage($chatId, $this->trilingual('Juda ko\'p noto\'g\'ri urinish. Bog\'lash vaqtincha bloklandi. :minutes daqiqadan keyin qayta urinib ko\'ring.', ['minutes' => max(1, $minutes)]));
            return;
        }

        $linkCode = TelegramLinkCode::where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        // Kod bir martalik: avval o'chiriladi, o'chirish muvaffaqiyatli bo'lsagina bog'lanadi
        $consumed = $linkCode && TelegramLinkCode::whereKey($linkCode->id)->delete() === 1;

        if (!$consumed) {
            RateLimiter::hit($limiterKey, self::LINK_CODE_DECAY_SECONDS);
            $this->sendMessage($chatId, $this->trilingual('Kod noto\'g\'ri yoki muddati tugagan. Saytdan yangi kod oling.'));
            return;
        }

        RateLimiter::clear($limiterKey);



        $user = $linkCode->user;

        $user->telegram_chat_id = $chatId;

        $user->save();



        $check = "\u{2705}";

        $this->sendMessage($chatId, "{$check} Muvaffaqiyatli bog'landi, {$user->name}!\n\nEndi quyidagi buyruqlardan foydalanishingiz mumkin:\n/hayvonlarim - hayvonlaringiz ro'yxati\nHayvon nomini yozing - o'sha hayvon holatini ko'rish");

    }



    private function handleBankWatch(string $chatId, string $number, array $from = [])

    {

        if ($number === '') {

            $this->sendMessage($chatId, "Iltimos, hayvon pasport yoki chip raqamini kiriting.\nMasalan: /kuzatish AA1234567");

            return;

        }



        $animals = Animal::where('passport_number', $number)

            ->orWhere('chip_number', $number)

            ->limit(2)

            ->get();



        // Raqam bir nechta hayvonga mos kelsa, noto'g'ri hayvon ko'rsatilmasligi uchun hech biri tanlanmaydi
        if ($animals->count() !== 1) {

            $this->sendMessage($chatId, "Bunday pasport/chip raqamiga ega hayvon topilmadi: {$number}");

            return;

        }

        $animal = $animals->first();
        // So'rov ferma egasiga boradi
        $owner = $animal->farm?->owner ?? $animal->user;

        if (!$owner || !$owner->telegram_chat_id) {
            $this->sendMessage($chatId, $this->trilingual("Hayvon egasi Telegram botga ulanmagan, shuning uchun kuzatuv so'rovi rad etildi."));
            return;
        }

        $watcher = BankAnimalWatcher::firstOrNew([
            'telegram_chat_id' => $chatId,
            'animal_id' => $animal->id,
        ]);

        // Fermer avval ruxsat bergan bo'lsa, ma'lumot beriladi
        if ($watcher->exists && $watcher->status === BankAnimalWatcher::STATUS_APPROVED) {
            $this->sendMessage($chatId, $this->formatAnimalStatus($animal));
            return;
        }

        // Fermerga bir xil so'rov soatiga bir martadan ko'p yuborilmaydi
        $requestLimiterKey = 'telegram-watch-request:' . $chatId . ':' . $animal->id;

        if (RateLimiter::tooManyAttempts($requestLimiterKey, 1)) {
            $this->sendMessage($chatId, $this->trilingual("So'rov allaqachon yuborilgan. Hayvon egasining javobini kuting."));
            return;
        }

        RateLimiter::hit($requestLimiterKey, self::WATCH_REQUEST_DECAY_SECONDS);

        $watcher->status = BankAnimalWatcher::STATUS_PENDING;
        $watcher->requester_name = $this->requesterName($from, $chatId);
        $watcher->responded_at = null;
        $watcher->save();

        $this->sendMessage(
            $owner->telegram_chat_id,
            "\u{1F3E6} " . $this->trilingual(
                "Kuzatuv so'rovi. :requester sizning \":animal\" (raqami: :number) hayvoningizni kuzatishni so'rayapti. Ruxsat bersangiz, u hayvon holatini ko'radi va hayvon o'chirilsa yoki tiklansa xabar oladi. Diqqat: so'rovchining ismi uning Telegram profilidan olingan va tekshirilmagan.",
                [
                    'requester' => $watcher->requester_name,
                    'animal' => $animal->name,
                    'number' => $animal->passport_number ?? $animal->chip_number,
                ]
            ),
            [
                'inline_keyboard' => [
                    [['text' => "\u{2705} " . $this->trilingualLabel('Ruxsat berish'), 'callback_data' => 'watch:approve:' . $watcher->id]],
                    [['text' => "\u{274C} " . $this->trilingualLabel('Rad etish'), 'callback_data' => 'watch:reject:' . $watcher->id]],
                ],
            ]
        );

        $this->sendMessage($chatId, $this->trilingual("So'rov hayvon egasiga yuborildi. Ruxsat berilgach, sizga xabar keladi."));

    }

    private function handleWatcherList(string $chatId)
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $this->sendMessage($chatId, $this->trilingual("Avval hisobingizni bog'lashingiz kerak. Saytdagi bog'lash kodini shu yerga yuboring."));
            return;
        }

        if (!$farm = $this->farmFor($user, $chatId)) {
            return;
        }

        // Kuzatuvga ruxsat berish/bekor qilish — ferma egasining ishi
        if (!$user->canInFarm('farm.settings')) {
            $this->sendMessage($chatId, $this->trilingual("Bu amal uchun huquqingiz yo'q."));
            return;
        }

        $watchers = BankAnimalWatcher::where('status', BankAnimalWatcher::STATUS_APPROVED)
            ->whereHas('animal', fn ($q) => $q->withTrashed()->where('farm_id', $farm->id))
            ->with(['animal' => fn ($q) => $q->withTrashed()])
            ->limit(50)
            ->get();

        if ($watchers->isEmpty()) {
            $this->sendMessage($chatId, $this->trilingual("Hozir hayvonlaringizni hech kim kuzatmayapti."));
            return;
        }

        $keyboard = $watchers->map(fn ($watcher) => [[
            'text' => mb_strimwidth("\u{274C} {$watcher->animal->name} — " . ($watcher->requester_name ?? 'ID ' . $watcher->telegram_chat_id), 0, 60, '…'),
            'callback_data' => 'watch:revoke:' . $watcher->id,
        ]])->all();

        $this->sendMessage(
            $chatId,
            $this->trilingual("Hayvonlaringizni kuzatayotganlar ro'yxati. Kuzatuvni bekor qilish uchun tegishli tugmani bosing."),
            ['inline_keyboard' => $keyboard]
        );
    }

    private function handleCallback(array $callback)
    {
        $callbackId = (string) ($callback['id'] ?? '');
        $fromId = (string) ($callback['from']['id'] ?? '');
        $data = (string) ($callback['data'] ?? '');
        $message = $callback['message'] ?? null;

        if (!preg_match('/^watch:(approve|reject|revoke):(\d+)$/', $data, $matches)) {
            $this->answerCallback($callbackId);
            return;
        }

        [, $action, $watcherId] = $matches;

        $watcher = BankAnimalWatcher::with(['animal' => fn ($q) => $q->withTrashed(), 'animal.farm.owner', 'animal.user'])->find($watcherId);
        $animal = $watcher?->animal;
        $owner = $animal?->farm?->owner ?? $animal?->user;

        $expectedStatus = $action === 'revoke' ? BankAnimalWatcher::STATUS_APPROVED : BankAnimalWatcher::STATUS_PENDING;

        // Faqat hayvon egasi va faqat kutilgan holatdagi so'rovga javob bera oladi
        if (!$owner || (string) $owner->telegram_chat_id !== $fromId || $watcher->status !== $expectedStatus) {
            $this->answerCallback($callbackId, $this->trilingualLabel("Bu so'rov endi amal qilmaydi."));
            $this->removeButtons($message);
            return;
        }

        $number = $animal->passport_number ?? $animal->chip_number;

        $watcher->status = match ($action) {
            'approve' => BankAnimalWatcher::STATUS_APPROVED,
            'reject' => BankAnimalWatcher::STATUS_REJECTED,
            'revoke' => BankAnimalWatcher::STATUS_REVOKED,
        };
        $watcher->responded_at = now();
        $watcher->save();

        $this->answerCallback($callbackId);
        $this->removeButtons($message);

        if ($action === 'approve') {
            $this->sendMessage($fromId, $this->trilingual("Ruxsat berildi. Kuzatuvni istalgan vaqtda /kuzatuvchilar buyrug'i orqali bekor qilishingiz mumkin."));
            $this->sendMessage($watcher->telegram_chat_id, $this->trilingual("Hayvon egasi kuzatuvga ruxsat berdi.") . "\n\n" . $this->formatAnimalStatus($animal));
        } elseif ($action === 'reject') {
            $this->sendMessage($fromId, $this->trilingual("So'rov rad etildi."));
            $this->sendMessage($watcher->telegram_chat_id, $this->trilingual("Hayvon egasi :number raqamli hayvonni kuzatish so'rovini rad etdi.", ['number' => $number]));
        } else {
            $this->sendMessage($fromId, $this->trilingual("Kuzatuv bekor qilindi."));
            $this->sendMessage($watcher->telegram_chat_id, $this->trilingual("Hayvon egasi :number raqamli hayvonni kuzatishga bergan ruxsatini bekor qildi.", ['number' => $number]));
        }
    }

    private function requesterName(array $from, string $chatId): string
    {
        $name = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));

        if (!empty($from['username'])) {
            $name = trim($name . ' (@' . $from['username'] . ')');
        }

        $name = trim($name . ', ID ' . $chatId, ', ');

        return mb_substr($name, 0, 255);
    }

    /**
     * Bot foydalanuvchining tilini bilmaydi, shuning uchun xabar uch tilda yuboriladi.
     */
    private function trilingual(string $key, array $replace = []): string
    {
        return collect(['uz', 'uz_cyrl', 'ru'])
            ->map(fn ($locale) => __($key, $replace, $locale))
            ->implode("\n\n");
    }

    private function trilingualLabel(string $key): string
    {
        return collect(['uz', 'uz_cyrl', 'ru'])
            ->map(fn ($locale) => __($key, [], $locale))
            ->implode(' / ');
    }



    private function handleMyAnimals(string $chatId)

    {

        $user = User::where('telegram_chat_id', $chatId)->first();



        if (!$user) {

            $this->sendMessage($chatId, "Avval hisobingizni bog'lashingiz kerak. Saytdagi bog'lash kodini shu yerga yuboring.");

            return;

        }



        if (!$farm = $this->farmFor($user, $chatId)) {
            return;
        }

        $animals = $farm->animals;



        if ($animals->isEmpty()) {

            $this->sendMessage($chatId, "Sizda hali hayvon qo'shilmagan.");

            return;

        }



        $cow = "\u{1F404}";

        $text = "{$cow} Sizning hayvonlaringiz:\n\n";

        foreach ($animals as $animal) {

            $text .= "- {$animal->name}";

            if ($animal->current_weight) {

                $text .= " - {$animal->current_weight} kg";

            }

            $text .= "\n";

        }

        $text .= "\nBatafsil ma'lumot uchun hayvon nomini yozing.";



        $this->sendMessage($chatId, $text);

    }



    private function handleFreeTextQuery(string $chatId, string $text)

    {

        $user = User::where('telegram_chat_id', $chatId)->first();



        // Bog'langan foydalanuvchi — joriy fermasining hayvonlari ichidan
        $farm = $user?->activeFarm();

        if ($farm && $user->canInFarm('animals.view')) {

            $animal = $farm->animals()->where('name', 'like', "%{$text}%")->first();

            if ($animal) {

                $this->sendMessage($chatId, $this->formatAnimalStatus($animal));

                return;

            }

        }



        $watchedAnimalIds = BankAnimalWatcher::where('telegram_chat_id', $chatId)
            ->where('status', BankAnimalWatcher::STATUS_APPROVED)
            ->pluck('animal_id');

        if ($watchedAnimalIds->isNotEmpty()) {

            $animal = Animal::whereIn('id', $watchedAnimalIds)

                ->where(function ($q) use ($text) {

                    $q->where('passport_number', $text)->orWhere('chip_number', $text);

                })

                ->first();

            if ($animal) {

                $this->sendMessage($chatId, $this->formatAnimalStatus($animal));

                return;

            }

        }



        $this->sendMessage($chatId, "Tushunmadim. /start buyrug'ini yuborib, ko'rsatmalarni qayta o'qing.");

    }



    /**
     * Bog'langan foydalanuvchining joriy fermasi; faol a'zoligi bo'lmasa xabar yuborib null qaytaradi.
     */
    private function farmFor(User $user, string $chatId): ?Farm
    {
        $farm = $user->activeFarm();

        if (!$farm || !$user->canInFarm('animals.view')) {
            $this->sendMessage($chatId, $this->trilingual("Siz hech qaysi fermaga a'zo emassiz yoki a'zoligingiz to'xtatilgan. Ferma egasiga yoki administratsiyaga murojaat qiling."));

            return null;
        }

        return $farm;
    }

    private function formatAnimalStatus(Animal $animal): string

    {

        $warning = "\u{26A0}";

        $deleted = $animal->trashed() ? "\n\n{$warning} DIQQAT: Bu hayvon fermer tomonidan o'chirilgan (sotilgan yoki tizimdan olib tashlangan bo'lishi mumkin)." : "";



        $directionText = match ($animal->direction) {

            'sut' => 'Sut',

            'gosht' => "Go'sht",

            'nasl' => 'Nasl',

            default => '',

        };



        $cow = "\u{1F404}";

        $text = "{$cow} {$animal->name}\n";

        $text .= "Yo'nalish: {$directionText}\n";

        if ($animal->breed) {

            $text .= "Zoti: {$animal->breed}\n";

        }

        if ($animal->current_weight) {

            $text .= "Joriy vazni: {$animal->current_weight} kg\n";

        }



        $lastPhoto = $animal->photoAnalyses()->latest()->first();

        if ($lastPhoto) {

            $camera = "\u{1F4F7}";

            $text .= "\n{$camera} So'nggi rasm tahlili ({$lastPhoto->created_at->format('d.m.Y')}):\n";

            if ($lastPhoto->bcs_score) {

                $text .= "BCS: {$lastPhoto->bcs_score}/5\n";

            }

            if ($lastPhoto->estimated_weight) {

                $text .= "Taxminiy vazn: {$lastPhoto->estimated_weight} kg\n";

            }

        }



        $text .= $deleted;



        return $text;

    }



    private function welcomeText(): string

    {

        $bank = "\u{1F3E6}";

        return "Assalomu alaykum! Bu - Chorvadorim.uz rasmiy boti.\n\n"

            . "FERMER bo'lsangiz:\n"

            . "Saytdagi shaxsiy sahifangizdan 6 xonali bog'lash kodini oling va shu yerga yuboring (kod 10 daqiqa amal qiladi).\n\n"

            . "{$bank} BANK vakili bo'lsangiz:\n"

            . "/kuzatish buyrug'idan keyin hayvon pasport yoki chip raqamini yuboring.\n"

            . "Masalan: /kuzatish AA1234567";

    }



    private function sendMessage(string $chatId, string $text, ?array $replyMarkup = null)

    {

        $params = [

            'chat_id' => $chatId,

            'text' => $text,

        ];

        if ($replyMarkup) {
            $params['reply_markup'] = $replyMarkup;
        }

        $this->telegramRequest('sendMessage', $params);

    }

    private function answerCallback(string $callbackId, ?string $text = null)
    {
        if ($callbackId === '') {
            return;
        }

        $this->telegramRequest('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackId,
            'text' => $text,
        ]));
    }

    private function removeButtons(?array $message)
    {
        if (!isset($message['chat']['id'], $message['message_id'])) {
            return;
        }

        $this->telegramRequest('editMessageReplyMarkup', [
            'chat_id' => $message['chat']['id'],
            'message_id' => $message['message_id'],
            'reply_markup' => ['inline_keyboard' => []],
        ]);
    }

    private function telegramRequest(string $method, array $params)
    {
        $token = config('services.telegram.bot_token');

        Http::post("https://api.telegram.org/bot{$token}/{$method}", $params);
    }

}

