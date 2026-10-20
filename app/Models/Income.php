<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;



class Income extends Model

{

    use HasFactory;



    protected $fillable = [

        'animal_id',

        'type',

        'amount',

        'income_date',

        'note',

    ];



    protected $casts = [

        'income_date' => 'date',

        'amount' => 'decimal:2',

    ];



    public function animal()

    {

        return $this->belongsTo(Animal::class);

    }

}

