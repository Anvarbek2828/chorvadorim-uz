@use('App\Models\FarmAuditLog')
@use('App\Models\FarmUser')

@php
    $actionLabels = [
        FarmAuditLog::ACTION_ANIMAL_CREATED => __("Hayvon qo'shildi"),
        FarmAuditLog::ACTION_ANIMAL_UPDATED => __('Hayvon tahrirlandi'),
        FarmAuditLog::ACTION_ANIMAL_DELETED => __('Hayvon arxivga o\'tkazildi'),
        FarmAuditLog::ACTION_ANIMAL_RESTORED => __('Hayvon tiklandi'),
        FarmAuditLog::ACTION_ANIMAL_FORCE_DELETED => __('Hayvon butunlay o\'chirildi'),
        FarmAuditLog::ACTION_ANIMALS_IMPORTED => __('Hayvonlar fayldan yuklandi'),
        FarmAuditLog::ACTION_REMINDER_CREATED => __("Eslatma qo'shildi"),
        FarmAuditLog::ACTION_REMINDER_DELETED => __("Eslatma o'chirildi"),
        FarmAuditLog::ACTION_EXPENSE_CREATED => __("Xarajat qo'shildi"),
        FarmAuditLog::ACTION_EXPENSE_DELETED => __("Xarajat o'chirildi"),
        FarmAuditLog::ACTION_INCOME_CREATED => __("Daromad qo'shildi"),
        FarmAuditLog::ACTION_INCOME_DELETED => __("Daromad o'chirildi"),
        FarmAuditLog::ACTION_STAFF_ADDED => __("Xodim qo'shildi"),
        FarmAuditLog::ACTION_STAFF_ROLE_CHANGED => __("Xodim roli o'zgartirildi"),
        FarmAuditLog::ACTION_STAFF_STATUS_CHANGED => __("Xodim holati o'zgartirildi"),
        FarmAuditLog::ACTION_STAFF_AUTO_SUSPENDED => __("Xodim tarif limiti tufayli to'xtatildi"),
        FarmAuditLog::ACTION_STAFF_REMOVED => __('Xodim fermadan chiqarildi'),
        FarmAuditLog::ACTION_FARM_RENAMED => __("Ferma nomi o'zgartirildi"),
    ];

    $objectLabels = [
        'animal' => __('Hayvon'),
        'import' => __('Import'),
        'reminder' => __('Eslatma'),
        'expense' => __('Xarajat'),
        'income' => __('Daromad'),
        'staff' => __('Xodim'),
        'farm' => __('Ferma'),
    ];

    $fieldLabels = [
        'name' => __('Nomi'),
        'nomi' => __('Nomi'),
        'direction' => __("Yo'nalish"),
        'breed' => __('Zoti'),
        'birth_date' => __("Tug'ilgan sana"),
        'initial_weight' => __("Boshlang'ich vazn (kg)"),
        'current_weight' => __('Joriy vazni'),
        'gender' => __('Jinsi'),
        'notes' => __('Izoh'),
        'has_passport' => __('Pasport bor'),
        'passport_number' => __('Pasport raqami'),
        'chip_number' => __('Chip raqami'),
        'passport_issue_date' => __('Pasport berilgan sana'),
        'hayvon' => __('Hayvon'),
        'turi' => __('Turi'),
        'summa' => __("Summa (so'm)"),
        'sana' => __('Sana'),
        'eslatma' => __('Eslatma'),
        'ism' => __('Ism'),
        'email' => __('Email'),
        'rol' => __('Rol'),
        'holat' => __('Holat'),
        'yangi_akkaunt' => __('Yangi akkaunt'),
        'limit' => __('Tarif limitidan oshgan'),
        'fayl' => __('Fayl'),
        'qatorlar' => __('Jami qatorlar'),
        'qoshildi' => __("Qo'shildi"),
        'xato' => __('Xato'),
    ];

    $valueLabels = [
        'rol' => [
            FarmUser::ROLE_OWNER => __('Ega'),
            FarmUser::ROLE_MANAGER => __('Menejer'),
            FarmUser::ROLE_WORKER => __('Ishchi'),
            FarmUser::ROLE_VIEWER => __("Kuzatuvchi (faqat ko'radi)"),
        ],
        'holat' => [
            FarmUser::STATUS_ACTIVE => __('Faol'),
            FarmUser::STATUS_SUSPENDED => __("To'xtatilgan"),
        ],
        'direction' => ['sut' => __('Sut'), 'gosht' => __("Go'sht"), 'nasl' => __('Nasl')],
    ];

    $formatValue = function ($key, $value) use ($valueLabels) {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? __('ha') : __("yo'q");
        }

        if (is_scalar($value) && isset($valueLabels[$key][$value])) {
            return $valueLabels[$key][$value];
        }

        return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    };

    $formatChanges = function (?array $values, string $action) use ($fieldLabels, $formatValue) {
        return collect($values ?? [])
            // Tizim to'xtatganda 'limit' — tarifdagi xodimlar chegarasi
            ->map(fn ($value, $key) => ($key === 'limit' && str_starts_with($action, 'staff.') ? __('Xodimlar limiti') : ($fieldLabels[$key] ?? $key)) . ': ' . $formatValue($key, $value))
            ->implode("\n");
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Ferma jurnali') }}</h2>
    </x-slot>

    @include('farm.partials.styles')

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('farm-audit.index') }}" class="flex flex-wrap gap-2 mb-4">
                    <select name="user" class="stf-input" aria-label="{{ __('Xodim') }}" onchange="this.form.submit()">
                        <option value="">{{ __('Barcha xodimlar') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected($filters['user'] === $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <select name="amal" class="stf-input" aria-label="{{ __('Amal') }}" onchange="this.form.submit()">
                        <option value="">{{ __('Barcha amallar') }}</option>
                        @foreach ($actionLabels as $action => $label)
                            <option value="{{ $action }}" @selected($filters['amal'] === $action)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded text-sm">{{ __('Qidirish') }}</button></noscript>
                    @if ($filters['user'] !== null || $filters['amal'] !== '')
                        <a href="{{ route('farm-audit.index') }}" class="text-gray-600 hover:text-gray-900 text-sm self-center">{{ __('Tozalash') }}</a>
                    @endif
                </form>

                @if ($logs->isEmpty())
                    <p class="text-gray-500">{{ __("Jurnal hozircha bo'sh.") }}</p>
                @else
                    <div class="stf-scroll">
                        <table class="stf-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Vaqt') }}</th>
                                    <th>{{ __('Xodim') }}</th>
                                    <th>{{ __('Amal') }}</th>
                                    <th>{{ __('Obyekt') }}</th>
                                    <th>{{ __('Eski qiymat') }}</th>
                                    <th>{{ __('Yangi qiymat') }}</th>
                                    <th>{{ __('IP') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logs as $log)
                                    <tr>
                                        <td>{{ $log->created_at?->timezone(config('ai.timezone'))->format('d.m.Y H:i') }}</td>
                                        <td>
                                            @if ($log->user)
                                                {{ $log->user->name }}
                                            @elseif ($log->amal === FarmAuditLog::ACTION_STAFF_AUTO_SUSPENDED)
                                                <span class="text-gray-500">{{ __('Tizim') }}</span>
                                            @else
                                                <span class="text-gray-500">{{ __("O'chirilgan foydalanuvchi") }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $actionLabels[$log->amal] ?? $log->amal }}</td>
                                        <td>
                                            {{ $objectLabels[$log->obyekt_turi] ?? $log->obyekt_turi }}
                                            @if ($log->obyekt_turi === 'animal' && isset($animalNames[$log->obyekt_id]))
                                                : {{ $animalNames[$log->obyekt_id] }}
                                            @endif
                                            @if ($log->obyekt_id)
                                                <span class="text-xs text-gray-500">#{{ $log->obyekt_id }}</span>
                                            @endif
                                        </td>
                                        <td class="stf-wrap"><div class="stf-pre">{{ $formatChanges($log->eski_qiymat, $log->amal) }}</div></td>
                                        <td class="stf-wrap"><div class="stf-pre">{{ $formatChanges($log->yangi_qiymat, $log->amal) }}</div></td>
                                        <td>{{ $log->ip ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
