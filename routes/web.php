<?php

use App\Http\Controllers\ProfileController;

use App\Http\Controllers\AnimalController;

use App\Http\Controllers\AnimalImportController;

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

});



// Ferma ma'lumotlari: joriy ferma ('farm') va rol huquqi (App\Support\FarmPermissions) tekshiriladi
Route::middleware(['auth', 'farm'])->group(function () {

    Route::get('/animals', [AnimalController::class, 'index'])->can('animals.view')->name('animals.index');

    Route::get('/animals/create', [AnimalController::class, 'create'])->can('animals.create')->name('animals.create');

    Route::post('/animals', [AnimalController::class, 'store'])->can('animals.create')->name('animals.store');

    // Fayldan ommaviy yuklash; /animals/{animal} dan oldin turishi kerak
    Route::get('/animals/import', [AnimalImportController::class, 'create'])->can('import.csv')->name('animals.import');
    Route::get('/animals/import/template', [AnimalImportController::class, 'template'])->can('import.csv')->name('animals.import.template');
    Route::post('/animals/import', [AnimalImportController::class, 'upload'])->can('import.csv')->middleware('throttle:10,1')->name('animals.import.upload');
    Route::get('/animals/import/{token}', [AnimalImportController::class, 'preview'])->can('import.csv')->whereAlphaNumeric('token')->name('animals.import.preview');
    Route::post('/animals/import/{token}/confirm', [AnimalImportController::class, 'confirm'])->can('import.csv')->whereAlphaNumeric('token')->name('animals.import.confirm');
    Route::get('/animals/import/{token}/errors', [AnimalImportController::class, 'errors'])->can('import.csv')->whereAlphaNumeric('token')->name('animals.import.errors');
    Route::delete('/animals/import/{token}', [AnimalImportController::class, 'cancel'])->can('import.csv')->whereAlphaNumeric('token')->name('animals.import.cancel');
    Route::get('/animals/archive', [AnimalController::class, 'archive'])->can('animals.view')->name('animals.archive');

    Route::post('/animals/{id}/restore', [AnimalController::class, 'restore'])->can('animals.delete')->name('animals.restore');

    Route::delete('/animals/{id}/force-delete', [AnimalController::class, 'forceDelete'])->can('animals.delete')->name('animals.forceDelete');

    Route::get('/animals/{animal}', [AnimalController::class, 'show'])->can('animals.view')->name('animals.show');

    Route::get('/animals/{animal}/edit', [AnimalController::class, 'edit'])->can('animals.update')->name('animals.edit');

    Route::put('/animals/{animal}', [AnimalController::class, 'update'])->can('animals.update')->name('animals.update');

    Route::delete('/animals/{animal}', [AnimalController::class, 'destroy'])->can('animals.delete')->name('animals.destroy');



    Route::post('/animals/{animal}/expenses', [ExpenseController::class, 'store'])->can('animals.update')->name('expenses.store');

    Route::delete('/animals/{animal}/expenses/{expense}', [ExpenseController::class, 'destroy'])->can('animals.delete')->name('expenses.destroy');



    Route::post('/animals/{animal}/incomes', [IncomeController::class, 'store'])->can('animals.update')->name('incomes.store');

    Route::delete('/animals/{animal}/incomes/{income}', [IncomeController::class, 'destroy'])->can('animals.delete')->name('incomes.destroy');



    Route::get('/animals/{animal}/chat', [ChatController::class, 'show'])->can('animals.view')->name('chat.show');

    Route::post('/animals/{animal}/chat', [ChatController::class, 'ask'])->can('ai.use')->middleware('throttle:20,1')->name('chat.ask');
    Route::get('/animals/{animal}/photo-analysis', [App\Http\Controllers\PhotoAnalysisController::class, 'index'])->can('animals.view')->name('photo-analysis.index');
    Route::post('/animals/{animal}/photo-analysis', [App\Http\Controllers\PhotoAnalysisController::class, 'store'])->can('ai.use')->middleware('throttle:10,1')->name('photo-analysis.store');
    Route::post('/animals/{animal}/photo-analysis/{analysis}/ask', [App\Http\Controllers\PhotoAnalysisController::class, 'askQuestion'])->can('ai.use')->middleware('throttle:20,1')->name('photo-analysis.ask');
    Route::delete('/animals/{animal}/photo-analysis/{analysis}', [App\Http\Controllers\PhotoAnalysisController::class, 'destroy'])->can('animals.delete')->name('photo-analysis.destroy');

    Route::get('/tariflar', [App\Http\Controllers\PlanController::class, 'index'])->can('plan.view')->name('plans.index');

    // Xodimlar: {member} — farm_user.id (boshqa fermaniki 404)
    Route::get('/xodimlar', [App\Http\Controllers\StaffController::class, 'index'])->can('staff.manage')->name('staff.index');
    Route::post('/xodimlar', [App\Http\Controllers\StaffController::class, 'store'])->can('staff.manage')->middleware('throttle:20,1')->name('staff.store');
    Route::patch('/xodimlar/{member}/rol', [App\Http\Controllers\StaffController::class, 'updateRole'])->can('staff.manage')->whereNumber('member')->name('staff.role');
    Route::patch('/xodimlar/{member}/holat', [App\Http\Controllers\StaffController::class, 'updateStatus'])->can('staff.manage')->whereNumber('member')->name('staff.status');
    Route::post('/xodimlar/{member}/parol', [App\Http\Controllers\StaffController::class, 'resetPassword'])->can('staff.manage')->middleware('throttle:20,1')->whereNumber('member')->name('staff.password');
    Route::delete('/xodimlar/{member}', [App\Http\Controllers\StaffController::class, 'destroy'])->can('staff.manage')->whereNumber('member')->name('staff.destroy');

    Route::get('/ferma/tolovlar', [App\Http\Controllers\FarmPaymentController::class, 'index'])->can('payments.view')->name('farm-payments.index');

    Route::get('/ferma-jurnali', [App\Http\Controllers\FarmAuditController::class, 'index'])->can('audit.view')->name('farm-audit.index');

    Route::get('/ferma/sozlamalar', [App\Http\Controllers\FarmController::class, 'edit'])->can('farm.settings')->name('farm-settings.edit');
    Route::patch('/ferma/sozlamalar', [App\Http\Controllers\FarmController::class, 'update'])->can('farm.settings')->name('farm-settings.update');

});

