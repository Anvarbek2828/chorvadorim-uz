<?php



namespace App\Http\Controllers;



use App\Models\Animal;

use App\Models\Reminder;

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



        $animal->reminders()->create($validated);



        return back()->with('success', "Eslatma qo'shildi.");

    }



    public function destroy(Animal $animal, Reminder $reminder)

    {

        $this->checkOwnership($animal);



        if ((int) $reminder->animal_id !== (int) $animal->id) {

            abort(404);

        }



        $reminder->delete();



        return back()->with('success', "Eslatma o'chirildi.");

    }



    private function checkOwnership(Animal $animal)

    {

        $this->ensureAnimalInFarm($animal);

    }

}

