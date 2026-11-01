<?php

namespace App\Support;

use App\Models\FarmUser;

/**
 * Ferma ichidagi rollar va huquqlar xaritasi. Config faylda emas — serverda config kesh bo'lishi mumkin.
 */
final class FarmPermissions
{
    public const ROLES = [
        FarmUser::ROLE_OWNER,
        FarmUser::ROLE_MANAGER,
        FarmUser::ROLE_WORKER,
        FarmUser::ROLE_VIEWER,
    ];

    // huquq => shu huquqqa ega rollar
    public const MAP = [
        'animals.view' => ['owner', 'manager', 'worker', 'viewer'],
        'animals.create' => ['owner', 'manager', 'worker'],
        'animals.update' => ['owner', 'manager', 'worker'],
        'animals.delete' => ['owner', 'manager'],
        'import.csv' => ['owner', 'manager'],
        'reminders.manage' => ['owner', 'manager', 'worker'],
        'reminders.complete' => ['owner', 'manager', 'worker'],
        'reminders.delete' => ['owner', 'manager'],
        'ai.use' => ['owner', 'manager', 'worker'],
        'bot.write' => ['owner', 'manager', 'worker'],
        'staff.manage' => ['owner'],
        'farm.settings' => ['owner'],
        'plan.view' => ['owner', 'manager'],
        'audit.view' => ['owner', 'manager'],
    ];

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::MAP);
    }

    public static function allows(?string $role, string $permission): bool
    {
        return $role !== null && in_array($role, self::MAP[$permission] ?? [], true);
    }
}
