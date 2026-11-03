<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ferma ichidagi amallar jurnali (xodimlar nima qilgani). Super admin jurnali — AdminAuditLog.
 */
class FarmAuditLog extends Model
{
    public const ACTION_ANIMAL_CREATED = 'animal.created';
    public const ACTION_ANIMAL_UPDATED = 'animal.updated';
    public const ACTION_ANIMAL_DELETED = 'animal.deleted';
    public const ACTION_ANIMAL_RESTORED = 'animal.restored';
    public const ACTION_ANIMAL_FORCE_DELETED = 'animal.force_deleted';
    public const ACTION_ANIMALS_IMPORTED = 'animals.imported';
    public const ACTION_REMINDER_CREATED = 'reminder.created';
    public const ACTION_REMINDER_DELETED = 'reminder.deleted';
    public const ACTION_EXPENSE_CREATED = 'expense.created';
    public const ACTION_EXPENSE_DELETED = 'expense.deleted';
    public const ACTION_INCOME_CREATED = 'income.created';
    public const ACTION_INCOME_DELETED = 'income.deleted';
    public const ACTION_STAFF_ADDED = 'staff.added';
    public const ACTION_STAFF_ROLE_CHANGED = 'staff.role_changed';
    public const ACTION_STAFF_STATUS_CHANGED = 'staff.status_changed';
    public const ACTION_STAFF_AUTO_SUSPENDED = 'staff.auto_suspended';
    public const ACTION_STAFF_REMOVED = 'staff.removed';
    public const ACTION_STAFF_PASSWORD_RESET = 'staff.password_reset';
    public const ACTION_FARM_RENAMED = 'farm.renamed';

    public const ACTIONS = [
        self::ACTION_ANIMAL_CREATED,
        self::ACTION_ANIMAL_UPDATED,
        self::ACTION_ANIMAL_DELETED,
        self::ACTION_ANIMAL_RESTORED,
        self::ACTION_ANIMAL_FORCE_DELETED,
        self::ACTION_ANIMALS_IMPORTED,
        self::ACTION_REMINDER_CREATED,
        self::ACTION_REMINDER_DELETED,
        self::ACTION_EXPENSE_CREATED,
        self::ACTION_EXPENSE_DELETED,
        self::ACTION_INCOME_CREATED,
        self::ACTION_INCOME_DELETED,
        self::ACTION_STAFF_ADDED,
        self::ACTION_STAFF_ROLE_CHANGED,
        self::ACTION_STAFF_STATUS_CHANGED,
        self::ACTION_STAFF_AUTO_SUSPENDED,
        self::ACTION_STAFF_REMOVED,
        self::ACTION_STAFF_PASSWORD_RESET,
        self::ACTION_FARM_RENAMED,
    ];

    // Jadvalda faqat created_at bor
    public const UPDATED_AT = null;

    protected $fillable = [
        'farm_id',
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
