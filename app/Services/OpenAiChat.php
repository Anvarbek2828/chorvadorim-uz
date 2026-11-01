<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiChat
{
    private const URL = 'https://api.openai.com/v1/chat/completions';

    private const MODEL_ERROR_CODES = ['model_not_found', 'unsupported_parameter', 'unsupported_value', 'invalid_model'];

    /**
     * Chat Completions so'rovi. Model turiga qarab parametrlar tanlanadi:
     * oddiy modellar (gpt-4o-mini) — temperature va max_tokens,
     * reasoning modellar (gpt-5.x, o-seriya) — max_completion_tokens va reasoning_effort, temperature'siz.
     * Muvaffaqiyatli bo'lsa javob JSON i qaytadi, aks holda xato jurnalga yozilib OpenAiException tashlanadi.
     */
    public function complete(string $model, array $messages, int $maxTokens, float $temperature, array $extra = [], int $timeout = 30): array
    {
        $payload = ['model' => $model, 'messages' => $messages] + $extra;

        if (!$this->isReasoningModel($model)) {
            $response = $this->send($payload + ['temperature' => $temperature, 'max_tokens' => $maxTokens], $timeout, $model);

            return $this->result($response, $model);
        }

        $effort = (string) config('ai.reasoning_effort', 'none');
        $response = $this->send($payload + $this->reasoningParams($effort, $maxTokens), $timeout, $model);

        // Model bu darajani qabul qilmasa (masalan "none"), bir marta "low" bilan qayta so'raladi
        if ($effort !== 'low' && $response->status() === 400 && $response->json('error.param') === 'reasoning_effort') {
            $response = $this->send($payload + $this->reasoningParams('low', $maxTokens), $timeout, $model);
        }

        return $this->result($response, $model);
    }

    public function isReasoningModel(string $model): bool
    {
        foreach ((array) config('ai.reasoning_model_prefixes', []) as $prefix) {
            if (str_starts_with($model, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function reasoningParams(string $effort, int $maxTokens): array
    {
        // max_completion_tokens o'ylash tokenlarini ham o'z ichiga oladi
        $extraTokens = $effort === 'none' ? 0 : (int) config('ai.reasoning_extra_tokens', 1000);

        return [
            'reasoning_effort' => $effort,
            'max_completion_tokens' => $maxTokens + $extraTokens,
        ];
    }

    private function send(array $payload, int $timeout, string $model): Response
    {
        try {
            return Http::withToken(config('services.openai.api_key'))
                ->timeout($timeout)
                ->post(self::URL, $payload);
        } catch (ConnectionException $e) {
            Log::error('OpenAI bilan ulanib bo\'lmadi', ['model' => $model, 'xato' => $e->getMessage()]);

            throw new OpenAiException($e->getMessage());
        }
    }

    private function result(Response $response, string $model): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $code = (string) $response->json('error.code');
        $param = (string) $response->json('error.param');

        Log::error('OpenAI xato qaytardi', [
            'model' => $model,
            'status' => $response->status(),
            'code' => $code,
            'param' => $param,
            'xabar' => mb_substr((string) $response->json('error.message'), 0, 500),
        ]);

        $modelError = in_array($response->status(), [403, 404], true)
            || in_array($code, self::MODEL_ERROR_CODES, true)
            || $param === 'model';

        throw new OpenAiException("OpenAI {$response->status()} {$code}", $modelError);
    }
}
