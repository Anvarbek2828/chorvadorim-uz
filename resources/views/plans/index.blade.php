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

                <style>
                    .chv-contact-buttons { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: .75rem; }
                    .chv-contact-btn { flex: 1 1 13rem; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 2.875rem; padding: .625rem 1rem; border-radius: .5rem; font-weight: 600; text-decoration: none; color: #fff; }
                    .chv-contact-btn svg { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }
                    .chv-contact-btn--phone { background: #16a34a; }
                    .chv-contact-btn--phone:hover, .chv-contact-btn--phone:focus { background: #15803d; }
                    .chv-contact-btn--telegram { background: #229ED9; }
                    .chv-contact-btn--telegram:hover, .chv-contact-btn--telegram:focus { background: #1b84b6; }
                    html.dark .chv-contact-btn, html.dark .chv-contact-btn span { color: #fff !important; }
                    @media (min-width: 640px) { .chv-contact-btn { flex: 0 1 auto; } }
                </style>

                <p class="text-sm text-gray-600 mt-4">
                    {{ __("Tarifni o'zgartirish yoki to'lov qilish uchun biz bilan bog'laning:") }}
                </p>
                <div class="chv-contact-buttons">
                    <a href="tel:{{ config('chorvadorim.contact.phone') }}" class="chv-contact-btn chv-contact-btn--phone">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                            <path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1l-2.3 2.2z"/>
                        </svg>
                        <span>{{ config('chorvadorim.contact.phone_display') }}</span>
                    </a>
                    <a href="https://t.me/{{ config('chorvadorim.contact.telegram') }}" target="_blank" rel="noopener" class="chv-contact-btn chv-contact-btn--telegram">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                            <path d="M3.4 11.3l16.1-6.2c.8-.3 1.4.2 1.2 1.3l-2.7 12.9c-.2.9-.7 1.1-1.5.7l-4.1-3-2 1.9c-.2.2-.4.4-.9.4l.3-4.2 7.6-6.9c.3-.3-.1-.5-.5-.2l-9.4 5.9-4-1.3c-.9-.3-.9-.9.2-1.3z"/>
                        </svg>
                        <span>{{ '@' . config('chorvadorim.contact.telegram') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
