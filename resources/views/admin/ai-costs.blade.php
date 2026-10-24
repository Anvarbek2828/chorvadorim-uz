@use('App\Support\Money')

@php
    // $days kalitlari sana (string), shuning uchun spread emas, array_values ishlatiladi
    $dayTotals = array_values(array_map(fn ($day) => $day['jami'], $days));
    $maxDay = $dayTotals === [] ? 0 : max($dayTotals);
    $periodTotal = array_sum($dayTotals);
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin.partials.header', ['title' => __('AI xarajatlari')])
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-1">{{ __('Kunlar bo\'yicha (oxirgi :days kun)', ['days' => $periodDays]) }}</h3>
                <p class="text-sm text-gray-500 mb-4">
                    {{ __('Jami') }}: {{ Money::usd($periodTotal) }} ≈ {{ Money::som($periodTotal) }}
                </p>

                <div class="adm-scroll">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>{{ __('Sana') }}</th>
                                <th class="adm-num">{{ __('Chat soni') }}</th>
                                <th class="adm-num">{{ __('Chat xarajati') }}</th>
                                <th class="adm-num">{{ __('Rasm tahlili soni') }}</th>
                                <th class="adm-num">{{ __('Rasm tahlili xarajati') }}</th>
                                <th class="adm-num">{{ __('Jami ($)') }}</th>
                                <th class="adm-num">{{ __("Jami (so'm)") }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($days as $date => $day)
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($date)->format('d.m.Y') }}</td>
                                    <td class="adm-num">{{ $day['chat']['soni'] }}</td>
                                    <td class="adm-num">{{ Money::usd($day['chat']['narx']) }}</td>
                                    <td class="adm-num">{{ $day['vision']['soni'] }}</td>
                                    <td class="adm-num">{{ Money::usd($day['vision']['narx']) }}</td>
                                    <td class="adm-num font-semibold">{{ Money::usd($day['jami']) }}</td>
                                    <td class="adm-num">{{ Money::som($day['jami']) }}</td>
                                    <td style="min-width: 8rem;">
                                        <div class="adm-bar" style="width: {{ $maxDay > 0 ? round($day['jami'] / $maxDay * 100) : 0 }}%"></div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">{{ __("Eng ko'p ishlatgan 10 foydalanuvchi (oxirgi :days kun)", ['days' => $periodDays]) }}</h3>

                @if ($topUsers->isEmpty())
                    <p class="text-gray-500">{{ __("Hali ma'lumot yo'q.") }}</p>
                @else
                    <div class="adm-scroll">
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Foydalanuvchi') }}</th>
                                    <th>{{ __('Tarif') }}</th>
                                    <th class="adm-num">{{ __("So'rovlar") }}</th>
                                    <th class="adm-num">{{ __('Shundan rasm tahlili') }}</th>
                                    <th class="adm-num">{{ __('Xarajat') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topUsers as $row)
                                    <tr>
                                        <td>
                                            {{ $row->user?->name ?? '—' }}
                                            <div class="text-xs text-gray-500">#{{ $row->user_id }} · {{ $row->user?->email }}</div>
                                        </td>
                                        <td>{{ $row->user?->plan?->nomi ?? \App\Models\Plan::free()->nomi }}</td>
                                        <td class="adm-num">{{ $row->soni }}</td>
                                        <td class="adm-num">{{ $row->vision_soni }}</td>
                                        <td class="adm-num">
                                            {{ Money::usd((float) $row->narx) }}
                                            <div class="text-xs text-gray-500">{{ Money::som((float) $row->narx) }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-1">{{ __("Tariflar bo'yicha bitta foydalanuvchiga o'rtacha oylik AI xarajati") }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ __("Foydalanuvchilarning hozirgi tarifi bo'yicha hisoblanadi. Hammaga — xarajat tarifdagi barcha foydalanuvchilarga bo'lingan, AI ishlatganlarga — o'sha oyda kamida bitta so'rov yuborganlarga bo'lingan.") }}</p>

                @foreach (['current' => __('Shu oy'), 'previous' => __("O'tgan oy")] as $key => $label)
                    <h4 class="font-semibold mt-4 mb-2">{{ $label }}</h4>
                    <div class="adm-scroll">
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Tarif') }}</th>
                                    <th class="adm-num">{{ __('Foydalanuvchilar') }}</th>
                                    <th class="adm-num">{{ __('AI ishlatganlar') }}</th>
                                    <th class="adm-num">{{ __('Jami xarajat') }}</th>
                                    <th class="adm-num">{{ __("O'rtacha (hammaga)") }}</th>
                                    <th class="adm-num">{{ __("O'rtacha (AI ishlatganlarga)") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($planAverages[$key] as $row)
                                    <tr>
                                        <td>{{ $row['plan']->nomi }}</td>
                                        <td class="adm-num">{{ $row['users'] }}</td>
                                        <td class="adm-num">{{ $row['active'] }}</td>
                                        <td class="adm-num">{{ Money::usd($row['cost']) }}</td>
                                        <td class="adm-num">
                                            {{ Money::usd($row['avg_all']) }}
                                            <div class="text-xs text-gray-500">{{ Money::som($row['avg_all']) }}</div>
                                        </td>
                                        <td class="adm-num">
                                            {{ Money::usd($row['avg_active']) }}
                                            <div class="text-xs text-gray-500">{{ Money::som($row['avg_active']) }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
