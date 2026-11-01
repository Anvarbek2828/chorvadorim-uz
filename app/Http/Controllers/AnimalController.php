<?php



namespace App\Http\Controllers;



use App\Models\Animal;

use App\Models\BankAnimalWatcher;

use App\Services\AiUsageService;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

use Illuminate\Validation\Rule;



class AnimalController extends Controller

{

    public function index(Request $request)
    {
        $baseQuery = Animal::where('user_id', auth()->id());

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
                // orWhere'lar alohida guruhda: user_id sharti har doim amal qiladi
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



        if ($limitMessage = app(AiUsageService::class)->animalLimitMessage(auth()->user())) {
            return back()->withInput()->withErrors(['plan_limit' => $limitMessage]);
        }

        $validated['user_id'] = auth()->id();

        $validated['current_weight'] = $validated['initial_weight'] ?? null;

        $validated['has_passport'] = $request->has('has_passport');



        Animal::create($validated);



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



        $animal->update($validated);



        return redirect()->route('animals.show', $animal)->with('success', 'Ma\'lumotlar yangilandi!');

    }



    public function destroy(Animal $animal)

    {

        $this->checkOwnership($animal);



        $this->notifyBankWatchers($animal, "o'chirildi (arxivga o'tkazildi - sotilgan yoki tizimdan olib tashlangan bo'lishi mumkin)");



        $animal->delete();



        return redirect()->route('animals.index')->with('success', 'Hayvon arxivga o\'tkazildi. Uni "Arxiv" bo\'limidan qayta tiklashingiz mumkin.');

    }



    public function archive()

    {

        $animals = Animal::onlyTrashed()->where('user_id', auth()->id())
            ->orderByDesc('deleted_at')
            ->orderByDesc('id')
            ->paginate(24);

        return view('animals.archive', compact('animals'));

    }



    public function restore($id)

    {

        $animal = Animal::onlyTrashed()->findOrFail($id);

        $this->checkOwnership($animal);

        if ($limitMessage = app(AiUsageService::class)->animalLimitMessage(auth()->user())) {
            return redirect()->route('animals.archive')->with('error', $limitMessage)->with('show_plans_link', true);
        }

        $animal->restore();



        $this->notifyBankWatchers($animal, "qayta tiklandi (endi faol holatda)");



        return redirect()->route('animals.archive')->with('success', 'Hayvon qayta tiklandi.');

    }



    public function forceDelete($id)

    {

        $animal = Animal::onlyTrashed()->findOrFail($id);

        $this->checkOwnership($animal);



        $this->notifyBankWatchers($animal, "butunlay tizimdan o'chirildi");



        $animal->forceDelete();



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



    private function checkOwnership(Animal $animal)

    {

        $animalUserId = (int) $animal->user_id;

        $currentUserId = (int) auth()->id();



        if ($animalUserId !== $currentUserId) {

            abort(403, 'Sizga bu ma\'lumotni ko\'rish huquqi berilmagan.');

        }

    }

}

