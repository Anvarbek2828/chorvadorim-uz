<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class Reminder extends Model

{

    protected $fillable = ['animal_id', 'title', 'due_date', 'sent_at'];



    protected $casts = [

        'due_date' => 'date',

        'sent_at' => 'datetime',

    ];



    public function animal()

    {

        return $this->belongsTo(Animal::class);

    }

}

