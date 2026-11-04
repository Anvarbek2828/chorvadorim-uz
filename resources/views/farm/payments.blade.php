@use('App\Models\FarmPayment')
@use('App\Services\SubscriptionService')

@php
    $methodLabels = ['bank' => __("Bank o'tkazmasi"), 'naqd' => __('Naqd'), 'boshqa' => __('Boshqa')];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __("To'lovlar tarixi") }}</h2>
    </x-slot>

    @include('farm.partials.styles')

    <div class="py-12 stf-page">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="text-sm text-gray-500">{{ __('Joriy tarif') }}</div>
                <div class="text-lg font-semibold">{{ $farm->activePlan()->nomi }}</div>
                <div class="text-sm text-gray-500 mt-1">
                    {{ __('Holat') }}: {{ SubscriptionService::statusLabel($status) }}
                    @if ($farm->plan_tugash_sanasi && $status !== SubscriptionService::STATUS_FREE)
                        · {{ __('Tugash sanasi') }}: {{ $farm->plan_tugash_sanasi->format('d.m.Y') }}
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($payments->isEmpty())
                    <p class="text-gray-500">{{ __("To'lovlar hozircha yo'q.") }}</p>
                @else
                    <div class="stf-scroll">
                        <table class="stf-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Sana') }}</th>
                                    <th>{{ __('Turi') }}</th>
                                    <th>{{ __('Tarif') }}</th>
                                    <th class="stf-num">{{ __('Oylar') }}</th>
                                    <th class="stf-num">{{ __("Summa (so'm)") }}</th>
                                    <th>{{ __("To'lov usuli") }}</th>
                                    <th>{{ __('Hujjat raqami') }}</th>
                                    <th>{{ __('Amal qilish muddati') }}</th>
                                    <th>{{ __('Holat') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->created_at?->timezone(config('ai.timezone'))->format('d.m.Y') }}</td>
                                        <td>{{ $payment->tur === FarmPayment::TYPE_BONUS ? __('Bonus') : __("To'lov") }}</td>
                                        <td>{{ $payment->plan?->nomi }}</td>
                                        <td class="stf-num">{{ $payment->oylar }}</td>
                                        <td class="stf-num">{{ number_format($payment->summa, 0, '.', ' ') }}</td>
                                        <td>{{ $payment->usul ? ($methodLabels[$payment->usul] ?? $payment->usul) : '—' }}</td>
                                        <td>{{ $payment->hujjat_raqami ?? '—' }}</td>
                                        <td>{{ __(':date gacha', ['date' => $payment->yangi_tugash->format('d.m.Y')]) }}</td>
                                        <td>
                                            @if ($payment->isCancelled())
                                                <span class="text-red-600">{{ __('Bekor qilingan') }}</span>
                                            @else
                                                <span class="text-green-700">{{ __('Amalda') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $payments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
