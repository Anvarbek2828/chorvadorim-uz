<?php

namespace App\Services;

use App\Models\FarmUser;
use App\Models\Reminder;

/**
 * Kunlik ish: muddati kelgan eslatmalarni (emlash, davolash va h.k.) Telegram orqali faol ega va menejerlarga yuboradi.
 * Obuna holatiga bog'liq emas: faqat o'qish rejimidagi fermada ham yuboriladi.
 */
class ReminderNotifier
{
    // Faqat due_date oxirgi shuncha kun ichida (bugun ham kiradi) bo'lganlar; eskilari yuborilmaydi va belgilanmaydi
    public const WINDOW_DAYS = 3;

    public function __construct(
        private SubscriptionService $subscriptions,
        private TelegramService $telegram,
    ) {
    }

    /**
     * @return array<string, int> natija (system_runs.last_result)
     */
    public function run(): array
    {
        $result = ['eslatmalar' => 0, 'xabarlar' => 0, 'qabul_qiluvchisiz' => 0, 'xato' => 0];
        $today = $this->subscriptions->today();

        Reminder::query()
            ->whereNull('sent_at')
            ->whereNull('bajarilgan_at')
            ->whereDate('due_date', '>=', $today->subDays(self::WINDOW_DAYS - 1)->toDateString())
            ->whereDate('due_date', '<=', $today->toDateString())
            // Arxivdagi (soft-delete) hayvonlarning eslatmalari o'tkazib yuboriladi
            ->whereHas('animal')
            ->with('animal.farm')
            ->chunkById(100, function ($reminders) use (&$result) {
                foreach ($reminders as $reminder) {
                    $this->send($reminder, $result);
                }
            });

        return $result;
    }

    private function send(Reminder $reminder, array &$result): void
    {
        $farm = $reminder->animal->farm;

        $recipients = $farm
            ? $farm->memberships()
                ->with('user')
                ->whereIn('role', [FarmUser::ROLE_OWNER, FarmUser::ROLE_MANAGER])
                ->where('status', FarmUser::STATUS_ACTIVE)
                ->orderBy('id')
                ->get()
                ->pluck('user')
                ->filter(fn ($user) => $user && $user->telegram_chat_id)
            : collect();

        // Hech kimga yuborilmasa, keyingi kun (oyna ichida) qayta urinadi
        if ($recipients->isEmpty()) {
            $result['qabul_qiluvchisiz']++;
            return;
        }

        $delivered = 0;

        foreach ($recipients as $user) {
            $text = "\u{1F514} " . $this->telegram->textFor($user, "Eslatma: \":animal\" — :title. Sana: :date", [
                'animal' => $reminder->animal->name,
                'title' => $reminder->title,
                'date' => $reminder->due_date->format('d.m.Y'),
            ]);

            $error = $this->telegram->sendMessage($user->telegram_chat_id, $text, [
                'inline_keyboard' => [
                    [['text' => "\u{2705} " . $this->telegram->labelFor($user, 'Bajarildi'), 'callback_data' => 'reminder:done:' . $reminder->id]],
                ],
            ]);

            if ($error === null) {
                $delivered++;
                $result['xabarlar']++;
            } else {
                $result['xato']++;
            }
        }

        if ($delivered > 0) {
            $reminder->update(['sent_at' => now()]);
            $result['eslatmalar']++;
        }
    }
}
