@use('App\Support\Money')

<x-app-layout>
    <x-slot name="header">
        @include('admin.partials.header', ['title' => __('Foydalanuvchilar')])
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin.partials.flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-2 mb-4">
                    <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('Ism, email yoki Telegram ID') }}" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">{{ __('Qidirish') }}</button>
                    @if ($search !== '')
                        <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900 text-sm self-center">{{ __('Tozalash') }}</a>
                    @endif
                </form>

                <p class="text-xs text-gray-500 mb-3">{{ __("AI ishlatish va xarajat — shu oy bo'yicha.") }}</p>

                @if ($users->isEmpty())
                    <p class="text-gray-500">{{ __('Foydalanuvchi topilmadi.') }}</p>
                @else
                    <div class="adm-scroll">
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Foydalanuvchi') }}</th>
                                    <th>{{ __('Ferma') }}</th>
                                    <th>{{ __('Tarif') }}</th>
                                    <th class="adm-num">{{ __('Hayvonlar (ferma)') }}</th>
                                    <th class="adm-num">{{ __('AI chat') }}</th>
                                    <th class="adm-num">{{ __('Rasm tahlili') }}</th>
                                    <th class="adm-num">{{ __('Xarajat') }}</th>
                                    <th>{{ __('Bot') }}</th>
                                    <th>{{ __('Amallar') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    @php
                                        $expired = $user->isPlanExpired();
                                        // Asosiy a'zolik: o'z fermasi (ega), bo'lmasa birinchi a'zoligi (xodim)
                                        $membership = $user->farmMemberships->firstWhere('role', \App\Models\FarmUser::ROLE_OWNER) ?? $user->farmMemberships->first();
                                        $otherFarms = $user->farmMemberships->count() - ($membership ? 1 : 0);
                                        $ownedFarm = $user->farmMemberships->firstWhere('role', \App\Models\FarmUser::ROLE_OWNER)?->farm;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="font-semibold">
                                                {{ $user->name }}
                                                @if ($user->is_admin)
                                                    <span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded">{{ __('Admin') }}</span>
                                                @endif
                                                @if ($user->is_blocked)
                                                    <span class="bg-red-100 text-red-700 text-xs px-2 py-1 rounded">{{ __('Bloklangan') }}</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500">#{{ $user->id }} · {{ $user->email }}</div>
                                            <div class="text-xs text-gray-500">{{ __("Ro'yxatdan o'tgan") }}: {{ $user->created_at?->format('d.m.Y') }}</div>
                                        </td>
                                        <td>
                                            @if ($membership?->farm)
                                                <a href="{{ route('admin.farms.show', $membership->farm) }}" class="underline">{{ $membership->farm->name }}</a>
                                                <div class="text-xs text-gray-500">
                                                    @if ($membership->role === \App\Models\FarmUser::ROLE_OWNER)
                                                        {{ __('Ega') }}
                                                    @else
                                                        {{ __('Xodim') }} ({{ ['manager' => __('Menejer'), 'worker' => __('Ishchi'), 'viewer' => __("Kuzatuvchi (faqat ko'radi)")][$membership->role] ?? $membership->role }}){{ $membership->status !== \App\Models\FarmUser::STATUS_ACTIVE ? ', ' . __("to'xtatilgan") : '' }}
                                                    @endif
                                                    · {{ __("A'zolar") }}: {{ $membership->farm->members_count }}
                                                </div>
                                                @if ($otherFarms > 0)
                                                    <div class="text-xs text-gray-500">{{ __("Yana :count ta fermada a'zo", ['count' => $otherFarms]) }}</div>
                                                @endif
                                            @else
                                                <span class="text-gray-400">{{ __("Fermasi yo'q") }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $user->plan?->nomi ?? \App\Models\Plan::free()->nomi }}
                                            @if ($user->plan_tugash_sanasi)
                                                <div class="text-xs {{ $expired ? 'text-red-600' : 'text-gray-500' }}">
                                                    {{ $expired ? __('Muddati tugagan: :date', ['date' => $user->plan_tugash_sanasi->format('d.m.Y')]) : __(':date gacha', ['date' => $user->plan_tugash_sanasi->format('d.m.Y')]) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="adm-num">{{ $membership?->farm?->animals_count ?? 0 }}</td>
                                        <td class="adm-num">{{ $user->month_chat }}</td>
                                        <td class="adm-num">{{ $user->month_vision }}</td>
                                        <td class="adm-num">
                                            {{ Money::usd((float) $user->month_cost) }}
                                            <div class="text-xs text-gray-500">{{ Money::som((float) $user->month_cost) }}</div>
                                        </td>
                                        <td>
                                            @if ($user->telegram_chat_id)
                                                <span class="text-green-700">{{ __('Ulangan') }}</span>
                                            @else
                                                <span class="text-gray-400">{{ __("Yo'q") }}</span>
                                            @endif
                                        </td>
                                        <td class="adm-wrap">
                                            {{-- Tarif ferma sahifasidagi Obuna blokida o'zgartiriladi --}}
                                            @if ($ownedFarm)
                                                <a href="{{ route('admin.farms.show', $ownedFarm) }}" class="block text-sm underline">{{ __('Obuna') }} →</a>
                                            @endif

                                            <a href="{{ route('admin.users.import', $user) }}" class="block mt-2 text-sm underline">{{ __('Hayvonlarni yuklash') }}</a>

                                            @if ($user->id !== auth()->id() && (!$user->is_admin || $user->is_blocked))
                                                <form method="POST" action="{{ route('admin.users.block', $user) }}" class="mt-2"
                                                      onsubmit="return confirm(@js($user->is_blocked ? __('Foydalanuvchini blokdan chiqarasizmi?') : __('Foydalanuvchini bloklaysizmi? U tizimga kira olmaydi.')))">
                                                    @csrf
                                                    @method('PATCH')
                                                    @if ($user->is_blocked)
                                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-sm">{{ __('Blokdan chiqarish') }}</button>
                                                    @else
                                                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-sm">{{ __('Bloklash') }}</button>
                                                    @endif
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
