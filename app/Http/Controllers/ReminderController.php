<?php



namespace App\Http\Controllers;



use App\Models\Animal;

use App\Models\Reminder;

use App\Models\FarmAuditLog;

use App\Services\FarmAuditService;

use Illuminate\Http\Request;



class ReminderController extends Controller

{

    public function store(Request $request, Animal $animal)

    {

        $this->checkOwnership($animal);



        $validated = $request->validate([

            'title' => 'required|string|max:255',

            'due_date' => 'required|date',

        ]);



        $reminder = $animal->reminders()->create($validated);

        app(FarmAuditService::class)->log($animal->farm_id, FarmAuditLog::ACTION_REMINDER_CREATED, 'reminder', $reminder->id, [], $this->auditState($animal, $reminder));



        return back()->with('success', "Eslatma qo'shildi.");

    }



    public function destroy(Animal $animal, Reminder $reminder)

    {

        $this->checkOwnership($animal);



        if ((int) $reminder->animal_id !== (int) $animal->id) {

            abort(404);

        }



        $reminder->delete();

        app(FarmAuditService::class)->log($animal->farm_id, FarmAuditLog::ACTION_REMINDER_DELETED, 'reminder', $reminder->id, $this->auditState($animal, $reminder));



        return back()->with('success', "Eslatma o'chirildi.");

    }



    private function auditState(Animal $animal, Reminder $reminder): array

    {

        return [

            'hayvon' => $animal->name,

            'eslatma' => $reminder->title,

            'sana' => $reminder->due_date,

        ];

    }



    private function checkOwnership(Animal $animal)

    {

        $this->ensureAnimalInFarm($animal);

    }

}

