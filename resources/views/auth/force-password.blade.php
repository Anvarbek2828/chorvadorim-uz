<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Siz ferma egasi bergan vaqtinchalik parol bilan kirdingiz. Davom etish uchun o\'zingizning yangi parolingizni o\'rnating.') }}
    </div>

    <form method="POST" action="{{ route('password.force.update') }}">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="current_password" :value="__('Vaqtinchalik parol')" />
            <x-text-input id="current_password" class="block mt-1 w-full" type="password" name="current_password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Yangi parol')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Yangi parolni takrorlang')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <button type="submit" form="force-password-logout" class="underline text-sm text-gray-600 hover:text-gray-900">
                {{ __('Chiqish') }}
            </button>

            <x-primary-button>
                {{ __('Parolni saqlash') }}
            </x-primary-button>
        </div>
    </form>

    <form id="force-password-logout" method="POST" action="{{ route('logout') }}">
        @csrf
    </form>
</x-guest-layout>
