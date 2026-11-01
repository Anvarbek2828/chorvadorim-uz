@use('App\Services\AnimalImportService')

@php
    $directionNames = ['sut' => __('Sut'), 'gosht' => __("Go'sht"), 'nasl' => __('Nasl')];
    $genderNames = ['urgochi' => __("Urg'ochi"), 'erkak' => __('Erkak')];
    $listValues = fn (array $names) => collect($names)->map(fn ($name, $value) => $value . ' (' . $name . ')')->implode(', ');
    $dateHint = __('Masalan: 15.03.2023 yoki 2023-03-15');

    $columnHints = [
        'name' => __('Matn'),
        'direction' => $listValues($directionNames),
        'breed' => __('Matn'),
        'birth_date' => $dateHint,
        'initial_weight' => __('Masalan: 450 yoki 450,5'),
        'gender' => $listValues($genderNames) . '. ' . __("Bo'sh qoldirish mumkin."),
        'has_passport' => __("ha yoki yo'q. Bo'sh qolsa: pasport raqami yozilgan bo'lsa — ha."),
        'passport_number' => __('Takrorlanmasligi kerak: na fayl ichida, na tizimda.'),
        'chip_number' => __('Matn'),
        'passport_issue_date' => $dateHint,
        'notes' => __('Matn'),
    ];

    $freeSlots = max(0, $maxAnimals - $currentAnimals);
@endphp

<x-app-layout>
    <x-slot name="header">
        @if ($adminMode)
            @include('admin.partials.header', ['title' => __('Hayvonlarni yuklash')])
        @else
            <div class="flex flex-wrap justify-between items-center gap-2">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __("Hayvonlarni Excel'dan yuklash") }}
                </h2>
                <a href="{{ route('animals.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">
                    &larr; {{ __('Mening hayvonlarim') }}
                </a>
            </div>
        @endif
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('animals.partials.import-common')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 sm:p-6">
                <h3 class="font-semibold text-lg mb-3">{{ __('Qanday yuklanadi') }}</h3>

                <ol class="imp-steps space-y-1 text-sm text-gray-700">
                    <li>{{ __("Shablonni yuklab oling va Excel'da oching.") }}</li>
                    <li>{{ __("Chip va pasport raqami ustunlarini avval Matn (Текстовый) formatiga o'tkazing.") }}</li>
                    <li>{{ __("Har bir hayvonni alohida qatorga yozing. Birinchi qatordagi ustun nomlarini o'zgartirmang, namuna qatorlarni o'chiring.") }}</li>
                    <li>{{ __('Faylni .xlsx yoki .csv formatida saqlab, quyida yuklang.') }}</li>
                    <li>{{ __("Ko'rib chiqish sahifasida natijani tekshiring va \"Tasdiqlash\" tugmasini bosing. Ungacha hech narsa saqlanmaydi.") }}</li>
                </ol>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <a href="{{ route('animals.import.template') }}" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">
                        {{ __('Shablonni yuklab olish (CSV)') }}
                    </a>
                    <span class="text-sm text-gray-500">
                        {{ __('Tarif bo\'yicha faol hayvonlar: :current / :max. Yana :free ta qo\'shish mumkin.', ['current' => $currentAnimals, 'max' => $maxAnimals, 'free' => $freeSlots]) }}
                    </span>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 sm:p-6">
                <h3 class="font-semibold text-lg mb-1">{{ __('Ustunlar va ruxsat etilgan qiymatlar') }}</h3>
                <p class="text-sm text-gray-500 mb-3">{{ __("Ustun nomlari shablondagidek (o'zbek lotinida) bo'lishi kerak. Ustunlar tartibi muhim emas.") }}</p>

                <div class="imp-scroll">
                    <table class="imp-table">
                        <thead>
                            <tr>
                                <th>{{ __('Ustun') }}</th>
                                <th>{{ __('Majburiy') }}</th>
                                <th>{{ __('Qiymat') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (AnimalImportService::FIELDS as $field => $definition)
                                <tr>
                                    <td>
                                        <span class="font-semibold">{{ $definition['label'] }}</span>
                                        @if (__($definition['label']) !== $definition['label'])
                                            <div class="text-xs text-gray-500">{{ __($definition['label']) }}</div>
                                        @endif
                                    </td>
                                    <td>{{ ($definition['required'] ?? false) ? __('ha') : '—' }}</td>
                                    <td class="imp-wrap">{{ $columnHints[$field] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 sm:p-6">
                <h3 class="font-semibold text-lg mb-3">{{ __('Faylni yuklash') }}</h3>

                @unless ($xlsxSupported)
                    <div class="mb-4 bg-orange-50 text-orange-700 p-3 rounded text-sm">
                        {{ __("Serverda .xlsx fayllarni o'qib bo'lmaydi. Iltimos, faylni Excel'da \"CSV\" formatida saqlab yuklang.") }}
                    </div>
                @endunless

                <form method="POST" action="{{ $uploadUrl }}" enctype="multipart/form-data" onsubmit="this.querySelector('button[type=submit]').disabled = true">
                    @csrf
                    <input type="file" name="file" accept="{{ $xlsxSupported ? '.xlsx,.csv' : '.csv' }}" required class="block w-full text-sm">
                    <p class="mt-2 text-xs text-gray-500">
                        {{ __('Ko\'pi bilan 2 MB va :max qator. CSV: UTF-8 yoki Windows-1251, ajratgich ";" yoki ",".', ['max' => AnimalImportService::MAX_ROWS]) }}
                    </p>
                    <button type="submit" class="mt-4 bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">
                        {{ __('Yuklash va tekshirish') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
