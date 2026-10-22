<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\Animal;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiUsageService
{
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
