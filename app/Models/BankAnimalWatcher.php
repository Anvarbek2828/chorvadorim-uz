<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class BankAnimalWatcher extends Model

{

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REVOKED = 'revoked';



    protected $fillable = ['telegram_chat_id', 'animal_id', 'status', 'requester_name', 'responded_at'];



    protected $casts = [

        'responded_at' => 'datetime',

    ];



    public function animal()

    {

        return $this->belongsTo(Animal::class);

    }

}

