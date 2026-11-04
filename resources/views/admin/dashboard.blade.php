@use('App\Support\Money')

@php
    $periodLabels = ['today' => __('Bugun'), 'week' => __('7 kun'), 'month' => __('30 kun')];
    $budgetPercent = $budget > 0 ? min(100, round($costMonth / $budget * 100)) : 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin.partials.header', ['title' => __('Bosh sahifa')])
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($budget > 0 && $costMonth >= $budget)
                <div class="bg-red-100 text-red-700 p-3 rounded">
                    {{ __("Shu oydagi AI xarajati platforma chegarasiga yetdi (:cost / :budget). AI so'rovlari oy oxirigacha to'xtatilgan. Chegarani config/ai.php faylida o'zgartirish mumkin.", ['cost' => Money::usd($costMonth), 'budget' => Money::usd($budget)]) }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Jami foydalanuvchilar') }}</div>
                    <div class="adm-stat">{{ $totalUsers }}</div>
                    <div class="text-sm text-gray-500 mt-1">
                        {{ __('Botga ulangan') }}: {{ $botLinked }} · {{ __('Bloklangan') }}: {{ $blockedUsers }}
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Hayvonlar') }}</div>
                    <div class="adm-stat">{{ $animals }}</div>
                    <div class="text-sm text-gray-500 mt-1">{{ __('Arxivda') }}: {{ $archivedAnimals }}</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Faol foydalanuvchilar') }}</div>
                    <div class="grid grid-cols-3 gap-2 mt-2">
                        @foreach ($periodLabels as $key => $label)
                            <div>
                                <div class="adm-stat">{{ $activeUsers[$key] }}</div>
                                <div class="text-xs text-gray-500">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="text-xs text-gray-400 mt-2">{{ __("Faollik admin panel o'rnatilgan kundan boshlab hisoblanadi.") }}</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __("Yangi ro'yxatdan o'tganlar") }}</div>
                    <div class="grid grid-cols-3 gap-2 mt-2">
                        @foreach ($periodLabels as $key => $label)
                            <div>
                                <div class="adm-stat">{{ $newUsers[$key] }}</div>
                                <div class="text-xs text-gray-500">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Bugungi AI xarajati') }}</div>
                    <div class="adm-stat">{{ Money::usd($costToday) }}</div>
                    <div class="text-sm text-gray-500">≈ {{ Money::som($costToday) }}</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Shu oydagi AI xarajati') }}</div>
                    <div class="adm-stat">{{ Money::usd($costMonth) }}</div>
                    <div class="text-sm text-gray-500">≈ {{ Money::som($costMonth) }}</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
                        <div class="adm-bar" style="width: {{ $budgetPercent }}%"></div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ __('Oylik chegara: :budget (:percent% ishlatildi)', ['budget' => Money::usd($budget), 'percent' => $budgetPercent]) }}
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">{{ __("Tariflar bo'yicha foydalanuvchilar") }}</h3>
                <div class="adm-scroll">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>{{ __('Tarif') }}</th>
                                <th class="adm-num">{{ __('Foydalanuvchilar') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plans as $plan)
                                <tr>
                                    <td>
                                        {{ $plan->nomi }}
                                        @unless ($plan->faol)
                                            <span class="text-xs text-gray-500">({{ __('nofaol') }})</span>
                                        @endunless
                                    </td>
                                    <td class="adm-num">{{ $plan->users_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($expiredPlans > 0)
                    <p class="text-sm text-gray-500 mt-3">
                        {{ __('Shulardan :count tasining tarif muddati tugagan.', ['count' => $expiredPlans]) }}
                    </p>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">{{ __('Kunlik tekshiruv') }}</h3>
                <div class="adm-scroll">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>{{ __('Ish') }}</th>
                                <th>{{ __('Oxirgi ishga tushgan') }}</th>
                                <th>{{ __('Tugagan') }}</th>
                                <th>{{ __('Natija') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($systemRuns as $run)
                                <tr>
                                    <td>{{ ['obuna' => __('Obuna xabarlari'), 'eslatmalar' => __('Eslatmalar')][$run->nom] ?? $run->nom }}</td>
                                    <td>{{ $run->last_started_at ? \Illuminate\Support\Carbon::parse($run->last_started_at, 'UTC')->timezone(config('ai.timezone'))->format('d.m.Y H:i') : __('Hali ishlamagan') }}</td>
                                    <td>{{ $run->last_finished_at ? \Illuminate\Support\Carbon::parse($run->last_finished_at, 'UTC')->timezone(config('ai.timezone'))->format('d.m.Y H:i') : '—' }}</td>
                                    <td class="adm-wrap"><span class="adm-pre">{{ $run->last_result ?? '—' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-400 mt-2">{{ __("Har kuni Toshkent vaqti bilan 08:00 dan keyingi birinchi so'rovda ishga tushadi.") }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
