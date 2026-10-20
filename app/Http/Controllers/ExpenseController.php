<?php

namespace App\Http\Controllers;



use App\Models\Animal;

use App\Models\Expense;

use Illuminate\Http\Request;



class ExpenseController extends Controller

{

    public function store(Request $request, Animal $animal)

    {

        $this->checkOwnership($animal);



        $validated = $request->validate([

            'type' => 'required|string|max:255',

            'amount' => 'required|numeric|min:0',

            'expense_date' => 'required|date',

            'note' => 'nullable|string',

        ]);



        $validated['animal_id'] = $animal->id;



        Expense::create($validated);



        return redirect()->route('animals.show', $animal)->with('success', 'Xarajat qo\'shildi!');

    }



    public function destroy(Animal $animal, Expense $expense)

    {

        $this->checkOwnership($animal);



        if ((int) $expense->animal_id !== (int) $animal->id) {

            abort(403);

        }



        $expense->delete();



        return redirect()->route('animals.show', $animal)->with('success', 'Xarajat o\'chirildi.');

    }



    private function checkOwnership(Animal $animal)

    {

        $animalUserId = (int) $animal->user_id;

        $currentUserId = (int) auth()->id();



        if ($animalUserId !== $currentUserId) {

            abort(403, 'Sizga bu ma\'lumotni ko\'rish huquqi berilmagan.');

        }

    }

}

