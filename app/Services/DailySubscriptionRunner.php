<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Cron'siz kunlik ishlar: har web/bot so'rovidan keyin (javob yuborilgach) chaqiriladi.
 * Har ish system_runs dagi o'z qatori bo'yicha kuniga bir marta, Toshkent vaqti bilan 08:00 dan keyin ishlaydi.
 */
class DailySubscriptionRunner
{
    public const RUN_SUBSCRIPTIONS = 'obuna';
    public const RUN_REMINDERS = 'eslatmalar';

    public const START_HOUR = 8;

    public function __construct(
        private SubscriptionNotifier $subscriptions,
        private ReminderNotifier $reminders,
    ) {
    }

    /**
     * @return array<string, array|null> ish nomi => natija (shu chaqiruvda ishlamagan bo'lsa null)
     */
    public function runIfDue(): array
    {
        return [
            self::RUN_SUBSCRIPTIONS => $this->runOnce(self::RUN_SUBSCRIPTIONS, fn () => $this->subscriptions->run()),
            self::RUN_REMINDERS => $this->runOnce(self::RUN_REMINDERS, fn () => $this->reminders->run()),
        ];
    }

    private function runOnce(string $name, callable $job): ?array
    {
        $now = now(config('ai.timezone'));

        if ($now->hour < self::START_HOUR || !$this->claim($name, $now->toDateString())) {
            return null;
        }

        try {
            $result = $job();
        } catch (Throwable $e) {
            report($e);
            $result = ['xato' => mb_substr($e->getMessage(), 0, 500)];
        }

        DB::table('system_runs')->where('nom', $name)->update([
            'last_finished_at' => now(),
            'last_result' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ]);

        return $result;
    }

    /**
     * Atomik: bir vaqtdagi bir nechta so'rovdan faqat bittasi qatorni o'zgartiradi va ishni bajaradi.
     */
    private function claim(string $name, string $today): bool
    {
        return DB::table('system_runs')
            ->where('nom', $name)
            ->where(fn ($q) => $q->whereNull('last_run_date')->orWhere('last_run_date', '<', $today))
            ->update(['last_run_date' => $today, 'last_started_at' => now()]) === 1;
    }
}
