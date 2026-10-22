<?php

use App\Http\Controllers\ProfileController;

use App\Http\Controllers\AnimalController;

use App\Http\Controllers\ExpenseController;

use App\Http\Controllers\IncomeController;

use App\Http\Controllers\ChatController;

use Illuminate\Support\Facades\Route;



Route::get('/', function () {

    return auth()->check() ? redirect('/dashboard') : view('landing');

});



Route::get('/dashboard', function () {

    return view('dashboard');

})->middleware(['auth', 'verified'])->name('dashboard');



Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');



    Route::get('/animals', [AnimalController::class, 'index'])->name('animals.index');

    Route::get('/animals/create', [AnimalController::class, 'create'])->name('animals.create');

    Route::post('/animals', [AnimalController::class, 'store'])->name('animals.store');

    Route::get('/animals/archive', [AnimalController::class, 'archive'])->name('animals.archive');

    Route::post('/animals/{id}/restore', [AnimalController::class, 'restore'])->name('animals.restore');

    Route::delete('/animals/{id}/force-delete', [AnimalController::class, 'forceDelete'])->name('animals.forceDelete');

    Route::get('/animals/{animal}', [AnimalController::class, 'show'])->name('animals.show');

    Route::get('/animals/{animal}/edit', [AnimalController::class, 'edit'])->name('animals.edit');

    Route::put('/animals/{animal}', [AnimalController::class, 'update'])->name('animals.update');

    Route::delete('/animals/{animal}', [AnimalController::class, 'destroy'])->name('animals.destroy');



    Route::post('/animals/{animal}/expenses', [ExpenseController::class, 'store'])->name('expenses.store');

    Route::delete('/animals/{animal}/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');



    Route::post('/animals/{animal}/incomes', [IncomeController::class, 'store'])->name('incomes.store');

    Route::delete('/animals/{animal}/incomes/{income}', [IncomeController::class, 'destroy'])->name('incomes.destroy');



    Route::get('/animals/{animal}/chat', [ChatController::class, 'show'])->name('chat.show');

    Route::post('/animals/{animal}/chat', [ChatController::class, 'ask'])->middleware('throttle:20,1')->name('chat.ask');
    Route::get('/animals/{animal}/photo-analysis', [App\Http\Controllers\PhotoAnalysisController::class, 'index'])->name('photo-analysis.index');
    Route::post('/animals/{animal}/photo-analysis', [App\Http\Controllers\PhotoAnalysisController::class, 'store'])->middleware('throttle:10,1')->name('photo-analysis.store');
    Route::post('/animals/{animal}/photo-analysis/{analysis}/ask', [App\Http\Controllers\PhotoAnalysisController::class, 'askQuestion'])->middleware('throttle:20,1')->name('photo-analysis.ask');
    Route::delete('/animals/{animal}/photo-analysis/{analysis}', [App\Http\Controllers\PhotoAnalysisController::class, 'destroy'])->name('photo-analysis.destroy');

    Route::get('/tariflar', [App\Http\Controllers\PlanController::class, 'index'])->name('plans.index');

});

// Super admin panel: 'admin' middleware admin bo'lmaganlarga (mehmonlarga ham) 404 qaytaradi
Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/plan', [App\Http\Controllers\Admin\UserController::class, 'updatePlan'])->name('users.plan');
    Route::patch('/users/{user}/block', [App\Http\Controllers\Admin\UserController::class, 'toggleBlock'])->name('users.block');
    Route::get('/ai-costs', [App\Http\Controllers\Admin\AiCostController::class, 'index'])->name('ai-costs');
    Route::get('/plans', [App\Http\Controllers\Admin\PlanController::class, 'index'])->name('plans.index');
    Route::patch('/plans/{plan}', [App\Http\Controllers\Admin\PlanController::class, 'update'])->name('plans.update');
    Route::get('/audit-log', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-log');
});



require __DIR__.'/auth.php';




Route::get('/lang/{locale}', function (string $locale) {

    if (in_array($locale, ['uz', 'ru', 'uz_cyrl'])) {

        session(['locale' => $locale]);

    }

    return back();

})->name('lang.switch');




Route::post('/telegram/webhook', [App\Http\Controllers\TelegramController::class, 'webhook'])->name('telegram.webhook');



Route::middleware(['auth'])->group(function () {

    Route::post('/telegram/generate-code', [App\Http\Controllers\TelegramController::class, 'generateLinkCode'])->name('telegram.generate-code');

});




Route::middleware(['auth'])->group(function () {

    Route::post('/animals/{animal}/reminders', [App\Http\Controllers\ReminderController::class, 'store'])->name('reminders.store');

    Route::delete('/animals/{animal}/reminders/{reminder}', [App\Http\Controllers\ReminderController::class, 'destroy'])->name('reminders.destroy');

});

