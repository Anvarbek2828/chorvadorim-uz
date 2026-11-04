<?php



namespace App\Console\Commands;



use App\Services\ReminderNotifier;

use Illuminate\Console\Command;



class SendReminders extends Command

{

    protected $signature = 'reminders:send';



    protected $description = "Muddati kelgan eslatmalarni Telegram orqali fermerlarga yuboradi";



    /**
     * Qo'lda ishga tushirish. Odatda eslatmalar kunlik ish (DailySubscriptionRunner) orqali yuboriladi.
     */
    public function handle(ReminderNotifier $notifier)

    {

        $result = $notifier->run();



        $this->info("{$result['eslatmalar']} ta eslatma yuborildi.");



        return 0;

    }

}
