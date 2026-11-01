<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Ferma a'zoligi: foydalanuvchining fermadagi roli va holati.
 */
class FarmUser extends Pivot
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_WORKER = 'worker';
    public const ROLE_VIEWER = 'viewer';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $table = 'farm_user';

    public $incrementing = true;

    protected $fillable = [
        'farm_id',
        'user_id',
        'role',
        'status',
        'invited_by',
        'must_change_password',
    ];

    protected $casts = [
        'must_change_password' => 'boolean',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
