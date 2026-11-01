@use('App\Services\AnimalImportService')
@use('Illuminate\Support\Str')

@php
    $notAdded = $summary['error'] + $summary['limit'];
    $statusBadges = [
        AnimalImportService::STATUS_OK => ['bg-green-100 text-green-700', $done ? __("Qo'shildi") : __("To'g'ri")],
        AnimalImportService::STATUS_ERROR => ['bg-red-100 text-red-700', __('Xato')],
        AnimalImportService::STATUS_LIMIT => ['bg-orange-50 text-orange-700', __('Limit')],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        @if ($adminMode)
            @include('admin.partials.header', ['title' => __('Hayvonlarni yuklash')])
        @else
            <div class="flex flex-wrap justify-between items-center gap-2">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $done ? __('Import natijasi') : __("Ko'rib chiqish") }}
                </h2>
                <a href="{{ route('animals.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">
                    &larr; {{ __('Mening hayvonlarim') }}
                </a>
            </div>
        @endif
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('animals.partials.import-common')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 sm:p-6">
                <div class="text-sm text-gray-500 mb-3">{{ __('Fayl') }}: {{ $fileName }}</div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <div class="text-sm text-gray-500">{{ __('Jami qatorlar') }}</div>
                        <div class="imp-stat">{{ $summary['total'] }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">{{ $done ? __("Qo'shildi") : __("To'g'ri") }}</div>
                        <div class="imp-stat text-green-700">{{ $done ? $added : $summary['ok'] }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">{{ __('Xato') }}</div>
                        <div class="imp-stat text-red-700">{{ $summary['error'] }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">{{ __('Tarif limitidan oshgan') }}</div>
                        <div class="imp-stat text-orange-700">{{ $summary['limit'] }}</div>
                    </div>
                </div>

                @unless ($done)
                    <p class="mt-4 text-sm text-gray-600">{{ __("Hali hech narsa saqlanmagan. \"Tasdiqlash\" bosilganda faqat to'g'ri qatorlar qo'shiladi.") }}</p>
                @endunless

                @if ($summary['limit'] > 0)
                    <div class="mt-4 bg-orange-50 text-orange-700 p-3 rounded text-sm">
                        {{ __("Tarif bo'yicha ko'pi bilan :max ta faol hayvon saqlash mumkin, hozir :current ta bor. Shuning uchun :count ta to'g'ri qator qo'shilmaydi.", ['max' => $summary['max_animals'], 'current' => $summary['current_animals'], 'count' => $summary['limit']]) }}
                        @if ($adminMode)
                            <a href="{{ route('admin.users.index', ['q' => $target->email]) }}" class="block mt-2 font-semibold underline">{{ __('Foydalanuvchi tarifini oshirish') }} &rarr;</a>
                        @else
                            {{ __("Ko'proq hayvon qo'shish uchun tarifingizni oshiring.") }}
                            @include('plans.partials.upgrade-link')
                        @endif
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    @if (! $done)
                        @if ($summary['ok'] > 0)
                            <form method="POST" action="{{ $urls['confirm'] }}" onsubmit="this.querySelector('button').disabled = true">
                                @csrf
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded text-sm">
                                    {{ __('Tasdiqlash: :count ta hayvonni qo\'shish', ['count' => $summary['ok']]) }}
                                </button>
                            </form>
                        @else
                            <span class="text-sm text-red-700">{{ __("Qo'shiladigan to'g'ri qator yo'q. Faylni tuzatib, qayta yuklang.") }}</span>
                        @endif
                    @endif

                    @if ($notAdded > 0)
                        <a href="{{ $urls['errors'] }}" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">
                            {{ __('Xato qatorlarni yuklab olish (CSV)') }}
                        </a>
                    @endif

                    @if (! $done)
                        <form method="POST" action="{{ $urls['cancel'] }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-600 hover:text-gray-900 text-sm">{{ __('Bekor qilish') }}</button>
                        </form>
                    @else
                        @if ($adminMode)
                            <a href="{{ route('admin.users.index', ['q' => $target->email]) }}" class="text-gray-600 hover:text-gray-900 text-sm">{{ __('Foydalanuvchilarga qaytish') }}</a>
                        @else
                            <a href="{{ route('animals.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">{{ __("Hayvonlarimga o'tish") }}</a>
                        @endif
                        <a href="{{ $urls['restart'] }}" class="text-gray-600 hover:text-gray-900 text-sm">{{ __('Yangi fayl yuklash') }}</a>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 sm:p-6">
                <div class="flex flex-wrap gap-3 mb-4 text-sm">
                    <a href="{{ $urls['preview'] }}" class="{{ $onlyErrors ? 'text-gray-600 hover:text-gray-900' : 'font-semibold text-gray-900' }}">
                        {{ __('Hammasi') }} ({{ $summary['total'] }})
                    </a>
                    <a href="{{ $urls['preview'] }}?show=errors" class="{{ $onlyErrors ? 'font-semibold text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                        {{ __("Faqat qo'shilmaydiganlar") }} ({{ $notAdded }})
                    </a>
                </div>

                @if ($rows === [])
                    <p class="text-gray-500">{{ __("Bunday qator yo'q.") }}</p>
                @else
                    <div class="imp-scroll">
                        <table class="imp-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Qator') }}</th>
                                    <th>{{ __('Holat') }}</th>
                                    @foreach ($labels as $label)
                                        <th>{{ __($label) }}</th>
                                    @endforeach
                                    <th>{{ __('Xato sababi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    @php
                                        $badFields = array_filter(array_column($row['errors'], 'field'));
                                    @endphp
                                    <tr>
                                        <td>{{ $row['line'] }}</td>
                                        <td>
                                            <span class="{{ $statusBadges[$row['status']][0] }} text-xs px-2 py-1 rounded">{{ $statusBadges[$row['status']][1] }}</span>
                                        </td>
                                        @foreach ($labels as $field => $label)
                                            <td class="{{ in_array($field, $badFields, true) ? 'imp-bad' : '' }}">{{ Str::limit($row['raw'][$field], $field === 'notes' ? 40 : 60) }}</td>
                                        @endforeach
                                        <td class="imp-wrap">
                                            @foreach ($row['messages'] as $message)
                                                <div>{{ $message }}</div>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
