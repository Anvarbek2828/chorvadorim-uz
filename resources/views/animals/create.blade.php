<x-app-layout>

    <x-slot name="header">

        <h2 class="font-semibold text-xl text-gray-800 leading-tight">

            {{ __("Yangi hayvon qo'shish (forma)") }}

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
                        @if ($errors->has('plan_limit'))
                            @include('plans.partials.upgrade-link')
                        @endif

                    </div>

                @endif

                <form method="POST" action="{{ route('animals.store') }}">

                    @csrf

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700 mb-2">{{ __("Qaysi yo'nalishda boqmoqchisiz?") }}</label>

                        <div class="grid grid-cols-3 gap-3">

                            <label class="border rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50">

                                <input type="radio" name="direction" value="sut" class="mb-2" required>

                                <div class="font-semibold">&#x1F95B; {{ __('Sut') }}</div>

                            </label>

                            <label class="border rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50">

                                <input type="radio" name="direction" value="gosht" class="mb-2" required>

                                <div class="font-semibold">&#x1F969; {{ __("Go'sht") }}</div>

                            </label>

                            <label class="border rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50">

                                <input type="radio" name="direction" value="nasl" class="mb-2" required>

                                <div class="font-semibold">&#x1F404; {{ __('Nasl') }}</div>

                            </label>

                        </div>

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Hayvon nomi/lavozimi') }}</label>

                        <input type="text" name="name" value="{{ old('name') }}" class="mt-1 block w-full rounded-md border-gray-300" required>

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Zoti') }}</label>

                        <input type="text" name="breed" value="{{ old('breed') }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __("Tug'ilgan sanasi") }}</label>

                        <input type="date" name="birth_date" value="{{ old('birth_date') }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __("Boshlang'ich vazni (kg)") }}</label>

                        <input type="number" step="0.01" name="initial_weight" value="{{ old('initial_weight') }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Jinsi') }}</label>

                        <select name="gender" class="mt-1 block w-full rounded-md border-gray-300">

                            <option value="">{{ __('Tanlang') }}</option>

                            <option value="urgochi">{{ __("Urg'ochi") }}</option>

                            <option value="erkak">{{ __('Erkak') }}</option>

                        </select>

                    </div>

                    <div class="mb-4 border-t pt-4">

                        <label class="flex items-center">

                            <input type="checkbox" name="has_passport" value="1" class="mr-2">

                            <span class="font-medium text-sm text-gray-700">{{ __('Chorva pasporti bor') }}</span>

                        </label>

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Pasport raqami') }}</label>

                        <input type="text" name="passport_number" value="{{ old('passport_number') }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Chip/teg raqami') }}</label>

                        <input type="text" name="chip_number" value="{{ old('chip_number') }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __('Pasport berilgan sana') }}</label>

                        <input type="date" name="passport_issue_date" value="{{ old('passport_issue_date') }}" class="mt-1 block w-full rounded-md border-gray-300">

                    </div>

                    <div class="mb-4">

                        <label class="block font-medium text-sm text-gray-700">{{ __("Qo'shimcha izoh") }}</label>

                        <textarea name="notes" class="mt-1 block w-full rounded-md border-gray-300">{{ old('notes') }}</textarea>

                    </div>

                    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700">

                        {{ __('Saqlash') }}

                    </button>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>

