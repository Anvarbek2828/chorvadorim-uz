<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\Farm;
use App\Models\FarmAuditLog;
use App\Models\FarmPayment;
use App\Models\Plan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Ferma obunasi: holat har so'rovda hisoblanadi (bazada saqlanmaydi), sanalar config('ai.timezone') bo'yicha.
 * plan_tugash_sanasi (T) — oxirgi to'langan kun, shu kun ham kiradi.
 */
class SubscriptionService
{
    public const STATUS_FREE = 'free';
    public const STATUS_UNLIMITED = 'muddatsiz';
    public const STATUS_ACTIVE = 'faol';
    public const STATUS_GRACE = 'imtiyoz';
    public const STATUS_READ_ONLY = 'faqat_oqish';

    // T+1 va T+2 — imtiyoz kunlari (hammasi ishlaydi), T+3 dan — faqat o'qish
    public const GRACE_DAYS = 2;

    public const MONTH_OPTIONS = [1, 3, 6, 12];

    // Admin "To'lovlar" bo'limidagi ferma filtrlari
    public const FILTERS = ['hammasi', 'yaqin', self::STATUS_GRACE, self::STATUS_READ_ONLY, self::STATUS_UNLIMITED];

    // "Muddati yaqin" filtri: shuncha kun ichida tugaydigan fermalar
    public const SOON_DAYS = 7;

