<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    public const TYPE_CHAT = 'chat';
    public const TYPE_VISION = 'vision';

    // Jadvalda faqat created_at bor
    public const UPDATED_AT = null;

    protected $fillable = [
        'farm_id',
        'user_id',
        'animal_id',
        'turi',
        'model',
        'input_tokens',
        'output_tokens',
        'narx_usd',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'narx_usd' => 'decimal:6',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class)->withTrashed();
    }
}
