<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;



class Animal extends Model

{

    use HasFactory, SoftDeletes;



    protected $fillable = [

        'user_id',

        'name',

        'direction',

        'breed',

        'birth_date',

        'initial_weight',

        'current_weight',

        'gender',

        'notes',

        'has_passport',

        'passport_number',

        'chip_number',

        'passport_issue_date',

    ];



    protected $casts = [

        'birth_date' => 'date',

        'passport_issue_date' => 'date',

        'has_passport' => 'boolean',

    ];



    public function user()

    {

        return $this->belongsTo(User::class);

    }



    public function expenses()

    {

        return $this->hasMany(Expense::class);

    }



    public function incomes()

    {

        return $this->hasMany(Income::class);

    }



    public function chatMessages()

    {

        return $this->hasMany(ChatMessage::class)->orderBy('created_at', 'asc');

    }



    public function photoAnalyses()

    {

        return $this->hasMany(AnimalPhotoAnalysis::class)->orderBy('created_at', 'asc');

    }



    public function reminders()

    {

        return $this->hasMany(Reminder::class)->orderBy('due_date', 'asc');

    }



    public function bankWatchers()

    {

        return $this->hasMany(BankAnimalWatcher::class);

    }



    public function totalExpenses()

    {

        return $this->expenses()->sum('amount');

    }



    public function totalIncomes()

    {

        return $this->incomes()->sum('amount');

    }



    public function profit()

    {

        return $this->totalIncomes() - $this->totalExpenses();

    }

}

