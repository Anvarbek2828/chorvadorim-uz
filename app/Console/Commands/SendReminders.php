<?php



namespace App\Console\Commands;



use App\Models\Reminder;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;



class SendReminders extends Command

{

    protected $signature = 'reminders:send';

    protected $description = "Muddati kelgan eslatmalarni Telegram orqali fermerlarga yuboradi";



    public function handle()

    {

        $reminders = Reminder::whereNull('sent_at')

            ->whereDate('due_date', '<=', now()->toDateString())

            ->with('animal.user')

            ->get();



        $token = config('services.telegram.bot_token');

        $sentCount = 0;



        foreach ($reminders as $reminder) {

            $user = $reminder->animal->user;



            if (!$user || !$user->telegram_chat_id) {

                continue;

            }



            $bell = "\u{1F514}";

            $text = "{$bell} ESLATMA\n\n\"{$reminder->animal->name}\" - {$reminder->title}\nSana: " . $reminder->due_date->format('d.m.Y');



            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [

                'chat_id' => $user->telegram_chat_id,

                'text' => $text,

            ]);



            $reminder->update(['sent_at' => now()]);

            $sentCount++;

        }



        $this->info("{$sentCount} ta eslatma yuborildi.");



        return 0;

    }

}

