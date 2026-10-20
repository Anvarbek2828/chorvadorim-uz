<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class BankAnimalWatcher extends Model

{

    protected $fillable = ['telegram_chat_id', 'animal_id'];



    public function animal()

    {

        return $this->belongsTo(Animal::class);

    }

}

