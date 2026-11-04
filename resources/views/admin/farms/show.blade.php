@use('App\Models\FarmPayment')
@use('App\Models\FarmUser')
@use('App\Services\SubscriptionService')

@php
    $methodLabels = \App\Models\FarmPayment::methodLabels();
    $roleLabels = [
        FarmUser::ROLE_OWNER => __('Ega'),
        FarmUser::ROLE_MANAGER => __('Menejer'),
        FarmUser::ROLE_WORKER => __('Ishchi'),
        FarmUser::ROLE_VIEWER => __("Kuzatuvchi (faqat ko'radi)"),
    ];
    // To'lov formasida standart tarif: joriy pullik tarif, bo'lmasa birinchisi
    $defaultPlan = $paidPlans->firstWhere('id', $farm->plan_id) ?? $paidPlans->first();
    $bonusNeedsPlan = in_array($status, [SubscriptionService::STATUS_FREE, SubscriptionService::STATUS_READ_ONLY], true);
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin.partials.header', ['title' => __('Ferma') . ': ' . $farm->name])
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin.partials.flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="text-lg font-semibold">{{ $farm->name }} <span class="text-sm text-gray-500">#{{ $farm->id }}</span></div>
                <div class="text-sm text-gray-500 mt-1">
                    {{ __('Ega') }}: {{ $farm->owner?->name }} · {{ $farm->owner?->email }}
                    · {{ __('Yaratilgan') }}: {{ $farm->created_at?->timezone(config('ai.timezone'))->format('d.m.Y') }}
                </div>
            </div>

            {{-- Obuna bloki --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">{{ __('Obuna') }}</h3>

                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <div class="text-sm text-gray-500">{{ __('Tarif') }}</div>
                        <div class="font-semibold">{{ $farm->activePlan()->nomi }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">{{ __('Tugash sanasi') }}</div>
                        <div class="font-semibold">{{ $farm->plan_tugash_sanasi?->format('d.m.Y') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">{{ __('Holat') }}</div>
                        <span class="adm-badge adm-badge-{{ $status }}">{{ SubscriptionService::statusLabel($status) }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <form method="POST" action="{{ route('admin.farms.payment', $farm) }}" class="space-y-2 border rounded-lg p-4" id="obn-payment-form">
                        @csrf
                        <div class="font-semibold">{{ __("To'landi") }}</div>
                        <div>
                            <label class="block text-xs text-gray-500">{{ __('Tarif') }}</label>
                            <select name="plan_id" class="adm-input" data-obn-plan required>
                                @foreach ($paidPlans as $plan)
                                    <option value="{{ $plan->id }}" data-price="{{ $plan->narx_som }}" @selected((int) old('plan_id', $defaultPlan?->id) === (int) $plan->id)>
                                        {{ $plan->nomi }}@if ($plan->narx_som !== null) — {{ number_format($plan->narx_som, 0, '.', ' ') }} {{ __("so'm") }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">{{ __('Oylar') }}</label>
                            <select name="oylar" class="adm-input" data-obn-months>
                                @foreach (SubscriptionService::MONTH_OPTIONS as $months)
                                    <option value="{{ $months }}" @selected((int) old('oylar', 1) === $months)>{{ $months }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">{{ __("Summa (so'm)") }}</label>
                            <input type="number" name="summa" min="0" step="1" required class="adm-input" data-obn-sum
                                   value="{{ old('summa', $defaultPlan?->narx_som) }}">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">{{ __("To'lov usuli") }}</label>
                            <select name="usul" class="adm-input" required>
                                @foreach ($methodLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('usul', 'bank') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">{{ __('Hujjat raqami (ixtiyoriy)') }}</label>
                            <input type="text" name="hujjat_raqami" maxlength="100" class="adm-input" value="{{ old('hujjat_raqami') }}">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">{{ __('Izoh') }}</label>
                            <textarea name="izoh" rows="2" class="adm-input w-full">{{ old('izoh') }}</textarea>
                        </div>
                        <button type="submit" class="bg-gray-800 text-white px-3 py-1 rounded hover:bg-gray-700 text-sm"
                                onclick="return confirm(@js(__("To'lovni saqlaysizmi? Tarif darhol o'rnatiladi.")))">{{ __("To'landi") }}</button>
                    </form>

                    <form method="POST" action="{{ route('admin.farms.bonus', $farm) }}" class="space-y-2 border rounded-lg p-4">
                        @csrf
                        <div class="font-semibold">{{ __('+1 oy bonus') }}</div>
                        @if ($status === SubscriptionService::STATUS_UNLIMITED)
                            <p class="text-sm text-gray-500">{{ __("Muddatsiz tarifdagi fermaga bonus berilmaydi.") }}</p>
                        @else
                            @if ($bonusNeedsPlan)
                                <div>
                                    <label class="block text-xs text-gray-500">{{ __('Tarif') }}</label>
                                    <select name="plan_id" class="adm-input" required>
                                        <option value="">—</option>
                                        @foreach ($paidPlans as $plan)
                                            <option value="{{ $plan->id }}">{{ $plan->nomi }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <p class="text-sm text-gray-500">{{ __('Joriy tarif') }}: {{ $farm->activePlan()->nomi }}</p>
                            @endif
                            <div>
                                <label class="block text-xs text-gray-500">{{ __('Sabab') }}</label>
                                <textarea name="sabab" rows="2" required class="adm-input w-full"></textarea>
                            </div>
                            <button type="submit" class="bg-gray-800 text-white px-3 py-1 rounded hover:bg-gray-700 text-sm">{{ __('+1 oy bonus') }}</button>
                        @endif
                    </form>

                    <form method="POST" action="{{ route('admin.farms.plan', $farm) }}" class="space-y-2 border rounded-lg p-4" id="obn-change-plan-form"
                          onsubmit="return confirm(@js(__("Bu amal to'lov yozuvisiz tarifni o'zgartiradi. Davom etasizmi?")))">
                        @csrf
                        <div class="font-semibold">{{ __("Tarifni almashtirish (to'lovsiz)") }}</div>
                        <div>
                            <label class="block text-xs text-gray-500">{{ __('Tarif') }}</label>
                            <select name="plan_id" class="adm-input" data-obn-change-plan required>
                                @foreach ($allPlans as $plan)
                                    <option value="{{ $plan->id }}" data-free="{{ $plan->kod === \App\Models\Plan::FREE ? 1 : 0 }}"
                                            @selected((int) old('plan_id', $farm->activePlan()->id) === (int) $plan->id)>{{ $plan->nomi }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">{{ __("Tugash sanasi (bo'sh — muddatsiz)") }}</label>
                            <input type="date" name="plan_tugash_sanasi" class="adm-input" data-obn-change-date
                                   value="{{ old('plan_tugash_sanasi', $farm->plan_tugash_sanasi?->toDateString()) }}">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">{{ __('Sabab') }}</label>
                            <textarea name="sabab" rows="2" required class="adm-input w-full"></textarea>
                        </div>
                        <p class="text-xs text-gray-500">{{ __("Yangi tarifda xodimlar limiti kamroq bo'lsa, ortiqcha xodimlar (eng oxirgi qo'shilganlar) to'xtatiladi.") }}</p>
                        <button type="submit" class="bg-gray-800 text-white px-3 py-1 rounded hover:bg-gray-700 text-sm">{{ __('Saqlash') }}</button>
                    </form>
                </div>
            </div>

            {{-- To'lovlar tarixi --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">{{ __("To'lovlar tarixi") }}</h3>
                @if ($payments->isEmpty())
                    <p class="text-gray-500">{{ __("To'lovlar hozircha yo'q.") }}</p>
                @else
                    <div class="adm-scroll">
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Sana') }}</th>
                                    <th>{{ __('Turi') }}</th>
                                    <th>{{ __('Tarif') }}</th>
                                    <th class="adm-num">{{ __('Oylar') }}</th>
                                    <th class="adm-num">{{ __("Summa (so'm)") }}</th>
                                    <th>{{ __("To'lov usuli") }}</th>
                                    <th>{{ __('Muddat') }}</th>
                                    <th>{{ __('Admin') }}</th>
                                    <th>{{ __('Izoh') }}</th>
                                    <th>{{ __('Holat') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->created_at?->timezone(config('ai.timezone'))->format('d.m.Y H:i') }}</td>
                                        <td>{{ $payment->tur === FarmPayment::TYPE_BONUS ? __('Bonus') : __("To'lov") }}</td>
                                        <td>{{ $payment->plan?->nomi }}</td>
                                        <td class="adm-num">{{ $payment->oylar }}</td>
                                        <td class="adm-num">{{ number_format($payment->summa, 0, '.', ' ') }}</td>
                                        <td>
                                            {{ $payment->usul ? ($methodLabels[$payment->usul] ?? $payment->usul) : '—' }}
                                            @if ($payment->hujjat_raqami)
                                                <div class="text-xs text-gray-500">{{ $payment->hujjat_raqami }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $payment->avvalgi_tugash?->format('d.m.Y') ?? '—' }} → {{ $payment->yangi_tugash->format('d.m.Y') }}
                                        </td>
                                        <td>{{ $payment->admin?->name ?? '—' }}</td>
                                        <td class="adm-wrap">{{ $payment->izoh }}</td>
                                        <td class="adm-wrap">
                                            @if ($payment->isCancelled())
                                                <span class="text-red-600">{{ __('Bekor qilingan') }}</span>
                                                <div class="text-xs text-gray-500">
                                                    {{ $payment->bekor_qilingan_at->timezone(config('ai.timezone'))->format('d.m.Y H:i') }}, {{ $payment->cancelledBy?->name }}
                                                </div>
                                                <div class="text-xs text-gray-500">{{ $payment->bekor_sababi }}</div>
                                            @else
                                                <span class="text-green-700">{{ __('Amalda') }}</span>
                                                @if ($payment->id === $cancellableId)
                                                    <form method="POST" action="{{ route('admin.payments.cancel', $payment) }}" class="mt-2 space-y-1"
                                                          onsubmit="return confirm(@js(__("To'lovni bekor qilasizmi? Tarif va tugash sanasi avvalgi holatga qaytadi.")))">
                                                        @csrf
                                                        <input type="text" name="sabab" required placeholder="{{ __('Sabab') }}" class="adm-input">
                                                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-sm">{{ __('Bekor qilish') }}</button>
                                                    </form>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- A'zolar (faqat ko'rish) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">{{ __("A'zolar") }}</h3>
                <div class="adm-scroll">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>{{ __('Ism') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Rol') }}</th>
                                <th>{{ __('Holat') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $member)
                                <tr>
                                    <td>{{ $member->user?->name }}</td>
                                    <td>{{ $member->user?->email }}</td>
                                    <td>{{ $roleLabels[$member->role] ?? $member->role }}</td>
                                    <td>{{ $member->status === FarmUser::STATUS_ACTIVE ? __('Faol') : __("To'xtatilgan") }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Summa standart qiymati: tarif narxi x oylar (admin qo'lda o'zgartirishi mumkin)
        (function () {
            var form = document.getElementById('obn-payment-form');
            if (!form) return;
            var plan = form.querySelector('[data-obn-plan]');
            var months = form.querySelector('[data-obn-months]');
            var sum = form.querySelector('[data-obn-sum]');
            function update() {
                var price = plan.options[plan.selectedIndex].getAttribute('data-price');
                if (price !== null && price !== '') {
                    sum.value = parseInt(price, 10) * parseInt(months.value, 10);
                }
            }
            plan.addEventListener('change', update);
            months.addEventListener('change', update);
        })();

        // To'lovsiz almashtirish: Free tanlansa tugash sanasi e'tiborga olinmaydi
        (function () {
            var form = document.getElementById('obn-change-plan-form');
            if (!form) return;
            var plan = form.querySelector('[data-obn-change-plan]');
            var date = form.querySelector('[data-obn-change-date]');
            function update() {
                date.disabled = plan.options[plan.selectedIndex].getAttribute('data-free') === '1';
            }
            plan.addEventListener('change', update);
            update();
        })();
    </script>
</x-app-layout>
