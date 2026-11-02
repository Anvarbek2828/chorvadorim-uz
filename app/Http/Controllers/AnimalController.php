<?php



namespace App\Http\Controllers;



use App\Models\Animal;

use App\Models\BankAnimalWatcher;

use App\Models\FarmAuditLog;

use App\Services\AiUsageService;

use App\Services\FarmAuditService;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

use Illuminate\Validation\Rule;



class AnimalController extends Controller

{

    public function index(Request $request)
    {
        $baseQuery = Animal::where('farm_id', $this->currentFarm()->id);

        // Filtrda faqat foydalanuvchida haqiqatda bor yo'nalishlar ko'rsatiladi
        $directions = array_values(array_intersect(
            ['sut', 'gosht', 'nasl'],
            (clone $baseQuery)->distinct()->pluck('direction')->all()
        ));

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'direction' => in_array($request->query('direction'), $directions, true) ? $request->query('direction') : '',
            'gender' => in_array($request->query('gender'), ['urgochi', 'erkak'], true) ? $request->query('gender') : '',
            'passport' => in_array($request->query('passport'), ['bor', 'yoq'], true) ? $request->query('passport') : '',
            'sort' => in_array($request->query('sort'), ['yangi', 'eski', 'nomi'], true) ? $request->query('sort') : 'yangi',
        ];