Route::middleware('auth')->group(function () {
    // Joriy fermani almashtirish (faqat o'z faol a'zoligiga)
    Route::post('/ferma/almashtirish', [App\Http\Controllers\FarmController::class, 'switch'])->name('farm.switch');

    // Egasi bergan vaqtinchalik parolni almashtirish (EnsurePasswordChanged)
    Route::get('/parol-almashtirish', [App\Http\Controllers\Auth\ForcePasswordController::class, 'edit'])->name('password.force');
    Route::put('/parol-almashtirish', [App\Http\Controllers\Auth\ForcePasswordController::class, 'update'])->name('password.force.update');
});

// Super admin panel: 'admin' middleware admin bo'lmaganlarga (mehmonlarga ham) 404 qaytaradi
Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/plan', [App\Http\Controllers\Admin\UserController::class, 'updatePlan'])->name('users.plan');
    Route::patch('/users/{user}/block', [App\Http\Controllers\Admin\UserController::class, 'toggleBlock'])->name('users.block');
    // Admin foydalanuvchi nomidan hayvonlarni fayldan yuklaydi (o'sha ikki bosqichli import)
    Route::get('/users/{user}/import', [AnimalImportController::class, 'create'])->whereNumber('user')->name('users.import');
    Route::post('/users/{user}/import', [AnimalImportController::class, 'upload'])->whereNumber('user')->middleware('throttle:10,1')->name('users.import.upload');
    Route::get('/users/{user}/import/{token}', [AnimalImportController::class, 'preview'])->whereNumber('user')->whereAlphaNumeric('token')->name('users.import.preview');
    Route::post('/users/{user}/import/{token}/confirm', [AnimalImportController::class, 'confirm'])->whereNumber('user')->whereAlphaNumeric('token')->name('users.import.confirm');
    Route::get('/users/{user}/import/{token}/errors', [AnimalImportController::class, 'errors'])->whereNumber('user')->whereAlphaNumeric('token')->name('users.import.errors');
    Route::delete('/users/{user}/import/{token}', [AnimalImportController::class, 'cancel'])->whereNumber('user')->whereAlphaNumeric('token')->name('users.import.cancel');
    Route::get('/ai-costs', [App\Http\Controllers\Admin\AiCostController::class, 'index'])->name('ai-costs');
    Route::get('/plans', [App\Http\Controllers\Admin\PlanController::class, 'index'])->name('plans.index');
    Route::patch('/plans/{plan}', [App\Http\Controllers\Admin\PlanController::class, 'update'])->name('plans.update');
    Route::get('/audit-log', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-log');
    Route::get('/tolovlar', [App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::post('/tolovlar/{payment}/bekor', [App\Http\Controllers\Admin\PaymentController::class, 'cancel'])->whereNumber('payment')->name('payments.cancel');
    Route::get('/fermalar/{farm}', [App\Http\Controllers\Admin\FarmController::class, 'show'])->whereNumber('farm')->name('farms.show');
    Route::post('/fermalar/{farm}/tolov', [App\Http\Controllers\Admin\FarmController::class, 'storePayment'])->whereNumber('farm')->name('farms.payment');
    Route::post('/fermalar/{farm}/bonus', [App\Http\Controllers\Admin\FarmController::class, 'storeBonus'])->whereNumber('farm')->name('farms.bonus');
});



require __DIR__.'/auth.php';




Route::get('/lang/{locale}', function (string $locale) {

    if (in_array($locale, ['uz', 'ru', 'uz_cyrl'])) {

        session(['locale' => $locale]);

        // Bot xabarlari foydalanuvchi tilida yuborilishi uchun
        auth()->user()?->forceFill(['locale' => $locale])->save();

    }

    return back();

})->name('lang.switch');




// Kunlik obuna ishining zaxira chaqiruvi (tashqi cron yoki qo'lda). Token bo'sh yoki noto'g'ri — 404
Route::get('/cron/obuna/{token}', [App\Http\Controllers\CronController::class, 'obuna'])->middleware('throttle:10,1')->name('cron.obuna');

Route::post('/telegram/webhook', [App\Http\Controllers\TelegramController::class, 'webhook'])->name('telegram.webhook');



Route::middleware(['auth'])->group(function () {

    Route::post('/telegram/generate-code', [App\Http\Controllers\TelegramController::class, 'generateLinkCode'])->name('telegram.generate-code');

});




Route::middleware(['auth', 'farm'])->group(function () {

    Route::post('/animals/{animal}/reminders', [App\Http\Controllers\ReminderController::class, 'store'])->can('reminders.manage')->name('reminders.store');

    Route::post('/animals/{animal}/reminders/{reminder}/bajarildi', [App\Http\Controllers\ReminderController::class, 'complete'])->can('reminders.complete')->name('reminders.complete');

    Route::delete('/animals/{animal}/reminders/{reminder}', [App\Http\Controllers\ReminderController::class, 'destroy'])->can('reminders.delete')->name('reminders.destroy');

});

