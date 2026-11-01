<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    /**
     * Amaldagi tarif: tarif belgilanmagan yoki muddati o'tgan bo'lsa — bepul tarif.
     * Tugash sanasi kuni oxirigacha amal qiladi.
     */
    public function activePlan(): Plan
    {
        if (!$this->plan || $this->isPlanExpired()) {
            return Plan::free();
        }

        return $this->plan;
    }

    public function isPlanExpired(): bool
    {
        return $this->plan_tugash_sanasi !== null
            && $this->plan_tugash_sanasi->toDateString() < now(config('ai.timezone'))->toDateString();
    }
}
