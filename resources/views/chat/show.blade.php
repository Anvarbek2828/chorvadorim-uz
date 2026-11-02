<x-app-layout>

    <x-slot name="header">

        <div class="flex justify-between items-center">

            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $animal->name }} {{ __('bilan maslahat') }}</h2>

            <a href="{{ route('animals.show', $animal) }}" class="text-gray-600 hover:text-gray-900 text-sm">{{ __('Orqaga qaytish') }}</a>

        </div>

    </x-slot>



    <div class="py-12">

        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('error'))

            <div class="mb-4 bg-red-100 text-red-700 p-3 rounded">
                {{ session('error') }}
                @if (session('show_plans_link'))
                    @include('plans.partials.upgrade-link')
                @endif
            </div>

            @endif



            @can('ai.use')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('chat.ask', $animal) }}">

                    @csrf

                    <label class="block text-sm font-medium text-gray-700 mb-2">

                        {{ $messages->isNotEmpty() ? __("Qo'shimcha savolingiz") : __('Savolingizni yozing') }}

                    </label>

                    <textarea name="question" rows="3" class="block w-full rounded-md border-gray-300" placeholder="{{ __('Masalan: Bu hayvonga qanday ozuqa berishim kerak?') }}" required>{{ old('question') }}</textarea>

                    <button type="submit" class="mt-3 bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">{{ __('Savol berish') }}</button>

                </form>

            </div>

            @endcan



            @php

                $reversedMessages = $messages->reverse()->values();

                $recentMessages = $reversedMessages->slice(0, 2);

                $olderMessages = $reversedMessages->slice(2);

            @endphp



            @if ($recentMessages->isNotEmpty())

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-6">

                @foreach ($recentMessages as $msg)

                <div>

                    <div class="mb-2 text-sm text-gray-500">{{ __('Savol') }}:</div>

                    <div class="mb-3 font-medium bg-gray-50 rounded p-3">{{ $msg->question }}</div>

                    <div class="mb-2 text-sm text-gray-500">{{ __('Javob') }}:</div>

                    <div class="text-gray-800">{!! $msg->answer !!}</div>

                    <x-ai-disclaimer />

                </div>

                @if (!$loop->last)

                <hr>

                @endif

                @endforeach

            </div>

            @endif



            @if ($olderMessages->isNotEmpty())

            <details class="bg-white rounded-lg shadow-sm">

                <summary class="cursor-pointer p-4 text-sm text-gray-500 hover:text-gray-700">

                    {{ __("Oldingi suhbatni ko'rish") }} ({{ $olderMessages->count() }} {{ __('ta') }})

                </summary>

                <div class="p-6 pt-0 space-y-6 border-t">

                    @foreach ($olderMessages as $msg)

                    <div>

                        <div class="mb-2 text-sm text-gray-500">{{ __('Savol') }}:</div>

                        <div class="mb-3 font-medium bg-gray-50 rounded p-3">{{ $msg->question }}</div>

                        <div class="mb-2 text-sm text-gray-500">{{ __('Javob') }}:</div>

                        <div class="text-gray-800">{!! $msg->answer !!}</div>

                        <x-ai-disclaimer />

                    </div>

                    @if (!$loop->last)

                    <hr>

                    @endif

                    @endforeach

                </div>

            </details>

            @endif

        </div>

    </div>

</x-app-layout>

