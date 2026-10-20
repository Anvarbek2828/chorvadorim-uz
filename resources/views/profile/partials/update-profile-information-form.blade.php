<section>

    <header>

        <h2 class="text-lg font-medium text-gray-900">

            {{ __('Profile Information') }}

        </h2>

        <p class="mt-1 text-sm text-gray-600">

            {{ __("Update your account's profile information and email address.") }}

        </p>

    </header>



    <form id="send-verification" method="post" action="{{ route('verification.send') }}">

        @csrf

    </form>



    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">

        @csrf

        @method('patch')



        <div>

            <x-input-label for="name" :value="__('Name')" />

            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />

            <x-input-error class="mt-2" :messages="$errors->get('name')" />

        </div>



        <div>

            <x-input-label for="email" :value="__('Email')" />

            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />

            <x-input-error class="mt-2" :messages="$errors->get('email')" />



            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())

                <div>

                    <p class="text-sm mt-2 text-gray-800">

                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">

                            {{ __('Click here to re-send the verification email.') }}

                        </button>

                    </p>

                    @if (session('status') === 'verification-link-sent')

                        <p class="mt-2 font-medium text-sm text-green-600">

                            {{ __('A new verification link has been sent to your email address.') }}

                        </p>

                    @endif

                </div>

            @endif

        </div>



        <div>

            <x-input-label for="region" value="Viloyat" />

            <select id="region" name="region" class="mt-1 block w-full rounded-md border-gray-300">

                <option value="">Tanlang</option>

                <option value="Andijon" {{ old('region', $user->region) === 'Andijon' ? 'selected' : '' }}>Andijon</option>

                <option value="Buxoro" {{ old('region', $user->region) === 'Buxoro' ? 'selected' : '' }}>Buxoro</option>

                <option value="Farg'ona" {{ old('region', $user->region) === "Farg'ona" ? 'selected' : '' }}>Farg'ona</option>

                <option value="Jizzax" {{ old('region', $user->region) === 'Jizzax' ? 'selected' : '' }}>Jizzax</option>

                <option value="Xorazm" {{ old('region', $user->region) === 'Xorazm' ? 'selected' : '' }}>Xorazm</option>

                <option value="Namangan" {{ old('region', $user->region) === 'Namangan' ? 'selected' : '' }}>Namangan</option>

                <option value="Navoiy" {{ old('region', $user->region) === 'Navoiy' ? 'selected' : '' }}>Navoiy</option>

                <option value="Qashqadaryo" {{ old('region', $user->region) === 'Qashqadaryo' ? 'selected' : '' }}>Qashqadaryo</option>

                <option value="Qoraqalpog'iston" {{ old('region', $user->region) === "Qoraqalpog'iston" ? 'selected' : '' }}>Qoraqalpog'iston</option>

                <option value="Samarqand" {{ old('region', $user->region) === 'Samarqand' ? 'selected' : '' }}>Samarqand</option>

                <option value="Sirdaryo" {{ old('region', $user->region) === 'Sirdaryo' ? 'selected' : '' }}>Sirdaryo</option>

                <option value="Surxondaryo" {{ old('region', $user->region) === 'Surxondaryo' ? 'selected' : '' }}>Surxondaryo</option>

                <option value="Toshkent shahri" {{ old('region', $user->region) === 'Toshkent shahri' ? 'selected' : '' }}>Toshkent shahri</option>

                <option value="Toshkent viloyati" {{ old('region', $user->region) === 'Toshkent viloyati' ? 'selected' : '' }}>Toshkent viloyati</option>

            </select>

            <p class="mt-1 text-xs text-gray-500">Bu ma'lumot AI maslahatlarini hududingiz shevasiga moslashtirish uchun ishlatiladi.</p>

        </div>



        <div class="flex items-center gap-4">

            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')

                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600">{{ __('Saved.') }}</p>

            @endif

        </div>

    </form>

</section>

