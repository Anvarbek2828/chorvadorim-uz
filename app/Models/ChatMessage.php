<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;



class ChatMessage extends Model

{

    use HasFactory;



    protected $fillable = [

        'animal_id',

        'photo_analysis_id',

        'question',

        'answer',

    ];



    public function animal()

    {

        return $this->belongsTo(Animal::class);

    }



    public function photoAnalysis()

    {

        return $this->belongsTo(AnimalPhotoAnalysis::class, 'photo_analysis_id');

    }

}

