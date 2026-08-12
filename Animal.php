<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Animal
 *
 * Chorva molini ifodalovchi asosiy model.
 * Har bir hayvon uchun to'liq raqamli karta, sog'liq tarixi
 * va moliyaviy ma'lumotlar shu model orqali bog'lanadi.
 */
class Animal extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'tag_number',
        'name',
        'species',          // sigir, qo'y, echki va h.k.
        'breed',
        'birth_date',
        'age_months',
        'gender',
        'weight_kg',
        'health_status',
        'last_vaccination_date',
        'next_vaccination_due',
        'photo_path',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'last_vaccination_date' => 'date',
        'next_vaccination_due' => 'date',
        'weight_kg' => 'decimal:1',
    ];

    /**
     * Hayvon egasi bo'lgan fermer.
     */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    /**
     * Hayvonga tegishli barcha vaksinatsiya yozuvlari.
     */
    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class);
    }

    /**
     * Hayvonga tegishli moliyaviy tranzaksiyalar
     * (xarid, oziq-ovqat xarajati, sotuv va h.k.)
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(AnimalTransaction::class);
    }

    /**
     * Vaksinatsiya muddati yaqinlashgan hayvonlarni topish uchun scope.
     * Eslatuv tizimi shu scope orqali ishlaydi.
     */
    public function scopeVaccinationDueSoon($query, int $days = 7)
    {
        return $query->whereNotNull('next_vaccination_due')
            ->whereBetween('next_vaccination_due', [now(), now()->addDays($days)]);
    }

    /**
     * Hayvonning joriy yoshini oy hisobida qaytaradi.
     */
    public function getCurrentAgeMonthsAttribute(): int
    {
        return $this->birth_date ? $this->birth_date->diffInMonths(now()) : $this->age_months;
    }
}
