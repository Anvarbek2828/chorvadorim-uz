<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Obuna to'lovi yoki bonusi. Yozuv o'chirilmaydi: bekor qilinganda bekor_qilingan_at belgilanadi.
 */
class FarmPayment extends Model
{
    public const TYPE_PAYMENT = 'tolov';
    public const TYPE_BONUS = 'bonus';

    // Ro'yxatdagi tartib: bank, to'lov tizimlari, naqd, boshqa (bazadagi ENUM tartibi boshqacha — bu muhim emas)
    public const METHODS = ['bank', 'tolov_tizimi', 'naqd', 'boshqa'];

    protected $fillable = [
        'farm_id',
        'farm_nomi',
        'tur',
        'plan_id',
        'avvalgi_plan_id',
        'oylar',
        'summa',
        'usul',
        'hujjat_raqami',
        'izoh',
        'avvalgi_tugash',
        'yangi_tugash',
        'admin_id',
    ];

    protected $casts = [
        'oylar' => 'integer',
        'summa' => 'integer',
        'avvalgi_tugash' => 'date',
        'yangi_tugash' => 'date',
        'bekor_qilingan_at' => 'datetime',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function previousPlan()
    {
        return $this->belongsTo(Plan::class, 'avvalgi_plan_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'bekor_admin_id');
    }

    /**
     * To'lov usullari yorliqlari (METHODS tartibida), joriy tilda.
     */
    public static function methodLabels(): array
    {
        return [
            'bank' => __("Bank o'tkazmasi"),
            'tolov_tizimi' => __("To'lov tizimlari orqali"),
            'naqd' => __('Naqd'),
            'boshqa' => __('Boshqa'),
        ];
    }

    public function isCancelled(): bool
    {
        return $this->bekor_qilingan_at !== null;
    }
}
