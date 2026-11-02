<?php

namespace App\Http\Controllers;



use App\Models\Animal;

use App\Models\Income;

use App\Models\FarmAuditLog;

use App\Services\FarmAuditService;

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



        $income = Income::create($validated);

        app(FarmAuditService::class)->log($animal->farm_id, FarmAuditLog::ACTION_INCOME_CREATED, 'income', $income->id, [], $this->auditState($animal, $income));



        return redirect()->route('animals.show', $animal)->with('success', 'Daromad qo\'shildi!');

    }



    public function destroy(Animal $animal, Income $income)

    {

        $this->checkOwnership($animal);



        if ((int) $income->animal_id !== (int) $animal->id) {

            abort(404);

        }



        $income->delete();

        app(FarmAuditService::class)->log($animal->farm_id, FarmAuditLog::ACTION_INCOME_DELETED, 'income', $income->id, $this->auditState($animal, $income));



        return redirect()->route('animals.show', $animal)->with('success', 'Daromad o\'chirildi.');

    }



    private function auditState(Animal $animal, Income $income): array

    {

        return [

            'hayvon' => $animal->name,

            'turi' => $income->type,

            'summa' => $income->amount,

            'sana' => $income->income_date,

        ];

    }



    private function checkOwnership(Animal $animal)

    {

        $this->ensureAnimalInFarm($animal);

    }

}

