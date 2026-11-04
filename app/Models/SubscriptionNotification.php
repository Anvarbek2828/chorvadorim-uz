<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Obuna haqidagi bot xabari: (ferma, foydalanuvchi, tur, tugash_sanasi) bo'yicha bir marta.
 */
class SubscriptionNotification extends Model
{
    public const TYPE_BEFORE = 'oldin_3kun';
    public const TYPE_EXPIRED = 'tugadi';
    public const TYPE_READ_ONLY = 'faqat_oqish';

    public const STATUS_SENT = 'yuborildi';
    public const STATUS_NO_TELEGRAM = 'telegram_yoq';
    public const STATUS_FAILED = 'xato';

    // Xato bo'lsa keyingi kunlik ishda qayta urinadi, jami shuncha martagacha
    public const MAX_ATTEMPTS = 3;

    // tugash_sanasi ataylab cast qilinmagan: 'Y-m-d' satr sifatida saqlanadi va unique kalit bo'yicha qidiriladi
    protected $fillable = [
        'farm_id',
        'user_id',
        'tur',
        'tugash_sanasi',
        'holat',
        'urinishlar',
        'xato',
    ];

    protected $casts = [
        'urinishlar' => 'integer',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
