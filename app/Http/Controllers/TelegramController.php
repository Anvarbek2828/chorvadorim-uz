<?php



namespace App\Http\Controllers;



use App\Models\Animal;

use App\Models\BankAnimalWatcher;

use App\Models\TelegramLinkCode;

use App\Models\User;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;



class TelegramController extends Controller

{

    public function webhook(Request $request)

    {

        $secret = config('services.telegram.webhook_secret');

        $incoming = $request->header('X-Telegram-Bot-Api-Secret-Token');



        if ($secret && $incoming !== $secret) {

            return response()->json(['ok' => false], 403);

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

            $this->handleBankWatch($chatId, trim(str_replace('/kuzatish', '', $text)));

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



        do {

            $code = (string) random_int(100000, 999999);

        } while (TelegramLinkCode::where('code', $code)->where('expires_at', '>', now())->exists());



        TelegramLinkCode::create([

            'code' => $code,

            'user_id' => $user->id,

            'expires_at' => now()->addMinutes(3),

        ]);



        return response()->json([

            'code' => $code,

            'expires_at' => now()->addMinutes(3)->toIso8601String(),

        ]);

    }



    private function handleLinkCode(string $chatId, string $code)

    {

        $linkCode = TelegramLinkCode::where('code', $code)->first();



        if (!$linkCode) {

            $this->sendMessage($chatId, "Bunday kod topilmadi. Saytdan yangi kod oling.");

            return;

        }



        if ($linkCode->isExpired()) {

            $this->sendMessage($chatId, "Kod muddati tugagan (3 daqiqadan o'tgan). Saytdan yangi kod oling.");

            $linkCode->delete();

            return;

        }



        $user = $linkCode->user;

        $user->telegram_chat_id = $chatId;

        $user->save();



        $linkCode->delete();



        $check = "\u{2705}";

        $this->sendMessage($chatId, "{$check} Muvaffaqiyatli bog'landi, {$user->name}!\n\nEndi quyidagi buyruqlardan foydalanishingiz mumkin:\n/hayvonlarim - hayvonlaringiz ro'yxati\nHayvon nomini yozing - o'sha hayvon holatini ko'rish");

    }



    private function handleBankWatch(string $chatId, string $number)

    {

        if ($number === '') {

            $this->sendMessage($chatId, "Iltimos, hayvon pasport yoki chip raqamini kiriting.\nMasalan: /kuzatish AA1234567");

            return;

        }



        $animal = Animal::where('passport_number', $number)

            ->orWhere('chip_number', $number)

            ->first();



        if (!$animal) {

            $this->sendMessage($chatId, "Bunday pasport/chip raqamiga ega hayvon topilmadi: {$number}");

            return;

        }



        BankAnimalWatcher::firstOrCreate([

            'telegram_chat_id' => $chatId,

            'animal_id' => $animal->id,

        ]);



        $check = "\u{2705}";

        $this->sendMessage($chatId, "{$check} Kuzatuv boshlandi.\n\n" . $this->formatAnimalStatus($animal));

    }



    private function handleMyAnimals(string $chatId)

    {

        $user = User::where('telegram_chat_id', $chatId)->first();



        if (!$user) {

            $this->sendMessage($chatId, "Avval hisobingizni bog'lashingiz kerak. Saytdagi bog'lash kodini shu yerga yuboring.");

            return;

        }



        $animals = $user->animals;



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



        if ($user) {

            $animal = $user->animals()->where('name', 'like', "%{$text}%")->first();

            if ($animal) {

                $this->sendMessage($chatId, $this->formatAnimalStatus($animal));

                return;

            }

        }



        $watchedAnimalIds = BankAnimalWatcher::where('telegram_chat_id', $chatId)->pluck('animal_id');

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

            . "Saytdagi shaxsiy sahifangizdan 6 xonali bog'lash kodini oling va shu yerga yuboring (kod 3 daqiqa amal qiladi).\n\n"

            . "{$bank} BANK vakili bo'lsangiz:\n"

            . "/kuzatish buyrug'idan keyin hayvon pasport yoki chip raqamini yuboring.\n"

            . "Masalan: /kuzatish AA1234567";

    }



    private function sendMessage(string $chatId, string $text)

    {

        $token = config('services.telegram.bot_token');



        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [

            'chat_id' => $chatId,

            'text' => $text,

        ]);

    }

}

