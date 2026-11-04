<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\FarmAuditLog;
use App\Models\FarmUser;
use App\Models\Plan;
use App\Models\SubscriptionNotification;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Kunlik ish: obuna muddati haqida bot xabarlari (faol ega va menejerlarga) va
 * faqat o'qish rejimiga o'tishni ferma jurnaliga yozish (bajaruvchi — tizim).
 */
class SubscriptionNotifier
{
    // Xabar turi => [bugun - T, oralig'i]. Kech qolgan kun ham shu oraliqda yetkaziladi
    private const WINDOWS = [
        SubscriptionNotification::TYPE_BEFORE => [-3, 0],
        SubscriptionNotification::TYPE_EXPIRED => [1, SubscriptionService::GRACE_DAYS],
        SubscriptionNotification::TYPE_READ_ONLY => [SubscriptionService::GRACE_DAYS + 1, 9],
    ];

    public function __construct(
        private SubscriptionService $subscriptions,
        private TelegramService $telegram,
        private FarmAuditService $audit,
    ) {
    }

    /**
     * @return array<string, int> natija (system_runs.last_result)
     */
    public function run(): array
    {
        $result = ['fermalar' => 0, 'yuborildi' => 0, 'telegram_yoq' => 0, 'xato' => 0, 'faqat_oqish_jurnal' => 0];
        $today = $this->subscriptions->today();

        // Pullik, muddatli va xabar oynasigacha yetgan fermalar (faqat o'qishdagilar jurnal uchun cheklovsiz)
        Farm::with('plan')
            ->whereHas('plan', fn ($q) => $q->where('kod', '!=', Plan::FREE))
            ->whereNotNull('plan_tugash_sanasi')
            ->whereDate('plan_tugash_sanasi', '<=', $today->addDays(3)->toDateString())
            ->chunkById(100, function ($farms) use (&$result) {
                foreach ($farms as $farm) {
                    $result['fermalar']++;
                    $this->processFarm($farm, $result);
                }
            });

        return $result;
    }

    private function processFarm(Farm $farm, array &$result): void
    {
        $daysPastEnd = $this->subscriptions->daysPastEnd($farm);

        if ($daysPastEnd > SubscriptionService::GRACE_DAYS && $this->logReadOnly($farm)) {
            $result['faqat_oqish_jurnal']++;
        }

        foreach (self::WINDOWS as $type => [$from, $to]) {
            if ($daysPastEnd >= $from && $daysPastEnd <= $to) {
                $this->notifyFarm($farm, $type, $daysPastEnd, $result);
            }
        }
    }

    /**
     * Faqat o'qish rejimiga o'tish jurnalga bir marta yoziladi: T+3 boshlanganidan keyin yozuv bo'lsa, qayta yozilmaydi.
     */
    private function logReadOnly(Farm $farm): bool
    {
        $readOnlySince = CarbonImmutable::parse($farm->plan_tugash_sanasi->toDateString(), config('ai.timezone'))
            ->addDays(SubscriptionService::GRACE_DAYS + 1)
            ->utc();

        $logged = FarmAuditLog::where('farm_id', $farm->id)
            ->where('amal', FarmAuditLog::ACTION_READ_ONLY_STARTED)
            ->where('created_at', '>=', $readOnlySince)
            ->exists();

        if ($logged) {
            return false;
        }

        return $this->audit->logSystem($farm, FarmAuditLog::ACTION_READ_ONLY_STARTED, 'subscription', null, [], [
            'tarif' => $farm->plan?->nomi,
            'plan_tugash_sanasi' => $farm->plan_tugash_sanasi->toDateString(),
        ]) !== null;
    }

    private function notifyFarm(Farm $farm, string $type, int $daysPastEnd, array &$result): void
    {
        $endDate = $farm->plan_tugash_sanasi->toDateString();

        $memberships = $farm->memberships()
            ->with('user')
            ->whereIn('role', [FarmUser::ROLE_OWNER, FarmUser::ROLE_MANAGER])
            ->where('status', FarmUser::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();

        foreach ($memberships as $membership) {
            $user = $membership->user;

            if (!$user) {
                continue;
            }

            $notification = SubscriptionNotification::firstOrNew([
                'farm_id' => $farm->id,
                'user_id' => $user->id,
                'tur' => $type,
                'tugash_sanasi' => $endDate,
            ]);

            // Yuborilgan yoki Telegram yo'q — tamom; xato — 3 urinishgacha qayta
            if ($notification->exists && ($notification->holat !== SubscriptionNotification::STATUS_FAILED
                || $notification->urinishlar >= SubscriptionNotification::MAX_ATTEMPTS)) {
                continue;
            }

            if (!$user->telegram_chat_id) {
                $notification->holat = SubscriptionNotification::STATUS_NO_TELEGRAM;
                $notification->save();
                $result['telegram_yoq']++;
                continue;
            }

            $error = $this->telegram->sendMessage($user->telegram_chat_id, $this->message($farm, $user, $type, $daysPastEnd));

            $notification->urinishlar = (int) $notification->urinishlar + 1;
            $notification->holat = $error === null ? SubscriptionNotification::STATUS_SENT : SubscriptionNotification::STATUS_FAILED;
            $notification->xato = $error;
            $notification->save();
            $result[$error === null ? 'yuborildi' : 'xato']++;
        }
    }

    private function message(Farm $farm, User $user, string $type, int $daysPastEnd): string
    {
        $date = $farm->plan_tugash_sanasi->format('d.m.Y');

        $text = match ($type) {
            SubscriptionNotification::TYPE_BEFORE => $daysPastEnd === 0
                ? $this->telegram->textFor($user, 'Obunangiz bugun (:date) tugaydi.', ['date' => $date])
                : $this->telegram->textFor($user, 'Obunangiz :days kundan keyin (:date) tugaydi.', ['days' => -$daysPastEnd, 'date' => $date]),
            // Bu ikki matnda ferma nomi matnning o'zida (:ferma), shuning uchun alohida sarlavha qo'yilmaydi
            SubscriptionNotification::TYPE_EXPIRED => $this->telegram->textFor(
                $user,
                ":ferma — obunangiz muddati tugadi. Iltimos, :days kun ichida to'lovni amalga oshiring, aks holda saytdagi faoliyatingizni to'lov qilinguncha cheklashga majbur bo'lamiz.",
                ['ferma' => $farm->name, 'days' => SubscriptionService::GRACE_DAYS + 1 - $daysPastEnd]
            ),
            default => $this->telegram->textFor(
                $user,
                ":ferma — to'lov qilinmagani sababli saytdagi faoliyatingiz cheklandi. Ma'lumotlaringiz saqlanadi va ko'rinib turadi. To'lov qilinishi bilan hammasi darhol tiklanadi.",
                ['ferma' => $farm->name]
            ),
        };

        if ($type !== SubscriptionNotification::TYPE_BEFORE) {
            return "\u{1F3E0} {$text}";
        }

        return "\u{1F3E0} {$farm->name}\n\n{$text}";
    }
}
