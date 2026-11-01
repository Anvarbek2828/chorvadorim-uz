<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\FarmUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Foydalanuvchining joriy fermasi: users.current_farm_id dagi faol a'zolik, bo'lmasa birinchi faol a'zolik.
 * status='suspended' a'zoliklar hisobga olinmaydi. Natija so'rov davomida eslab qolinadi.
 */
class CurrentFarm
{
    /** @var array<int, FarmUser|null> */
    private array $memberships = [];

    public function membership(User $user): ?FarmUser
    {
        if (!array_key_exists($user->id, $this->memberships)) {
            $this->memberships[$user->id] = $this->resolve($user);
        }

        return $this->memberships[$user->id];
    }

    public function farm(User $user): ?Farm
    {
        return $this->membership($user)?->farm;
    }

    public function role(User $user): ?string
    {
        return $this->membership($user)?->role;
    }

    public function forget(User $user): void
    {
        unset($this->memberships[$user->id]);
    }

    private function resolve(User $user): ?FarmUser
    {
        $active = FarmUser::with('farm')
            ->where('user_id', $user->id)
            ->where('status', FarmUser::STATUS_ACTIVE);

        $membership = $user->current_farm_id
            ? (clone $active)->where('farm_id', $user->current_farm_id)->first()
            : null;

        $membership ??= (clone $active)->orderBy('id')->first();

        // current_farm_id eskirgan bo'lsa (a'zolik to'xtatilgan yoki o'chirilgan) — topilgan fermaga to'g'rilanadi
        if ($membership && (int) $user->current_farm_id !== (int) $membership->farm_id) {
            DB::table('users')->where('id', $user->id)->update(['current_farm_id' => $membership->farm_id]);
            $user->current_farm_id = $membership->farm_id;
            $user->syncOriginalAttribute('current_farm_id');
        }

        return $membership;
    }
}
