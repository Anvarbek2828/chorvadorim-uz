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

        ];

    }



    public function animals()

    {

        return $this->hasMany(Animal::class);

    }

}

