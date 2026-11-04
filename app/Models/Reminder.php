<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class Reminder extends Model

{

    protected $fillable = ['animal_id', 'title', 'due_date', 'sent_at'];



    protected $casts = [

        'due_date' => 'date',

        'sent_at' => 'datetime',

        'bajarilgan_at' => 'datetime',

    ];



    public function animal()

    {

        return $this->belongsTo(Animal::class);

    }

    public function isDone(): bool
    {
        return $this->bajarilgan_at !== null;
    }

    /**
     * "Bajarildi" deb belgilaydi. Shartli UPDATE: ikki marta bosilsa (web yoki bot) faqat birinchisi true qaytaradi.
     */
    public function markDone(): bool
    {
        $now = now();
        $updated = static::whereKey($this->id)->whereNull('bajarilgan_at')->update(['bajarilgan_at' => $now]) === 1;

        if ($updated) {
            $this->bajarilgan_at = $now;
            $this->syncOriginalAttribute('bajarilgan_at');
        }

        return $updated;
    }

}
