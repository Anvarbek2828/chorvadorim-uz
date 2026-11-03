<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $animal->name }}</h2>
            <a href="{{ route('chat.show', $animal) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm mr-2">{{ __('AI Maslahat') }}</a><a href="{{ route('photo-analysis.index', $animal) }}" class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700 text-sm mr-2">{{ __('Rasm tahlili') }}</a>@can('animals.update')<a href="{{ route('animals.edit', $animal) }}" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">{{ __('Tahrirlash') }}</a>@endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
            <div class="mb-4 bg-green-100 text-green-700 p-3 rounded">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
            <div class="mb-4 bg-red-100 text-red-700 p-3 rounded">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-4">
                    <span class="text-sm text-gray-500">{{ __('Yo\'nalish') }}:</span>
                    <span class="font-semibold">
                        @if ($animal->direction === 'sut') &#x1F95B; {{ __('Sut') }}
                        @elseif ($animal->direction === 'gosht') &#x1F969; {{ __('Go\'sht') }}
                        @else &#x1F404; {{ __('Nasl') }}
                        @endif
                    </span>
                </div>
                @if ($animal->breed)
                <div class="mb-4"><span class="text-sm text-gray-500">{{ __('Zoti') }}:</span> <span class="font-semibold">{{ $animal->breed }}</span></div>
                @endif
                @if ($animal->current_weight)
                <div class="mb-4"><span class="text-sm text-gray-500">{{ __('Joriy vazni') }}:</span> <span class="font-semibold">{{ $animal->current_weight }} kg</span></div>
                @endif
                @if ($animal->notes)
                <div class="mb-4 border-t pt-4"><span class="text-sm text-gray-500">{{ __('Izoh') }}:</span><p class="mt-1">{{ $animal->notes }}</p></div>
                @endif
                {{-- Kim qo'shgan: xodim akkaunti o'chirilgan bo'lsa user_id NULL --}}
                <div class="text-sm text-gray-500">{{ __("Qo'shgan") }}: {{ $animal->user?->name ?? __("O'chirilgan foydalanuvchi") }}</div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">{{ __('Eslatmalar') }}</h3>
                @can('reminders.manage')
                <form method="POST" action="{{ route('reminders.store', $animal) }}" class="grid grid-cols-3 gap-3">
                    @csrf
                    <div class="col-span-2"><label class="block text-sm text-gray-700">{{ __('Nima haqida') }}</label><input type="text" name="title" placeholder="{{ __('Masalan: emlash') }}" class="mt-1 block w-full rounded-md border-gray-300" required></div>
                    <div><label class="block text-sm text-gray-700">{{ __('Sana') }}</label><input type="date" name="due_date" class="mt-1 block w-full rounded-md border-gray-300" required></div>
                    <div class="col-span-3"><button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 text-sm">{{ __('Eslatma qo\'shish') }}</button></div>
                </form>
                @endcan
                @if ($animal->reminders->isNotEmpty())
                <div class="mt-4 border-t pt-4 space-y-2">
                    @foreach ($animal->reminders as $reminder)
                    <div class="flex justify-between items-center text-sm">
                        <div>
                            <span class="font-medium">{{ $reminder->title }}</span>
                            <span class="{{ $reminder->due_date->isPast() ? 'text-red-600' : 'text-gray-500' }}">
                                — {{ $reminder->due_date->format('d.m.Y') }}
                                @if ($reminder->due_date->isPast()) ({{ __("o'tib ketgan") }}) @endif
                            </span>
                        </div>
                        @can('reminders.delete')
                        <form method="POST" action="{{ route('reminders.destroy', [$animal, $reminder]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600">x</button>
                        </form>
                        @endcan
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">{{ __('Moliyaviy holat') }}</h3>
                <div class="grid grid-cols-3 gap-4 text-center mb-4">
                    <div class="bg-red-50 rounded p-3">
                        <div class="text-xs text-gray-500">{{ __('Jami xarajat') }}</div>
                        <div class="font-bold text-red-700">{{ number_format($animal->totalExpenses(), 0, '.', ' ') }} {{ __("so'm") }}</div>
                    </div>
                    <div class="bg-green-50 rounded p-3">
                        <div class="text-xs text-gray-500">{{ __('Jami daromad') }}</div>
                        <div class="font-bold text-green-700">{{ number_format($animal->totalIncomes(), 0, '.', ' ') }} {{ __("so'm") }}</div>
                    </div>
                    <div class="{{ $animal->profit() >= 0 ? 'bg-blue-50' : 'bg-orange-50' }} rounded p-3">
                        <div class="text-xs text-gray-500">{{ $animal->profit() >= 0 ? __('Foyda') : __('Zarar') }}</div>
                        <div class="font-bold {{ $animal->profit() >= 0 ? 'text-blue-700' : 'text-orange-700' }}">{{ number_format(abs($animal->profit()), 0, '.', ' ') }} {{ __("so'm") }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">@can('animals.update'){{ __('Xarajat qo\'shish') }}@else{{ __('Xarajatlar') }}@endcan</h3>
                @can('animals.update')
                <form method="POST" action="{{ route('expenses.store', $animal) }}" class="grid grid-cols-2 gap-3">
                    @csrf
                    <div><label class="block text-sm text-gray-700">{{ __('Turi') }}</label><input type="text" name="type" placeholder="{{ __('ozuqa, dori') }}" class="mt-1 block w-full rounded-md border-gray-300" required></div>
                    <div><label class="block text-sm text-gray-700">{{ __('Summa (so\'m)') }}</label><input type="number" step="0.01" name="amount" class="mt-1 block w-full rounded-md border-gray-300" required></div>
                    <div><label class="block text-sm text-gray-700">{{ __('Sana') }}</label><input type="date" name="expense_date" class="mt-1 block w-full rounded-md border-gray-300" required></div>
                    <div><label class="block text-sm text-gray-700">{{ __('Izoh') }}</label><input type="text" name="note" class="mt-1 block w-full rounded-md border-gray-300"></div>
                    <div class="col-span-2"><button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">{{ __('Xarajat qo\'shish') }}</button></div>
                </form>
                @endcan
                @if ($animal->expenses->isNotEmpty())
                <div class="mt-4 border-t pt-4 space-y-2">
                    @foreach ($animal->expenses as $expense)
                    <div class="flex justify-between items-center text-sm">
                        <div><span class="font-medium">{{ $expense->type }}</span> <span class="text-gray-500">{{ $expense->expense_date->format('d.m.Y') }}</span></div>
                        <div class="flex items-center gap-2">
                            <span class="text-red-700 font-semibold">{{ number_format($expense->amount, 0, '.', ' ') }} {{ __("so'm") }}</span>
                            @can('animals.delete')
                            <form method="POST" action="{{ route('expenses.destroy', [$animal, $expense]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600">x</button>
                            </form>
                            @endcan
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">@can('animals.update'){{ __('Daromad qo\'shish') }}@else{{ __('Daromadlar') }}@endcan</h3>
                @can('animals.update')
                <form method="POST" action="{{ route('incomes.store', $animal) }}" class="grid grid-cols-2 gap-3">
                    @csrf
                    <div><label class="block text-sm text-gray-700">{{ __('Turi') }}</label><input type="text" name="type" placeholder="{{ __('sut sotuvi') }}" class="mt-1 block w-full rounded-md border-gray-300" required></div>
                    <div><label class="block text-sm text-gray-700">{{ __('Summa (so\'m)') }}</label><input type="number" step="0.01" name="amount" class="mt-1 block w-full rounded-md border-gray-300" required></div>
                    <div><label class="block text-sm text-gray-700">{{ __('Sana') }}</label><input type="date" name="income_date" class="mt-1 block w-full rounded-md border-gray-300" required></div>
                    <div><label class="block text-sm text-gray-700">{{ __('Izoh') }}</label><input type="text" name="note" class="mt-1 block w-full rounded-md border-gray-300"></div>
                    <div class="col-span-2"><button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">{{ __('Daromad qo\'shish') }}</button></div>
                </form>
                @endcan
                @if ($animal->incomes->isNotEmpty())
                <div class="mt-4 border-t pt-4 space-y-2">
                    @foreach ($animal->incomes as $income)
                    <div class="flex justify-between items-center text-sm">
                        <div><span class="font-medium">{{ $income->type }}</span> <span class="text-gray-500">{{ $income->income_date->format('d.m.Y') }}</span></div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-700 font-semibold">{{ number_format($income->amount, 0, '.', ' ') }} {{ __("so'm") }}</span>
                            @can('animals.delete')
                            <form method="POST" action="{{ route('incomes.destroy', [$animal, $income]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600">x</button>
                            </form>
                            @endcan
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div><a href="{{ route('animals.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('Orqaga qaytish') }}</a></div>
        </div>
    </div>
</x-app-layout>