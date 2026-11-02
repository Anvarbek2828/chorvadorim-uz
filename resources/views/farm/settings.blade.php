<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Ferma sozlamalari') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-100 text-green-700 p-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('farm-settings.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="farm-name" :value="__('Ferma nomi')" />
                        <x-text-input id="farm-name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $farm->name)" maxlength="255" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <x-primary-button>{{ __('Saqlash') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
