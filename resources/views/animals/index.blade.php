
<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-wrap justify-between items-center gap-2">

            <h2 class="font-semibold text-xl text-gray-800 leading-tight">

                {{ __('Mening hayvonlarim') }}

            </h2>

            <div class="flex gap-2">

                <a href="{{ route('animals.archive') }}" class="text-gray-600 hover:text-gray-900 text-sm self-center">

                    {{ __('Arxiv') }}

                </a>

                <a href="{{ route('animals.create') }}" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">

                    {{ __("+ Yangi hayvon qo'shish") }}

                </a>

            </div>

        </div>

    </x-slot>



    <div class="py-12">

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">



            @if (session('success'))

                <div class="mb-4 bg-green-100 text-green-700 p-3 rounded">

                    {{ session('success') }}

                </div>

            @endif



            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 sm:p-6">

                @if ($animals->total() === 0 && ! $hasFilters)

                    <p class="text-gray-500">{{ __("Sizda hali hayvon qo'shilmagan. Yuqoridagi tugma orqali qo'shing.") }}</p>

                @else

                    <form method="GET" action="{{ route('animals.index') }}" class="mb-4">
                        <div class="flex gap-2">
                            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Nomi, pasport yoki chip raqami') }}" class="flex-1 min-w-0 border-gray-300 rounded-md shadow-sm text-sm">
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">{{ __('Qidirish') }}</button>
                        </div>

                        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <label class="block">
                                <span class="block text-xs text-gray-500 mb-1">{{ __("Yo'nalish") }}</span>
                                <select name="direction" onchange="this.form.submit()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    <option value="">{{ __('Hammasi') }}</option>
                                    @foreach ($directions as $direction)
                                        <option value="{{ $direction }}" @selected($filters['direction'] === $direction)>
                                            @if ($direction === 'sut') {{ __('Sut') }}
                                            @elseif ($direction === 'gosht') {{ __("Go'sht") }}
                                            @else {{ __('Nasl') }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="block text-xs text-gray-500 mb-1">{{ __('Jinsi') }}</span>
                                <select name="gender" onchange="this.form.submit()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    <option value="">{{ __('Hammasi') }}</option>
                                    <option value="urgochi" @selected($filters['gender'] === 'urgochi')>{{ __("Urg'ochi") }}</option>
                                    <option value="erkak" @selected($filters['gender'] === 'erkak')>{{ __('Erkak') }}</option>
                                </select>
                            </label>

                            <label class="block">
                                <span class="block text-xs text-gray-500 mb-1">{{ __('Pasport') }}</span>
                                <select name="passport" onchange="this.form.submit()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    <option value="">{{ __('Hammasi') }}</option>
                                    <option value="bor" @selected($filters['passport'] === 'bor')>{{ __('Pasport bor') }}</option>
                                    <option value="yoq" @selected($filters['passport'] === 'yoq')>{{ __("Pasport yo'q") }}</option>
                                </select>
                            </label>

                            <label class="block">
                                <span class="block text-xs text-gray-500 mb-1">{{ __('Tartiblash') }}</span>
                                <select name="sort" onchange="this.form.submit()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    <option value="yangi" @selected($filters['sort'] === 'yangi')>{{ __('Avval yangilari') }}</option>
                                    <option value="eski" @selected($filters['sort'] === 'eski')>{{ __('Avval eskilari') }}</option>
                                    <option value="nomi" @selected($filters['sort'] === 'nomi')>{{ __("Nomi bo'yicha (A-Z)") }}</option>
                                </select>
                            </label>
                        </div>
                    </form>

                    <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
                        <div class="text-sm font-semibold text-gray-700">{{ __('Jami: :count ta hayvon', ['count' => $animals->total()]) }}</div>
                        @if ($hasFilters)
                            <a href="{{ route('animals.index', $filters['sort'] !== 'yangi' ? ['sort' => $filters['sort']] : []) }}" class="text-gray-600 hover:text-gray-900 text-sm">{{ __('Tozalash') }}</a>
                        @endif
                    </div>

                    @if ($animals->isEmpty())

                        <p class="text-gray-500">{{ __("Hech qanday hayvon topilmadi. Qidiruv yoki filtrlarni o'zgartirib ko'ring.") }}</p>

                    @else

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        @foreach ($animals as $animal)

                            <a href="{{ route('animals.show', $animal) }}" class="border rounded-lg p-4 hover:bg-gray-50 block">

                                <div class="flex justify-between items-start gap-2">

                                    <div class="min-w-0">

                                        <div class="font-semibold text-lg break-words">{{ $animal->name }}</div>

                                        <div class="text-sm text-gray-500">

                                            @if ($animal->direction === 'sut') &#x1F95B; Sut

                                            @elseif ($animal->direction === 'gosht') &#x1F969; Go'sht

                                            @else &#x1F404; Nasl

                                            @endif

                                        </div>

                                        @if ($animal->breed)

                                            <div class="text-sm text-gray-500">{{ __('Zoti') }}: {{ $animal->breed }}</div>

                                        @endif

                                    </div>

                                    @if ($animal->has_passport)

                                        <span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded shrink-0">{{ __('Pasport bor') }}</span>

                                    @endif

                                </div>

                            </a>

                        @endforeach

                    </div>

                    {{ $animals->onEachSide(1)->links('animals.partials.pagination') }}

                    @endif

                @endif

            </div>

        </div>

    </div>

</x-app-layout>
