@use('App\Models\AdminAuditLog')

@php
    $actionLabels = [
        AdminAuditLog::ACTION_USER_PLAN_CHANGED => __("Tarif o'zgartirildi"),
        AdminAuditLog::ACTION_USER_BLOCKED => __('Bloklandi'),
        AdminAuditLog::ACTION_USER_UNBLOCKED => __('Blokdan chiqarildi'),
        AdminAuditLog::ACTION_USER_MADE_ADMIN => __('Admin qilindi'),
        AdminAuditLog::ACTION_PLAN_UPDATED => __('Tarif tahrirlandi'),
    ];

    $fieldLabels = [
        'plan' => __('Tarif'),
        'plan_tugash_sanasi' => __('Tugash sanasi'),
        'is_blocked' => __('Bloklangan'),
        'is_admin' => __('Admin'),
        'nomi' => __('Nomi'),
        'narx_som' => __("Narx (so'm / oy)"),
        'max_hayvon' => __('Maks. hayvon'),
        'oylik_chat_limit' => __('Oylik chat limiti'),
        'oylik_vision_limit' => __('Oylik rasm limiti'),
        'chat_model' => __('Chat modeli'),
        'vision_model' => __('Rasm modeli'),
        'faol' => __('Faol'),
    ];

    $formatValue = function ($value) {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? __('ha') : __("yo'q");
        }

        return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    };

    $formatChanges = function (?array $values) use ($fieldLabels, $formatValue) {
        return collect($values ?? [])
            ->map(fn ($value, $key) => ($fieldLabels[$key] ?? $key) . ': ' . $formatValue($value))
            ->implode("\n");
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin.partials.header', ['title' => __('Audit jurnali')])
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($logs->isEmpty())
                    <p class="text-gray-500">{{ __("Jurnal hozircha bo'sh.") }}</p>
                @else
                    <div class="adm-scroll">
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Vaqt') }}</th>
                                    <th>{{ __('Admin') }}</th>
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
                                            @if ($log->admin)
                                                {{ $log->admin->name }}
                                                <div class="text-xs text-gray-500">{{ $log->admin->email }}</div>
                                            @elseif ($log->admin_id === null)
                                                <span class="text-gray-500">{{ __("Konsol yoki o'chirilgan admin") }}</span>
                                            @else
                                                #{{ $log->admin_id }}
                                            @endif
                                        </td>
                                        <td>{{ $actionLabels[$log->amal] ?? $log->amal }}</td>
                                        <td>
                                            @if ($log->obyekt_turi === 'user')
                                                {{ __('Foydalanuvchi') }}: {{ $userNames[$log->obyekt_id] ?? '' }} #{{ $log->obyekt_id }}
                                            @elseif ($log->obyekt_turi === 'plan')
                                                {{ __('Tarif') }}: {{ $planNames[$log->obyekt_id] ?? '' }} #{{ $log->obyekt_id }}
                                            @else
                                                {{ $log->obyekt_turi }} #{{ $log->obyekt_id }}
                                            @endif
                                        </td>
                                        <td class="adm-wrap"><div class="adm-pre">{{ $formatChanges($log->eski_qiymat) }}</div></td>
                                        <td class="adm-wrap"><div class="adm-pre">{{ $formatChanges($log->yangi_qiymat) }}</div></td>
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
