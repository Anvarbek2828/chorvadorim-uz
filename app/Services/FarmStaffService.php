<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\FarmAuditLog;
use App\Models\FarmUser;
use App\Models\Plan;

/**
 * Xodimlar limiti: tarif pasayganda ortiqcha xodimlarni to'xtatish va ferma jurnaliga yozish.
 */
class FarmStaffService
{
    // Vaqtinchalik parol: o'xshash belgilar (0/O, 1/l/I) ishlatilmaydi
    private const PASSWORD_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    private const PASSWORD_LENGTH = 10;

    public function __construct(private FarmAuditService $audit)
    {
    }

    /**
     * Fermadagi faol xodimlar soni tarif limitidan oshsa, eng oxirgi qo'shilganlarni to'xtatadi.
     * $plan — amaldagi tarifning yangi holati (berilmasa Farm::activePlan()). Qaytaradi: to'xtatilganlar soni.
     */
    public function enforceLimit(Farm $farm, ?Plan $plan = null): int
    {
        $limit = ($plan ?? $farm->activePlan())->max_staff;
        $suspended = $farm->suspendExcessStaff($limit);

        foreach ($suspended as $membership) {
            $this->audit->logSystem($farm, FarmAuditLog::ACTION_STAFF_AUTO_SUSPENDED, 'staff', $membership->user_id,
                ['holat' => FarmUser::STATUS_ACTIVE],
                ['holat' => FarmUser::STATUS_SUSPENDED, 'ism' => $membership->user?->name, 'email' => $membership->user?->email, 'limit' => $limit]
            );
        }

        return $suspended->count();
    }

    /**
     * Tarif sozlamasi o'zgarganda: shu tarifdagi xodimli barcha fermalar tekshiriladi. Muddati o'tgan fermalarga
     * tegilmaydi: ularda xodimlar to'xtatilmaydi (imtiyoz yoki faqat o'qish rejimi).
     * Limit $plan dan olinadi: Plan::free() so'rov davomida keshlanadi va eski qiymatni qaytarishi mumkin.
     */
    public function enforceLimitForPlan(Plan $plan): int
    {
        $count = 0;

        Farm::with('plan')
            ->whereHas('memberships', fn ($q) => $q->where('role', '!=', FarmUser::ROLE_OWNER)->where('status', FarmUser::STATUS_ACTIVE))
            ->orderBy('id')
            ->each(function (Farm $farm) use ($plan, &$count) {
                if ((int) $farm->activePlan()->id === (int) $plan->id && !$farm->isPlanExpired()) {
                    $count += $this->enforceLimit($farm, $plan);
                }
            });

        return $count;
    }

    public function generatePassword(): string
    {
        $max = strlen(self::PASSWORD_ALPHABET) - 1;

        do {
            $password = '';

            for ($i = 0; $i < self::PASSWORD_LENGTH; $i++) {
                $password .= self::PASSWORD_ALPHABET[random_int(0, $max)];
            }
            // Kamida bitta harf va bitta raqam
        } while (!preg_match('/\d/', $password) || !preg_match('/[A-Za-z]/', $password));

        return $password;
    }
}
