<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    public const ACTION_USER_PLAN_CHANGED = 'user.plan_changed';
    public const ACTION_USER_BLOCKED = 'user.blocked';
    public const ACTION_USER_UNBLOCKED = 'user.unblocked';
    public const ACTION_USER_MADE_ADMIN = 'user.made_admin';
    public const ACTION_PLAN_UPDATED = 'plan.updated';
    public const ACTION_USER_ANIMALS_IMPORTED = 'user.animals_imported';
    public const ACTION_SUBSCRIPTION_EXTENDED = 'subscription.extended';
    public const ACTION_SUBSCRIPTION_BONUS = 'subscription.bonus';
    public const ACTION_PAYMENT_CANCELLED = 'subscription.payment_cancelled';

    // Jadvalda faqat created_at bor
    public const UPDATED_AT = null;

    protected $fillable = [
        'farm_id',
        'admin_id',
        'amal',
        'obyekt_turi',
        'obyekt_id',
        'eski_qiymat',
        'yangi_qiymat',
        'ip',
    ];

    protected $casts = [
        'eski_qiymat' => 'array',
        'yangi_qiymat' => 'array',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
