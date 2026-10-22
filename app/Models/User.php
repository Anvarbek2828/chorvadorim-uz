<?php



namespace App\Models;



use Database\Factories\UserFactory;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Notifications\Notifiable;



class User extends Authenticatable

{

    use HasFactory, Notifiable;



    protected $fillable = [

        'name',

        'email',

        'password',

        'region',

        'telegram_chat_id',

    ];



    protected $hidden = [

        'password',

        'remember_token',

    ];



    protected function casts(): array

    {

        return [

            'email_verified_at' => 'datetime',

            'password' => 'hashed',
            'plan_tugash_sanasi' => 'date',
            'is_admin' => 'boolean',
            'is_blocked' => 'boolean',
            'last_active_at' => 'datetime',

        ];

    }

    // plan_id, is_admin va is_blocked ataylab $fillable ga qo'shilmagan: ularni faqat admin o'zgartiradi
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if ($user->plan_id === null) {
                $user->plan_id = Plan::free()->id;
            }
        });
    }



    public function animals()

    {

        return $this->hasMany(Animal::class);

    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function aiUsageLogs()
    {
        return $this->hasMany(AiUsageLog::class);
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