    public function __construct(
        private AdminAuditService $adminAudit,
        private FarmAuditService $farmAudit,
        private FarmStaffService $staff,
    ) {
    }

    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('ai.timezone'))->startOfDay();
    }

    public function status(Farm $farm): string
    {
        $plan = $farm->plan;

        if (!$plan || $plan->kod === Plan::FREE) {
            return self::STATUS_FREE;
        }

        $daysPastEnd = $this->daysPastEnd($farm);

        return match (true) {
            $daysPastEnd === null => self::STATUS_UNLIMITED,
            $daysPastEnd <= 0 => self::STATUS_ACTIVE,
            $daysPastEnd <= self::GRACE_DAYS => self::STATUS_GRACE,
            default => self::STATUS_READ_ONLY,
        };
    }

    public function isReadOnly(Farm $farm): bool
    {
        return $this->status($farm) === self::STATUS_READ_ONLY;
    }

    /**
     * Bugun tugash sanasidan necha kun o'tgan: 0 — T kuni, manfiy — hali tugamagan. Muddatsiz bo'lsa null.
     */
    public function daysPastEnd(Farm $farm): ?int
    {
        if ($farm->plan_tugash_sanasi === null) {
            return null;
        }

        $end = CarbonImmutable::parse($farm->plan_tugash_sanasi->toDateString(), config('ai.timezone'));

        return (int) round($end->diffInDays($this->today(), false));
    }

    /**
     * Imtiyoz holatida: necha kundan keyin faqat o'qish rejimi boshlanadi (2 yoki 1).
     */
    public function daysUntilReadOnly(Farm $farm): int
    {
        return max(0, self::GRACE_DAYS + 1 - (int) $this->daysPastEnd($farm));
    }

    /**
     * Yangi muddat: faol yoki imtiyozda — T+1 dan davom etadi, aks holda bugundan. Yangi T = boshlanish + N oy - 1 kun.
     */
    public function newEndDate(Farm $farm, int $months): CarbonImmutable
    {
        $start = in_array($this->status($farm), [self::STATUS_ACTIVE, self::STATUS_GRACE], true)
            ? CarbonImmutable::parse($farm->plan_tugash_sanasi->toDateString(), config('ai.timezone'))->addDay()
            : $this->today();

        return $start->addMonthsNoOverflow($months)->subDay();
    }

    /**
     * Admin "To'landi" yoki "+1 oy bonus": tarif darhol o'rnatiladi, muddat uzaytiriladi, jurnallarga yoziladi.
     * Yangi tarifda xodimlar limiti kamroq bo'lsa, ortiqcha xodimlar to'xtatiladi.
     */
    public function extend(Farm $farm, Plan $plan, int $months, string $type, int $amount = 0, ?string $method = null, ?string $document = null, ?string $note = null): FarmPayment
    {
        $before = null;

        $payment = DB::transaction(function () use ($farm, $plan, $months, $type, $amount, $method, $document, $note, &$before) {
            // Ikki admin bir vaqtda uzaytirsa, ikkinchisi birinchisining natijasidan davom etadi
            $farm = Farm::with('plan')->lockForUpdate()->findOrFail($farm->id);
            $before = $this->planState($farm);

            $payment = FarmPayment::create([
                'farm_id' => $farm->id,
                'farm_nomi' => $farm->name,
                'tur' => $type,
                'plan_id' => $plan->id,
                'avvalgi_plan_id' => $farm->plan_id,
                'oylar' => $months,
                'summa' => $amount,
                'usul' => $method,
                'hujjat_raqami' => $document,
                'izoh' => $note,
                'avvalgi_tugash' => $farm->plan_tugash_sanasi?->toDateString(),
                'yangi_tugash' => $this->newEndDate($farm, $months)->toDateString(),
                'admin_id' => auth()->id(),
            ]);

            $this->applyPlan($farm, $plan->id, $payment->yangi_tugash->toDateString());

            return $payment;
        });

        $farm->refresh()->load('plan');
        $after = $this->planState($farm) + ['tolov_id' => $payment->id, 'oylar' => $months, 'summa' => $amount];
        $isBonus = $type === FarmPayment::TYPE_BONUS;

        $this->adminAudit->log(
            $isBonus ? AdminAuditLog::ACTION_SUBSCRIPTION_BONUS : AdminAuditLog::ACTION_SUBSCRIPTION_EXTENDED,
            'farm', $farm->id, $before, $after, null, null, $farm->id
        );
        $this->farmAudit->log(
            $farm,
            $isBonus ? FarmAuditLog::ACTION_SUBSCRIPTION_BONUS : FarmAuditLog::ACTION_SUBSCRIPTION_EXTENDED,
            'subscription', $payment->id, $before, $after
        );

        $this->staff->enforceLimit($farm, $plan);

        return $payment;
    }

    /**
     * Fermaning oxirgi bekor qilinmagan to'lovini bekor qiladi: tarif va tugash sanasi avvalgi qiymatlarga qaytadi.
     * Yozuv o'chirilmaydi. Qaytaradi: xato matni yoki null.
     */
    public function cancel(FarmPayment $payment, string $reason): ?string
    {
        $before = null;
        $farm = null;

        $error = DB::transaction(function () use ($payment, $reason, &$before, &$farm) {
            $farm = $payment->farm_id ? Farm::with('plan')->lockForUpdate()->find($payment->farm_id) : null;

            if (!$farm) {
                return __("Ferma o'chirilgan: to'lovni bekor qilib bo'lmaydi.");
            }

            $payment = FarmPayment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->isCancelled()) {
                return __("Bu to'lov allaqachon bekor qilingan.");
            }

            $lastId = FarmPayment::where('farm_id', $farm->id)->whereNull('bekor_qilingan_at')->max('id');

            if ((int) $lastId !== (int) $payment->id) {
                return __("Faqat fermaning oxirgi to'lovini bekor qilish mumkin.");
            }

            $before = $this->planState($farm);

            $payment->forceFill([
                'bekor_qilingan_at' => now(),
                'bekor_sababi' => $reason,
                'bekor_admin_id' => auth()->id(),
            ])->save();

            // Avvalgi tarif keyinchalik o'chirilgan bo'lsa — tarifsiz (bepul) holat
            $previousPlanId = $payment->avvalgi_plan_id && Plan::whereKey($payment->avvalgi_plan_id)->exists() ? $payment->avvalgi_plan_id : null;
            $this->applyPlan($farm, $previousPlanId, $payment->avvalgi_tugash?->toDateString());

            return null;
        });

        if ($error !== null) {
            return $error;
        }

        $farm->refresh()->load('plan');
        $after = $this->planState($farm) + ['tolov_id' => $payment->id, 'sabab' => $reason];

        $this->adminAudit->log(AdminAuditLog::ACTION_PAYMENT_CANCELLED, 'farm', $farm->id, $before, $after, null, null, $farm->id);
        $this->farmAudit->log($farm, FarmAuditLog::ACTION_PAYMENT_CANCELLED, 'subscription', $payment->id, $before, $after);

        // Avvalgi tarifda xodimlar limiti kamroq bo'lishi mumkin
        $this->staff->enforceLimit($farm);

        return null;
    }

    /**
     * Pullik tarifdagi fermalar, holat bo'yicha filtr (admin "To'lovlar" bo'limi). 'hammasi' — barcha pullik fermalar.
     */
    public function farmsQuery(string $filter): Builder
    {
        $today = $this->today();
        $query = Farm::query()->whereHas('plan', fn ($q) => $q->where('kod', '!=', Plan::FREE));

        return match ($filter) {
            'yaqin' => $query->whereDate('plan_tugash_sanasi', '>=', $today->toDateString())
                ->whereDate('plan_tugash_sanasi', '<=', $today->addDays(self::SOON_DAYS)->toDateString()),
            self::STATUS_GRACE => $query->whereDate('plan_tugash_sanasi', '>=', $today->subDays(self::GRACE_DAYS)->toDateString())
                ->whereDate('plan_tugash_sanasi', '<=', $today->subDay()->toDateString()),
            self::STATUS_READ_ONLY => $query->whereDate('plan_tugash_sanasi', '<=', $today->subDays(self::GRACE_DAYS + 1)->toDateString()),
            self::STATUS_UNLIMITED => $query->whereNull('plan_tugash_sanasi'),
            default => $query,
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_FREE => __('Bepul'),
            self::STATUS_UNLIMITED => __('Muddatsiz'),
            self::STATUS_ACTIVE => __('Faol'),
            self::STATUS_GRACE => __('Imtiyozda'),
            self::STATUS_READ_ONLY => __("Faqat o'qish"),
            default => $status,
        };
    }

    /**
     * Ferma va egasining users dagi eski ustunlari bir xil yangilanadi (Admin\UserController::updatePlan bilan bir xil).
     */
    private function applyPlan(Farm $farm, ?int $planId, ?string $endDate): void
    {
        $farm->plan_id = $planId;
        $farm->plan_tugash_sanasi = $endDate;
        $farm->save();

        $owner = $farm->owner;

        if ($owner) {
            $owner->plan_id = $planId ?? Plan::free()->id;
            $owner->plan_tugash_sanasi = $endDate;
            $owner->save();
        }
    }

    private function planState(Farm $farm): array
    {
        return [
            'plan' => $farm->plan?->kod,
            'plan_tugash_sanasi' => $farm->plan_tugash_sanasi?->toDateString(),
        ];
    }
}
