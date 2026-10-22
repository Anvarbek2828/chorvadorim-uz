<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\Animal;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiUsageService
{
    /**
     * AI chaqirilishidan OLDIN tekshiriladi. Ruxsat bo'lsa null, aks holda foydalanuvchiga
     * ko'rsatiladigan xabar va tariflar sahifasiga havola kerakmi — shu qaytadi.
     *
     * @return array{message: string, upgrade: bool}|null
     */
    public function denyReason(User $user, string $type, ?Animal $animal = null): ?array
    {
        if ($this->isPlatformBudgetExceeded()) {
            return [
                'message' => __("AI xizmati vaqtincha to'xtatildi. Iltimos, keyinroq qayta urinib ko'ring."),
                'upgrade' => false,
            ];
        }

        $isVision = $type === AiUsageLog::TYPE_VISION;
        $monthlyLimit = $user->activePlan()->monthlyLimit($type);
        $monthlyUsed = $this->usageCount($user, $type, $this->monthStart());

        if ($monthlyUsed >= $monthlyLimit) {
            $params = [
                'used' => $monthlyUsed,
                'limit' => $monthlyLimit,
                'date' => $this->nextMonthStartLocal()->format('d.m.Y'),
            ];

            return [
                'message' => $isVision
                    ? __("Bu oy uchun rasm tahlili limitingiz tugadi (:used / :limit). Limit :date kuni yangilanadi. Ko'proq tahlil kerak bo'lsa, tarifingizni oshirishingiz mumkin.", $params)
                    : __("Bu oy uchun AI maslahat limitingiz tugadi (:used / :limit). Limit :date kuni yangilanadi. Ko'proq so'rov kerak bo'lsa, tarifingizni oshirishingiz mumkin.", $params),
                'upgrade' => true,
            ];
        }

        $dailyLimit = $this->dailyLimit($monthlyLimit);
        $dailyUsed = $this->usageCount($user, $type, $this->dayStart());

        if ($dailyUsed >= $dailyLimit) {
            $params = [
                'used' => $dailyUsed,
                'limit' => $dailyLimit,
                'remaining' => max(0, $monthlyLimit - $monthlyUsed),
            ];

            return [
                'message' => $isVision
                    ? __("Bugungi rasm tahlili limitingiz tugadi (:used / :limit). Ertaga yana foydalanishingiz mumkin. Bu oy uchun yana :remaining ta tahlil qoldi. Ko'proq kerak bo'lsa, tarifingizni oshiring.", $params)
                    : __("Bugungi AI maslahat limitingiz tugadi (:used / :limit). Ertaga yana foydalanishingiz mumkin. Bu oy uchun yana :remaining ta so'rov qoldi. Ko'proq kerak bo'lsa, tarifingizni oshiring.", $params),
                'upgrade' => true,
            ];
        }

        if ($isVision && $animal) {
            $perAnimalLimit = (int) config('ai.vision_per_animal_daily', 3);
            $perAnimalUsed = AiUsageLog::where('animal_id', $animal->id)
                ->where('turi', AiUsageLog::TYPE_VISION)
                ->where('created_at', '>=', $this->dayStart())
                ->count();

            if ($perAnimalUsed >= $perAnimalLimit) {
                return [
                    'message' => __("Bitta hayvon uchun kuniga :limit tadan ortiq rasm tahlil qilib bo'lmaydi. Ertaga qayta urinib ko'ring.", ['limit' => $perAnimalLimit]),
                    'upgrade' => false,
                ];
            }
        }

        return null;
    }

    /**
     * Faol (arxivda bo'lmagan) hayvonlar soni tarifdagi chegaraga yetgan bo'lsa, xabar qaytaradi.
     */
    public function animalLimitMessage(User $user): ?string
    {
        $limit = $user->activePlan()->max_hayvon;

        if (Animal::where('user_id', $user->id)->count() < $limit) {
            return null;
        }

        return __("Tarifingiz bo'yicha ko'pi bilan :limit ta faol hayvon saqlash mumkin. Yangi hayvon qo'shish uchun tarifingizni oshiring yoki keraksiz hayvonni arxivga o'tkazing.", ['limit' => $limit]);
    }

    /**
     * Tariflar sahifasi uchun foydalanuvchining joriy holati.
     */
    public function summary(User $user): array
    {
        $plan = $user->activePlan();
        $result = [
            'plan' => $plan,
            'animals' => Animal::where('user_id', $user->id)->count(),
            'resets_at' => $this->nextMonthStartLocal(),
        ];

        foreach ([AiUsageLog::TYPE_CHAT, AiUsageLog::TYPE_VISION] as $type) {
            $monthly = $plan->monthlyLimit($type);
            $result[$type] = [
                'month_used' => $this->usageCount($user, $type, $this->monthStart()),
                'month_limit' => $monthly,
                'today_used' => $this->usageCount($user, $type, $this->dayStart()),
                'today_limit' => $this->dailyLimit($monthly),
            ];
        }

        return $result;
    }

    public function dailyLimit(int $monthlyLimit): int
    {
        $divisor = max(1, (int) config('ai.daily_limit_divisor', 10));

        return max((int) config('ai.daily_limit_min', 3), intdiv($monthlyLimit, $divisor));
    }

    public function usageCount(User $user, string $type, Carbon $since): int
    {
        return AiUsageLog::where('user_id', $user->id)
            ->where('turi', $type)
            ->where('created_at', '>=', $since)
            ->count();
    }

    public function platformMonthCost(): float
    {
        return (float) AiUsageLog::where('created_at', '>=', $this->monthStart())->sum('narx_usd');
    }

    public function isPlatformBudgetExceeded(): bool
    {
        return $this->platformMonthCost() >= (float) config('ai.platform_monthly_budget_usd', 30);
    }

    /**
     * Kun va oy chegaralari config('ai.timezone') bo'yicha, bazadagi qiymatlar bilan
     * solishtirish uchun UTC ko'rinishida qaytariladi.
     */
    public function dayStart(): Carbon
    {
        return now(config('ai.timezone'))->startOfDay()->utc();
    }

    public function monthStart(): Carbon
    {
        return now(config('ai.timezone'))->startOfMonth()->utc();
    }

    public function nextMonthStartLocal(): Carbon
    {
        return now(config('ai.timezone'))->startOfMonth()->addMonthNoOverflow();
    }

    /**
     * OpenAI javobidagi usage qiymatlari bo'yicha xarajat yozuvini qo'shadi.
     * Yozib bo'lmasa ham foydalanuvchi javobini yo'qotmasligi uchun xato faqat jurnalga yoziladi.
     */
    public function record(User $user, ?Animal $animal, string $type, array $response, string $requestedModel): ?AiUsageLog
    {
        $inputTokens = (int) data_get($response, 'usage.prompt_tokens', 0);
        $outputTokens = (int) data_get($response, 'usage.completion_tokens', 0);
        $model = (string) (data_get($response, 'model') ?: $requestedModel);

        try {
            return AiUsageLog::create([
                'user_id' => $user->id,
                'animal_id' => $animal?->id,
                'turi' => $type,
                'model' => mb_substr($model, 0, 100),
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'narx_usd' => $this->cost($model, $inputTokens, $outputTokens, $requestedModel),
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Narx USD da. Model nomi config('ai.pricing') dagi eng uzun mos kalit bilan boshlanishi kerak.
     */
    public function cost(string $model, int $inputTokens, int $outputTokens, ?string $fallbackModel = null): float
    {
        $price = $this->priceFor($model) ?? ($fallbackModel ? $this->priceFor($fallbackModel) : null);

        if ($price === null) {
            Log::warning('AI narxi topilmadi', ['model' => $model]);

            return 0.0;
        }

        $cost = ($inputTokens * (float) $price['input'] + $outputTokens * (float) $price['output']) / 1_000_000;

        return round($cost, 6);
    }

    private function priceFor(string $model): ?array
    {
        $match = null;

        foreach (config('ai.pricing', []) as $key => $price) {
            if (str_starts_with($model, $key) && ($match === null || strlen($key) > strlen($match))) {
                $match = $key;
            }
        }

        return $match !== null ? config('ai.pricing')[$match] : null;
    }
}
