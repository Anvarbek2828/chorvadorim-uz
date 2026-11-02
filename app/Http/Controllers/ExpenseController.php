<?php

namespace App\Http\Controllers;



use App\Models\Animal;

use App\Models\Expense;

use App\Models\FarmAuditLog;

use App\Services\FarmAuditService;

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



        $expense = Expense::create($validated);

        app(FarmAuditService::class)->log($animal->farm_id, FarmAuditLog::ACTION_EXPENSE_CREATED, 'expense', $expense->id, [], $this->auditState($animal, $expense));



        return redirect()->route('animals.show', $animal)->with('success', 'Xarajat qo\'shildi!');

    }



    public function destroy(Animal $animal, Expense $expense)

    {

        $this->checkOwnership($animal);



        if ((int) $expense->animal_id !== (int) $animal->id) {

            abort(404);

        }



        $expense->delete();

        app(FarmAuditService::class)->log($animal->farm_id, FarmAuditLog::ACTION_EXPENSE_DELETED, 'expense', $expense->id, $this->auditState($animal, $expense));



        return redirect()->route('animals.show', $animal)->with('success', 'Xarajat o\'chirildi.');

    }



    private function auditState(Animal $animal, Expense $expense): array

    {

        return [

            'hayvon' => $animal->name,

            'turi' => $expense->type,

            'summa' => $expense->amount,

            'sana' => $expense->expense_date,

        ];

    }



    private function checkOwnership(Animal $animal)

    {

        $this->ensureAnimalInFarm($animal);

    }

}