        $query = (clone $baseQuery)
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
                // orWhere'lar alohida guruhda: farm_id sharti har doim amal qiladi
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('passport_number', 'like', $like)
                        ->orWhere('chip_number', 'like', $like);
                });
            })
            ->when($filters['direction'] !== '', fn ($query) => $query->where('direction', $filters['direction']))
            ->when($filters['gender'] !== '', fn ($query) => $query->where('gender', $filters['gender']))
            ->when($filters['passport'] !== '', fn ($query) => $query->where('has_passport', $filters['passport'] === 'bor'));

        match ($filters['sort']) {
            'nomi' => $query->orderBy('name')->orderBy('id'),
            'eski' => $query->orderBy('created_at')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $animals = $query->paginate(24)->withQueryString();

        $hasFilters = $filters['q'] !== '' || $filters['direction'] !== '' || $filters['gender'] !== '' || $filters['passport'] !== '';

        return view('animals.index', compact('animals', 'directions', 'filters', 'hasFilters'));
    }



    public function create()

    {

        return view('animals.create');

    }



    public function store(Request $request)

    {

        $validated = $request->validate([

            'name' => 'required|string|max:255',

            'direction' => 'required|in:sut,gosht,nasl',

            'breed' => 'nullable|string|max:255',

            'birth_date' => 'nullable|date',

            'initial_weight' => 'nullable|numeric',

            'gender' => 'nullable|string|max:50',

            'notes' => 'nullable|string',

            'has_passport' => 'nullable|boolean',

            'passport_number' => ['nullable', 'string', 'max:255', Rule::unique('animals', 'passport_number')],

            'chip_number' => 'nullable|string|max:255',

            'passport_issue_date' => 'nullable|date',

        ], [

            'passport_number.unique' => __("Bu pasport raqamiga ega hayvon tizimda allaqachon mavjud."),

        ]);



        $farm = $this->currentFarm();

        if ($limitMessage = app(AiUsageService::class)->animalLimitMessage($farm)) {
            return back()->withInput()->withErrors(['plan_limit' => $limitMessage]);
        }

        $validated['farm_id'] = $farm->id;

        $validated['user_id'] = auth()->id();

        $validated['current_weight'] = $validated['initial_weight'] ?? null;

        $validated['has_passport'] = $request->has('has_passport');



        $animal = Animal::create($validated);

        app(FarmAuditService::class)->log($farm, FarmAuditLog::ACTION_ANIMAL_CREATED, 'animal', $animal->id, [], $this->auditState($animal, true));



        return redirect()->route('animals.index')->with('success', 'Hayvon muvaffaqiyatli qo\'shildi!');

    }



    public function show(Animal $animal)

    {

        $this->checkOwnership($animal);

        return view('animals.show', compact('animal'));

    }



    public function edit(Animal $animal)

    {

        $this->checkOwnership($animal);

        return view('animals.edit', compact('animal'));

    }



    public function update(Request $request, Animal $animal)

    {

        $this->checkOwnership($animal);



        $validated = $request->validate([

            'name' => 'required|string|max:255',

            'direction' => 'required|in:sut,gosht,nasl',

            'breed' => 'nullable|string|max:255',

            'birth_date' => 'nullable|date',

            'initial_weight' => 'nullable|numeric',

            'current_weight' => 'nullable|numeric',

            'gender' => 'nullable|string|max:50',

            'notes' => 'nullable|string',

            'has_passport' => 'nullable|boolean',

            'passport_number' => ['nullable', 'string', 'max:255', Rule::unique('animals', 'passport_number')->ignore($animal->id)],

            'chip_number' => 'nullable|string|max:255',

            'passport_issue_date' => 'nullable|date',

        ], [

            'passport_number.unique' => __("Bu pasport raqamiga ega hayvon tizimda allaqachon mavjud."),

        ]);



        $validated['has_passport'] = $request->has('has_passport');



        $before = $this->auditState($animal);

        $animal->update($validated);

        // Faqat o'zgargan maydonlar; bazadagi ko'rinishda solishtirish uchun qayta o'qiladi
        $audit = app(FarmAuditService::class);
        [$old, $new] = $audit->diff($before, $this->auditState($animal->fresh()));

        if ($new !== []) {
            $audit->log($animal->farm_id, FarmAuditLog::ACTION_ANIMAL_UPDATED, 'animal', $animal->id, $old, $new);
        }



        return redirect()->route('animals.show', $animal)->with('success', 'Ma\'lumotlar yangilandi!');

    }



    public function destroy(Animal $animal)

    {

        $this->checkOwnership($animal);



        $this->notifyBankWatchers($animal, "o'chirildi (arxivga o'tkazildi - sotilgan yoki tizimdan olib tashlangan bo'lishi mumkin)");



        $animal->delete();

        app(FarmAuditService::class)->log($animal->farm_id, FarmAuditLog::ACTION_ANIMAL_DELETED, 'animal', $animal->id, ['name' => $animal->name]);



        return redirect()->route('animals.index')->with('success', 'Hayvon arxivga o\'tkazildi. Uni "Arxiv" bo\'limidan qayta tiklashingiz mumkin.');

    }



    public function archive()

    {

        $animals = Animal::onlyTrashed()->where('farm_id', $this->currentFarm()->id)
            ->orderByDesc('deleted_at')
            ->orderByDesc('id')
            ->paginate(24);

        return view('animals.archive', compact('animals'));

    }



    public function restore($id)

    {

        // Boshqa fermaning hayvoni — 404
        $animal = Animal::onlyTrashed()->where('farm_id', $this->currentFarm()->id)->findOrFail($id);

        if ($limitMessage = app(AiUsageService::class)->animalLimitMessage($this->currentFarm())) {
            return redirect()->route('animals.archive')->with('error', $limitMessage)->with('show_plans_link', true);
        }

        $animal->restore();

        app(FarmAuditService::class)->log($animal->farm_id, FarmAuditLog::ACTION_ANIMAL_RESTORED, 'animal', $animal->id, [], ['name' => $animal->name]);



        $this->notifyBankWatchers($animal, "qayta tiklandi (endi faol holatda)");



        return redirect()->route('animals.archive')->with('success', 'Hayvon qayta tiklandi.');

    }



    public function forceDelete($id)

    {

        $animal = Animal::onlyTrashed()->where('farm_id', $this->currentFarm()->id)->findOrFail($id);



        $this->notifyBankWatchers($animal, "butunlay tizimdan o'chirildi");



        $animal->forceDelete();

        app(FarmAuditService::class)->log($animal->farm_id, FarmAuditLog::ACTION_ANIMAL_FORCE_DELETED, 'animal', $animal->id, $this->auditState($animal, true));



        return redirect()->route('animals.archive')->with('success', 'Hayvon butunlay o\'chirildi.');

    }



    private function notifyBankWatchers(Animal $animal, string $eventText)

    {

        $watchers = BankAnimalWatcher::where('animal_id', $animal->id)
            ->where('status', BankAnimalWatcher::STATUS_APPROVED)
            ->get();



        if ($watchers->isEmpty()) {

            return;

        }



        $warning = "\u{26A0}";

        $text = "{$warning} OGOHLANTIRISH\n\n\"{$animal->name}\" (pasport: " . ($animal->passport_number ?? 'yo\'q') . ") {$eventText}.";



        $token = config('services.telegram.bot_token');



        foreach ($watchers as $watcher) {

            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [

                'chat_id' => $watcher->telegram_chat_id,

                'text' => $text,

            ]);

        }

    }



    /**
     * Ferma jurnali uchun hayvon maydonlari. $withoutEmpty — bo'sh maydonlarsiz (qo'shish/o'chirish yozuvlari uchun).
     */
    private function auditState(Animal $animal, bool $withoutEmpty = false): array
    {
        $state = $animal->only([
            'name', 'direction', 'breed', 'birth_date', 'initial_weight', 'current_weight', 'gender',
            'notes', 'has_passport', 'passport_number', 'chip_number', 'passport_issue_date',
        ]);

        return $withoutEmpty ? array_filter($state, fn ($value) => $value !== null && $value !== '') : $state;
    }



    private function checkOwnership(Animal $animal)

    {

        $this->ensureAnimalInFarm($animal);

    }

}

