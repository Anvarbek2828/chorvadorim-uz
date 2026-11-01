@php
    // Tanlash mumkin bo'lgan modellar — config('ai.pricing') dagi narxi bor modellar
    $pricedModels = array_keys(config('ai.pricing', []));
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin.partials.header', ['title' => __('Tariflar')])
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin.partials.flash')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">
                    {{ __("Narx bo'sh qoldirilsa, foydalanuvchiga «Narx bo'yicha murojaat qiling» deb ko'rsatiladi. Nofaol tarif foydalanuvchilar sahifasida ko'rinmaydi, lekin unga biriktirilgan foydalanuvchilar limitlari saqlanadi. Kunlik AI limiti — oylik limitning 1/10 qismi (kamida 3).") }}
                </p>

                <div class="adm-scroll">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>{{ __('Kod') }}</th>
                                <th>{{ __('Nomi') }}</th>
                                <th>{{ __("Narx (so'm / oy)") }}</th>
                                <th>{{ __('Maks. hayvon') }}</th>
                                <th>{{ __('Oylik chat limiti') }}</th>
                                <th>{{ __('Oylik rasm limiti') }}</th>
                                <th>{{ __('Chat modeli') }}</th>
                                <th>{{ __('Rasm modeli') }}</th>
                                <th>{{ __('Faol') }}</th>
                                <th class="adm-num">{{ __('Foydalanuvchilar') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plans as $plan)
                                @php
                                    $formId = 'plan-form-' . $plan->id;
                                @endphp
                                <tr>
                                    <td>
                                        <code>{{ $plan->kod }}</code>
                                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.plans.update', $plan) }}">
                                            @csrf
                                            @method('PATCH')
                                        </form>
                                    </td>
                                    <td><input form="{{ $formId }}" type="text" name="nomi" value="{{ $plan->nomi }}" maxlength="100" required class="adm-input" style="width: 8rem;"></td>
                                    <td><input form="{{ $formId }}" type="number" name="narx_som" value="{{ $plan->narx_som }}" min="0" step="1" placeholder="—" class="adm-input" style="width: 9rem;"></td>
                                    <td><input form="{{ $formId }}" type="number" name="max_hayvon" value="{{ $plan->max_hayvon }}" min="0" step="1" required class="adm-input" style="width: 6rem;"></td>
                                    <td><input form="{{ $formId }}" type="number" name="oylik_chat_limit" value="{{ $plan->oylik_chat_limit }}" min="0" step="1" required class="adm-input" style="width: 6rem;"></td>
                                    <td><input form="{{ $formId }}" type="number" name="oylik_vision_limit" value="{{ $plan->oylik_vision_limit }}" min="0" step="1" required class="adm-input" style="width: 6rem;"></td>
                                    @foreach (['chat_model' => \App\Models\AiUsageLog::TYPE_CHAT, 'vision_model' => \App\Models\AiUsageLog::TYPE_VISION] as $field => $type)
                                        <td>
                                            <select form="{{ $formId }}" name="{{ $field }}" required class="adm-input" style="width: 10rem;">
                                                @if ($plan->{$field} && !in_array($plan->{$field}, $pricedModels, true))
                                                    <option value="{{ $plan->{$field} }}" selected>{{ $plan->{$field} }} ({{ __("narxi yo'q") }})</option>
                                                @endif
                                                @foreach ($pricedModels as $model)
                                                    <option value="{{ $model }}" @selected($plan->aiModel($type) === $model)>{{ $model }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endforeach
                                    <td><input form="{{ $formId }}" type="checkbox" name="faol" value="1" @checked($plan->faol)></td>
                                    <td class="adm-num">{{ $plan->users_count }}</td>
                                    <td>
                                        <button form="{{ $formId }}" type="submit" class="bg-gray-800 text-white px-3 py-1 rounded hover:bg-gray-700 text-sm">{{ __('Saqlash') }}</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
