<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    public const FREE = 'free';

    protected $fillable = [
        'nomi',
        'narx_som',
        'max_hayvon',
        'oylik_chat_limit',
        'oylik_vision_limit',
        'chat_model',
        'vision_model',
        'faol',
    ];

    protected $casts = [
        'narx_som' => 'integer',
        'max_hayvon' => 'integer',
        'oylik_chat_limit' => 'integer',
        'oylik_vision_limit' => 'integer',
        'faol' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Bepul tarif. Bazada topilmasa (masalan o'chirib yuborilgan bo'lsa), standart limitli
     * saqlanmagan nusxa qaytariladi — shunda limitlar hech qachon cheksiz bo'lib qolmaydi.
     */
    public static function free(): self
    {
        return once(fn () => static::where('kod', self::FREE)->first()
            ?? (new static())->forceFill([
                'kod' => self::FREE,
                'nomi' => 'Free',
                'narx_som' => null,
                'max_hayvon' => 10,
                'oylik_chat_limit' => 20,
                'oylik_vision_limit' => 3,
                'faol' => true,
            ]));
    }

    public function hasPrice(): bool
    {
        return $this->narx_som !== null;
    }

    public function monthlyLimit(string $type): int
    {
        return $type === AiUsageLog::TYPE_VISION ? $this->oylik_vision_limit : $this->oylik_chat_limit;
    }

    /**
     * Shu tarif uchun AI modeli. Tarifda tanlanmagan bo'lsa, config('ai.chat_model' / 'ai.vision_model').
     */
    public function aiModel(string $type): string
    {
        $isVision = $type === AiUsageLog::TYPE_VISION;
        $model = trim((string) ($isVision ? $this->vision_model : $this->chat_model));

        return $model !== '' ? $model : (string) config($isVision ? 'ai.vision_model' : 'ai.chat_model', 'gpt-4o-mini');
    }
}
