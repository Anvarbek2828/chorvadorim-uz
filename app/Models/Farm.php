<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ferma — ma'lumotlar egasi. Tarif ham fermaga tegishli (plan_id, plan_tugash_sanasi).
 */
class Farm extends Model
{
    // plan_id va plan_tugash_sanasi ataylab $fillable ga qo'shilmagan: ularni faqat admin o'zgartiradi
    protected $fillable = [
        'name',
        'owner_id',
    ];

    protected $casts = [
        'plan_tugash_sanasi' => 'date',
    ];

    /**
     * Yangi foydalanuvchi uchun ferma va egalik a'zoligini yaratadi (tranzaksiya ichida).
     * Tarif foydalanuvchidan nusxa olinadi.
     */
    public static function createForOwner(User $user): self
    {
        return DB::transaction(function () use ($user) {
            $farm = new static(['name' => $user->name, 'owner_id' => $user->id]);
            $farm->plan_id = $user->plan_id ?? Plan::free()->id;
            $farm->plan_tugash_sanasi = $user->plan_tugash_sanasi;
            $farm->save();

            $farm->members()->attach($user->id, [
                'role' => FarmUser::ROLE_OWNER,
                'status' => FarmUser::STATUS_ACTIVE,
            ]);

            $user->current_farm_id = $farm->id;
            $user->save();

            return $farm;
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class)
            ->using(FarmUser::class)
            ->withPivot(['id', 'role', 'status', 'invited_by', 'must_change_password'])
            ->withTimestamps();
    }

    public function memberships()
    {
        return $this->hasMany(FarmUser::class);
    }

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    public function aiUsageLogs()
    {
        return $this->hasMany(AiUsageLog::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments()
    {
        return $this->hasMany(FarmPayment::class);
    }

    /**
     * Amaldagi tarif: tarif belgilanmagan bo'lsa — bepul tarif. Muddati o'tgan pullik tarifning limitlari
     * saqlanadi: imtiyoz kunlarida hammasi ishlaydi, keyin yozishni faqat o'qish rejimi yopadi (SubscriptionService).
     */
    public function activePlan(): Plan
    {
        return $this->plan ?? Plan::free();
    }

    public function isPlanExpired(): bool
    {
        return $this->plan_tugash_sanasi !== null
            && $this->plan_tugash_sanasi->toDateString() < now(config('ai.timezone'))->toDateString();
    }

    /**
     * Tarifdagi xodimlar chegarasi (egasi kirmaydi). null — cheksiz.
     */
    public function staffLimit(): ?int
    {
        return $this->activePlan()->max_staff;
    }

    /**
     * Limitga kiradigan xodimlar: egasidan boshqa faol a'zolar.
     */
    public function activeStaffCount(): int
    {
        return $this->memberships()
            ->where('role', '!=', FarmUser::ROLE_OWNER)
            ->where('status', FarmUser::STATUS_ACTIVE)
            ->count();
    }

    public function hasStaffCapacity(): bool
    {
        $limit = $this->staffLimit();

        return $limit === null || $this->activeStaffCount() < $limit;
    }

    /**
     * Limitdan ortiq faol xodimlarni to'xtatadi: eng oxirgi qo'shilganlar birinchi. $limit null — cheksiz.
     *
     * @return Collection<int, FarmUser> to'xtatilgan a'zoliklar
     */
    public function suspendExcessStaff(?int $limit): Collection
    {
        if ($limit === null) {
            return collect();
        }

        $active = $this->memberships()
            ->with('user')
            ->where('role', '!=', FarmUser::ROLE_OWNER)
            ->where('status', FarmUser::STATUS_ACTIVE)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $excess = $active->take(max(0, $active->count() - $limit));

        foreach ($excess as $membership) {
            $membership->status = FarmUser::STATUS_SUSPENDED;
            $membership->save();
        }

        return $excess;
    }
}
