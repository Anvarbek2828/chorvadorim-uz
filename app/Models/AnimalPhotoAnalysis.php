<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;



class AnimalPhotoAnalysis extends Model

{

    use HasFactory;



    protected $fillable = [

        'animal_id',

        'photo_path',

        'bcs_score',

        'estimated_weight',

        'ai_summary',

        'ai_raw_response',

    ];



    protected $casts = [

        'bcs_score' => 'float',

        'ai_raw_response' => 'array',

    ];



    public function animal()

    {

        return $this->belongsTo(Animal::class);

    }



    public function chatMessages()

    {

        return $this->hasMany(ChatMessage::class, 'photo_analysis_id')->orderBy('created_at', 'asc');

    }

}

