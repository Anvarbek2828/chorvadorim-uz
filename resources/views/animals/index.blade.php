
<x-app-layout>

    <x-slot name="header">

        <div class="flex justify-between items-center">

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



            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($animals->isEmpty())

                    <p class="text-gray-500">{{ __("Sizda hali hayvon qo'shilmagan. Yuqoridagi tugma orqali qo'shing.") }}</p>

                @else

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        @foreach ($animals as $animal)

                            <a href="{{ route('animals.show', $animal) }}" class="border rounded-lg p-4 hover:bg-gray-50 block">

                                <div class="flex justify-between items-start">

                                    <div>

                                        <div class="font-semibold text-lg">{{ $animal->name }}</div>

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

                                        <span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded">{{ __('Pasport bor') }}</span>

                                    @endif

                                </div>

                            </a>

                        @endforeach

                    </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>

