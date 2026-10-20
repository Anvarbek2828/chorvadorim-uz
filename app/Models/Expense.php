<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;



class Expense extends Model

{

    use HasFactory;



    protected $fillable = [

        'animal_id',

        'type',

        'amount',

        'expense_date',

        'note',

    ];



    protected $casts = [

        'expense_date' => 'date',

        'amount' => 'decimal:2',

    ];



    public function animal()

    {

        return $this->belongsTo(Animal::class);

    }

}

