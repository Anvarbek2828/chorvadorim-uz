<?php

namespace App\Http\Controllers;



use App\Models\Animal;

use App\Models\Income;

use Illuminate\Http\Request;



class IncomeController extends Controller

{

    public function store(Request $request, Animal $animal)

    {

        $this->checkOwnership($animal);



        $validated = $request->validate([

            'type' => 'required|string|max:255',

            'amount' => 'required|numeric|min:0',

            'income_date' => 'required|date',

            'note' => 'nullable|string',

        ]);



        $validated['animal_id'] = $animal->id;



        Income::create($validated);



        return redirect()->route('animals.show', $animal)->with('success', 'Daromad qo\'shildi!');

    }



    public function destroy(Animal $animal, Income $income)

    {

        $this->checkOwnership($animal);



        if ((int) $income->animal_id !== (int) $animal->id) {

            abort(404);

        }



        $income->delete();



        return redirect()->route('animals.show', $animal)->with('success', 'Daromad o\'chirildi.');

    }



    private function checkOwnership(Animal $animal)

    {

        $this->ensureAnimalInFarm($animal);

    }

}

