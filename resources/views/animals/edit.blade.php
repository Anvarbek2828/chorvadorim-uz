<x-app-layout>

    <x-slot name="header">

        <h2 class="font-semibold text-xl text-gray-800 leading-tight">

            {{ $animal->name }} {{ __('ni tahrirlash') }}

        </h2>

    </x-slot>

    <div class="py-12">

        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())

                    <div class="mb-4 bg-red-100 text-red-700 p-3 rounded">

                        <ul class="list-disc list-inside">

                            @foreach ($errors->all() as $error)

                                <li>{{ $error }}</li>

                            @endforeach

                        </ul>

                    </div>

                @endif

                <form method="POST" action="{{ route('animals.update', $animal) }}">

                    @csrf

                    @method('PUT')

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700 mb-2">{{ __("Yo'nalish") }}</label>

                        <div class="grid grid-cols-3 gap-3">

                            <label class="border rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50">

                                <input type="radio" name="direction" value="sut" class="mb-2" {{ $animal->direction === 'sut' ? 'checked' : '' }} required>

                                <div class="font-semibold">&#x1F95B; {{ __('Sut') }}</div>

                            </label>

                            <label class="border rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50">

                                <input type="radio" name="direction" value="gosht" class="mb-2" {{ $animal->direction === 'gosht' ? 'checked' : '' }} required>

                                <div class="font-semibold">&#x1F969; {{ __("Go'sht") }}</div>

                            </label>

                            <label class="border rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50">

                                <input type="radio" name="direction" value="nasl" class="mb-2" {{ $animal->direction === 'nasl' ? 'checked' : '' }} required>

                                <div class="font-semibold">&#x1F404; {{ __('Nasl') }}</div>

                            </label>

                        </div>

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Hayvon nomi/lavozimi') }}</label>

                        <input type="text" name="name" value="{{ old('name', $animal->name) }}" class="mt-1 block w-full rounded-md border-gray-300" required>

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Zoti') }}</label>

                        <input type="text" name="breed" value="{{ old('breed', $animal->breed) }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __("Tug'ilgan sanasi") }}</label>

                        <input type="date" name="birth_date" value="{{ old('birth_date', $animal->birth_date ? $animal->birth_date->format('Y-m-d') : '') }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __("Boshlang'ich vazni (kg)") }}</label>

                        <input type="number" step="0.01" name="initial_weight" value="{{ old('initial_weight', $animal->initial_weight) }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Joriy vazni (kg)') }}</label>

                        <input type="number" step="0.01" name="current_weight" value="{{ old('current_weight', $animal->current_weight) }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Jinsi') }}</label>

                        <select name="gender" class="mt-1 block w-full rounded-md border-gray-300">

                            <option value="">{{ __('Tanlang') }}</option>

                            <option value="urgochi" {{ $animal->gender === 'urgochi' ? 'selected' : '' }}>{{ __("Urg'ochi") }}</option>

                            <option value="erkak" {{ $animal->gender === 'erkak' ? 'selected' : '' }}>{{ __('Erkak') }}</option>

                        </select>

                    </div>

                    <div class="mb-4 border-t pt-4">

                        <label class="flex items-center">

                            <input type="checkbox" name="has_passport" value="1" class="mr-2" {{ $animal->has_passport ? 'checked' : '' }}>

                            <span class="font-medium text-sm text-gray-700">{{ __('Chorva pasporti bor') }}</span>

                        </label>

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Pasport raqami') }}</label>

                        <input type="text" name="passport_number" value="{{ old('passport_number', $animal->passport_number) }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Chip/teg raqami') }}</label>

                        <input type="text" name="chip_number" value="{{ old('chip_number', $animal->chip_number) }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Pasport berilgan sana') }}</label>

                        <input type="date" name="passport_issue_date" value="{{ old('passport_issue_date', $animal->passport_issue_date ? $animal->passport_issue_date->format('Y-m-d') : '') }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __("Qo'shimcha izoh") }}</label>

                        <textarea name="notes" class="mt-1 block w-full rounded-md border-gray-300">{{ old('notes', $animal->notes) }}</textarea>

                    </div>

                    <div class="flex items-center gap-3">

                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700">

                            {{ __('Saqlash') }}

                        </button>

                        <a href="{{ route('animals.show', $animal) }}" class="text-gray-600 hover:text-gray-900">{{ __('Bekor qilish') }}</a>

                    </div>

                </form>



                <form method="POST" action="{{ route('animals.destroy', $animal) }}" class="mt-6 border-t pt-4" onsubmit='return confirm("{{ __("Rostdan ham o'chirmoqchimisiz?") }}");'>

                    @csrf

                    @method('DELETE')

                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">

                        {{ __("Bu hayvonni o'chirish") }}

                    </button>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>

