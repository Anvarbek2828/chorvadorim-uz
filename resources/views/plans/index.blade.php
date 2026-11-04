<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tariflar') }}
        </h2>
    </x-slot>

    @php
        $current = $summary['plan'];
    @endphp

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-2">
                    {{ __('Joriy tarifingiz') }}: {{ $current->nomi }}
                </h3>

                @if ($planExpired)
                    <div class="mb-4 bg-orange-50 text-orange-700 p-3 rounded">
                        {{ __('Tarifingiz muddati :date kuni tugagan.', ['date' => $planEndsAt->format('d.m.Y')]) }}
                    </div>
                @elseif ($planEndsAt && $current->kod !== \App\Models\Plan::FREE)
                    <p class="text-sm text-gray-500 mb-4">
                        {{ __('Amal qilish muddati: :date gacha', ['date' => $planEndsAt->format('d.m.Y')]) }}
                    </p>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border rounded-lg p-4">
                        <div class="text-sm text-gray-500">{{ __('AI maslahat (shu oy)') }}</div>
                        <div class="text-xl font-semibold">{{ $summary['chat']['month_used'] }} / {{ $summary['chat']['month_limit'] }}</div>
                        <div class="text-sm text-gray-500">{{ __('Bugun') }}: {{ $summary['chat']['today_used'] }} / {{ $summary['chat']['today_limit'] }}</div>
                    </div>
                    <div class="border rounded-lg p-4">
                        <div class="text-sm text-gray-500">{{ __('Rasm tahlili (shu oy)') }}</div>
                        <div class="text-xl font-semibold">{{ $summary['vision']['month_used'] }} / {{ $summary['vision']['month_limit'] }}</div>
                        <div class="text-sm text-gray-500">{{ __('Bugun') }}: {{ $summary['vision']['today_used'] }} / {{ $summary['vision']['today_limit'] }}</div>
                    </div>
                    <div class="border rounded-lg p-4">
                        <div class="text-sm text-gray-500">{{ __('Hayvonlar') }}</div>
                        <div class="text-xl font-semibold">{{ $summary['animals'] }} / {{ $current->max_hayvon }}</div>
                    </div>
                </div>

                <p class="text-sm text-gray-500 mt-4">
                    {{ __('Oylik limitlar :date kuni yangilanadi. Kunlik limitlar har kuni yangilanadi.', ['date' => $summary['resets_at']->format('d.m.Y')]) }}
                </p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">{{ __('Mavjud tariflar') }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($plans as $plan)
                        <div class="border rounded-lg p-4 {{ $plan->id === $current->id ? 'bg-green-50' : '' }}">
                            <div class="flex justify-between items-start">
                                <div class="font-semibold text-lg">{{ $plan->nomi }}</div>
                                @if ($plan->id === $current->id)
                                    <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded">{{ __('Joriy tarif') }}</span>
                                @endif
                            </div>

                            <div class="mt-1 mb-3 font-semibold">
                                @if (!$plan->hasPrice())
                                    <span class="text-gray-500">{{ __("Narx bo'yicha murojaat qiling") }}</span>
                                @elseif ($plan->narx_som === 0)
                                    {{ __('Bepul') }}
                                @else
                                    {{ __(":price so'm / oy", ['price' => number_format($plan->narx_som, 0, '.', ' ')]) }}
                                @endif
                            </div>

                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>{{ __(':count tagacha hayvon', ['count' => $plan->max_hayvon]) }}</li>
                                <li>{{ __('Oyiga :count ta AI maslahat', ['count' => $plan->oylik_chat_limit]) }}</li>
                                <li>{{ __('Oyiga :count ta rasm tahlili', ['count' => $plan->oylik_vision_limit]) }}</li>
                            </ul>
                        </div>
                    @endforeach
                </div>

                <p class="text-sm text-gray-500 mt-4">
                    {{ __("Tarifni o'zgartirish uchun administratsiyaga murojaat qiling.") }}
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
