@use('App\Models\AiUsageLog')
@use('App\Models\FarmUser')

@php
    $roleLabels = [
        FarmUser::ROLE_OWNER => __('Ega'),
        FarmUser::ROLE_MANAGER => __('Menejer'),
        FarmUser::ROLE_WORKER => __('Ishchi'),
        FarmUser::ROLE_VIEWER => __("Kuzatuvchi (faqat ko'radi)"),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Xodimlar') }} — {{ $farm->name }}</h2>
    </x-slot>

    @include('farm.partials.styles')

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (!empty($credentials))
                <div class="stf-info" id="stf-credentials-block">
                    <div class="flex justify-between items-start gap-2">
                        <p class="text-sm font-semibold">{{ __('Bu parol faqat hozir ko\'rsatiladi. Uni xodimga bering.') }}</p>
                        <button type="button" class="stf-close" aria-label="{{ __('Yopish') }}" title="{{ __('Yopish') }}"
                                onclick="document.getElementById('stf-credentials-block').remove()">&times;</button>
                    </div>
<pre id="stf-credentials" class="stf-code">{{ __('Ism') }}: {{ $credentials['name'] ?? '' }}
{{ __('Login') }}: {{ $credentials['email'] ?? '' }}
{{ __('Parol') }}: <span id="stf-password">{{ $credentials['password'] ?? '' }}</span></pre>
                    <button type="button" class="bg-gray-800 text-white px-3 py-1 rounded hover:bg-gray-700 text-sm mt-2"
                            data-copied="{{ __('Nusxa olindi') }}"
                            onclick="navigator.clipboard.writeText(document.getElementById('stf-credentials').textContent.trim()).then(() => { this.textContent = this.dataset.copied; })">
                        {{ __('Nusxa olish') }}
                    </button>
                </div>
            @endif

            @if (session('success'))
                <div class="bg-green-100 text-green-700 p-3 rounded">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-3 rounded">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                    @if ($errors->has('staff') && !$hasCapacity)
                        @include('plans.partials.upgrade-link')
                    @endif
                </div>
            @endif

            @if ($suspendedStaff > 0 && !$hasCapacity)
                <div class="stf-warning">
                    {{ __(':count ta xodim to\'xtatilgan: tarifingizdagi xodimlar limiti to\'lgan. Ularni faollashtirish uchun yuqoriroq tarifga o\'ting yoki boshqa xodimni to\'xtating.', ['count' => $suspendedStaff]) }}
                    @include('plans.partials.upgrade-link')
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
                    <h3 class="font-semibold text-lg">{{ __('Ferma a\'zolari') }}</h3>
                    <div class="text-sm text-gray-500">
                        {{ __('Faol xodimlar') }}: {{ $activeStaff }} / {{ $staffLimit === null ? __('cheksiz') : $staffLimit }}
                        <span class="text-xs">({{ __('ega hisobga kirmaydi') }})</span>
                    </div>
                </div>

                <div class="stf-scroll">
                    <table class="stf-table">
                        <thead>
                            <tr>
                                <th>{{ __('Xodim') }}</th>
                                <th>{{ __('Rol') }}</th>
                                <th>{{ __('Holat') }}</th>
                                <th>{{ __('Qo\'shilgan sana') }}</th>
                                <th class="stf-num">{{ __('AI chat (shu oy)') }}</th>
                                <th class="stf-num">{{ __('Rasm tahlili (shu oy)') }}</th>
                                <th>{{ __('Telegram') }}</th>
                                <th>{{ __('Amallar') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $member)
                                @php
                                    $isOwner = $member->role === FarmUser::ROLE_OWNER;
                                    $isActive = $member->status === FarmUser::STATUS_ACTIVE;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $member->user?->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $member->user?->email }}</div>
                                        @if ($member->must_change_password)
                                            <div class="text-xs text-orange-700">{{ __('Vaqtinchalik parolni hali almashtirmagan') }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $roleLabels[$member->role] ?? $member->role }}</td>
                                    <td>
                                        @if ($isActive)
                                            <span class="stf-badge bg-green-100 text-green-700">{{ __('Faol') }}</span>
                                        @else
                                            <span class="stf-badge bg-red-100 text-red-700">{{ __('To\'xtatilgan') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $member->created_at?->timezone(config('ai.timezone'))->format('d.m.Y') }}</td>
                                    <td class="stf-num">{{ $aiUsage[$member->user_id][AiUsageLog::TYPE_CHAT] ?? 0 }}</td>
                                    <td class="stf-num">{{ $aiUsage[$member->user_id][AiUsageLog::TYPE_VISION] ?? 0 }}</td>
                                    <td>
                                        @if ($member->user?->telegram_chat_id)
                                            <span class="text-green-700">{{ __('Ulangan') }}</span>
                                        @else
                                            <span class="text-gray-400">{{ __("Yo'q") }}</span>
                                        @endif
                                    </td>
                                    <td class="stf-wrap">
                                        @if ($isOwner)
                                            <span class="text-gray-400 text-sm">—</span>
                                        @else
                                            <form method="POST" action="{{ route('staff.role', $member->id) }}" class="flex flex-wrap items-center gap-1">
                                                @csrf
                                                @method('PATCH')
                                                <select name="role" class="stf-input" aria-label="{{ __('Rol') }}">
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role }}" @selected($member->role === $role)>{{ $roleLabels[$role] }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="bg-gray-800 text-white px-3 py-1 rounded hover:bg-gray-700 text-sm">{{ __('Saqlash') }}</button>
                                            </form>

                                            <div class="flex flex-wrap gap-1 mt-2">
                                                <form method="POST" action="{{ route('staff.status', $member->id) }}"
                                                      onsubmit="return confirm(@js($isActive ? __('Xodimni to\'xtatasizmi? U bu fermaga kira olmaydi.') : __('Xodimni faollashtirasizmi?')))">
                                                    @csrf
                                                    @method('PATCH')
                                                    @if ($isActive)
                                                        <button type="submit" class="border border-gray-300 text-gray-700 px-3 py-1 rounded hover:bg-gray-50 text-sm">{{ __('To\'xtatish') }}</button>
                                                    @else
                                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm">{{ __('Faollashtirish') }}</button>
                                                    @endif
                                                </form>

                                                <form method="POST" action="{{ route('staff.destroy', $member->id) }}"
                                                      onsubmit="return confirm(@js(__('Xodimni fermadan chiqarasizmi? Uning akkaunti o\'chirilmaydi, u qo\'shgan ma\'lumotlar fermada qoladi.')))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm">{{ __('Fermadan chiqarish') }}</button>
                                                </form>

                                                @if ($member->canResetTemporaryPassword($farm))
                                                    <form method="POST" action="{{ route('staff.password', $member->id) }}"
                                                          onsubmit="return confirm(@js(__('Xodimga yangi vaqtinchalik parol yaratilsinmi? Eski vaqtinchalik parol ishlamay qoladi.')))">
                                                        @csrf
                                                        <button type="submit" class="border border-gray-300 text-gray-700 px-3 py-1 rounded hover:bg-gray-50 text-sm">{{ __('Yangi vaqtinchalik parol') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-2">{{ __('Xodim qo\'shish') }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ __('Email tizimda bo\'lmasa, yangi akkaunt va vaqtinchalik parol yaratiladi. Email allaqachon ro\'yxatdan o\'tgan bo\'lsa, o\'sha akkaunt fermangizga a\'zo qilinadi va paroli o\'zgarmaydi.') }}</p>

                @if ($hasCapacity)
                    <form method="POST" action="{{ route('staff.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        @csrf
                        <div>
                            <label class="block text-sm text-gray-700" for="staff-name">{{ __('Ism') }}</label>
                            <input id="staff-name" type="text" name="name" value="{{ old('name') }}" maxlength="255" class="mt-1 block w-full rounded-md border-gray-300" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700" for="staff-email">{{ __('Email') }}</label>
                            <input id="staff-email" type="email" name="email" value="{{ old('email') }}" maxlength="255" class="mt-1 block w-full rounded-md border-gray-300" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700" for="staff-role">{{ __('Rol') }}</label>
                            <select id="staff-role" name="role" class="mt-1 block w-full rounded-md border-gray-300">
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" @selected(old('role', FarmUser::ROLE_WORKER) === $role)>{{ $roleLabels[$role] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex" style="align-items: flex-end;">
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm w-full">{{ __('Qo\'shish') }}</button>
                        </div>
                    </form>
                @else
                    <div class="stf-warning">
                        {{ __('Tarifingiz bo\'yicha ko\'pi bilan :limit ta xodim qo\'shish mumkin. Ko\'proq xodim kerak bo\'lsa, yuqoriroq tarifga o\'ting.', ['limit' => (int) $staffLimit]) }}
                        @include('plans.partials.upgrade-link')
                    </div>
                @endif

                <div class="mt-4 border-t pt-4 text-sm text-gray-500 space-y-1">
                    <div><span class="font-semibold">{{ __('Menejer') }}</span> — {{ __('hayvonlar, eslatmalar, import va o\'chirish; tariflar va ferma jurnalini ko\'radi.') }}</div>
                    <div><span class="font-semibold">{{ __('Ishchi') }}</span> — {{ __('hayvon qo\'shadi va tahrirlaydi, xarajat/daromad va eslatma qo\'shadi, AI dan foydalanadi; hayvon va eslatmani o\'chira olmaydi.') }}</div>
                    <div><span class="font-semibold">{{ __("Kuzatuvchi (faqat ko'radi)") }}</span> — {{ __('faqat ko\'radi, hech narsani o\'zgartira olmaydi.') }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
