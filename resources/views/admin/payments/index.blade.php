@use('App\Models\FarmPayment')
@use('App\Services\SubscriptionService')

@php
    $filterLabels = [
        'hammasi' => __('Hammasi'),
        'yaqin' => __('Muddati yaqin (7 kun ichida)'),
        SubscriptionService::STATUS_GRACE => __('Imtiyozda'),
        SubscriptionService::STATUS_READ_ONLY => __("Faqat o'qish"),
        SubscriptionService::STATUS_UNLIMITED => __('Muddatsiz'),
    ];
    $methodLabels = ['bank' => __("Bank o'tkazmasi"), 'naqd' => __('Naqd'), 'boshqa' => __('Boshqa')];
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin.partials.header', ['title' => __("To'lovlar")])
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin.partials.flash')

            <nav class="adm-tabs">
                @foreach ($filterLabels as $key => $label)
                    <a href="{{ route('admin.payments.index', $key === 'hammasi' ? [] : ['filtr' => $key]) }}"
                       class="adm-tab {{ $filter === $key ? 'adm-tab-active' : '' }}">{{ $label }}</a>
                @endforeach
            </nav>

            @if ($farms !== null)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">{{ __('Fermalar') }}: {{ $filterLabels[$filter] }} ({{ $farms->count() }})</h3>
                    @if ($farms->isEmpty())
                        <p class="text-gray-500">{{ __('Ferma topilmadi.') }}</p>
                    @else
                        <div class="adm-scroll">
                            <table class="adm-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Ferma') }}</th>
                                        <th>{{ __('Ega') }}</th>
                                        <th>{{ __('Tarif') }}</th>
                                        <th>{{ __('Tugash sanasi') }}</th>
                                        <th>{{ __('Holat') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($farms as $farm)
                                        @php($farmStatus = $subscriptions->status($farm))
                                        <tr>
                                            <td><a href="{{ route('admin.farms.show', $farm) }}" class="underline">{{ $farm->name }}</a></td>
                                            <td>{{ $farm->owner?->name }} <div class="text-xs text-gray-500">{{ $farm->owner?->email }}</div></td>
                                            <td>{{ $farm->plan?->nomi }}</td>
                                            <td>{{ $farm->plan_tugash_sanasi?->format('d.m.Y') ?? '—' }}</td>
                                            <td><span class="adm-badge adm-badge-{{ $farmStatus }}">{{ SubscriptionService::statusLabel($farmStatus) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">{{ __("To'lovlar") }}</h3>
                @if ($payments->isEmpty())
                    <p class="text-gray-500">{{ __("To'lovlar hozircha yo'q.") }}</p>
                @else
                    <div class="adm-scroll">
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Sana') }}</th>
                                    <th>{{ __('Ferma') }}</th>
                                    <th>{{ __('Turi') }}</th>
                                    <th>{{ __('Tarif') }}</th>
                                    <th class="adm-num">{{ __('Oylar') }}</th>
                                    <th class="adm-num">{{ __("Summa (so'm)") }}</th>
                                    <th>{{ __("To'lov usuli") }}</th>
                                    <th>{{ __('Admin') }}</th>
                                    <th>{{ __('Holat') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->created_at?->timezone(config('ai.timezone'))->format('d.m.Y H:i') }}</td>
                                        <td>
                                            @if ($payment->farm)
                                                <a href="{{ route('admin.farms.show', $payment->farm) }}" class="underline">{{ $payment->farm->name }}</a>
                                            @else
                                                {{ $payment->farm_nomi }} <span class="text-xs text-gray-500">({{ __("o'chirilgan") }})</span>
                                            @endif
                                        </td>
                                        <td>{{ $payment->tur === FarmPayment::TYPE_BONUS ? __('Bonus') : __("To'lov") }}</td>
                                        <td>{{ $payment->plan?->nomi }}</td>
                                        <td class="adm-num">{{ $payment->oylar }}</td>
                                        <td class="adm-num">{{ number_format($payment->summa, 0, '.', ' ') }}</td>
                                        <td>{{ $payment->usul ? ($methodLabels[$payment->usul] ?? $payment->usul) : '—' }}</td>
                                        <td>{{ $payment->admin?->name ?? '—' }}</td>
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
