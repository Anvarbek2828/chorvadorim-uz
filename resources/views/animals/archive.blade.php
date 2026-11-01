
<x-app-layout>

    <x-slot name="header">

        <div class="flex justify-between items-center">

            <h2 class="font-semibold text-xl text-gray-800 leading-tight">

                Arxiv

            </h2>

            <a href="{{ route('animals.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">

                &larr; Hayvonlarimga qaytish

            </a>

        </div>

    </x-slot>



    <div class="py-12">

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">



            @if (session('success'))

                <div class="mb-4 bg-green-100 text-green-700 p-3 rounded">

                    {{ session('success') }}

                </div>

            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 text-red-700 p-3 rounded">
                    {{ session('error') }}
                    @if (session('show_plans_link'))
                        @include('plans.partials.upgrade-link')
                    @endif
                </div>
            @endif



            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($animals->isEmpty())

                    <p class="text-gray-500">Arxivda hayvon yo'q.</p>

                @else

                    <div class="space-y-3">

                        @foreach ($animals as $animal)

                            <div class="border rounded-lg p-4 flex justify-between items-center">

                                <div>

                                    <div class="font-semibold">{{ $animal->name }}</div>

                                    <div class="text-sm text-gray-500">

                                        @if ($animal->direction === 'sut') &#x1F95B; Sut

                                        @elseif ($animal->direction === 'gosht') &#x1F969; Go'sht

                                        @else &#x1F404; Nasl

                                        @endif

                                        &mdash; O'chirilgan: {{ $animal->deleted_at->format('d.m.Y H:i') }}

                                    </div>

                                </div>

                                <div class="flex gap-2">

                                    <form method="POST" action="{{ route('animals.restore', $animal->id) }}">

                                        @csrf

                                        <button type="submit" class="bg-green-600 text-white px-3 py-1.5 rounded text-sm hover:bg-green-700">

                                            Tiklash

                                        </button>

                                    </form>

                                    <form method="POST" action="{{ route('animals.forceDelete', $animal->id) }}" onsubmit="return confirm('DIQQAT: Bu amalni ortga qaytarib bo\'lmaydi! Hayvon butunlay o\'chiriladi. Davom etasizmi?');">

                                        @csrf

                                        @method('DELETE')

                                        <button type="submit" class="bg-red-600 text-white px-3 py-1.5 rounded text-sm hover:bg-red-700">

                                            Butunlay o'chirish

                                        </button>

                                    </form>

                                </div>

                            </div>

                        @endforeach

                    </div>

                    {{ $animals->onEachSide(1)->links('animals.partials.pagination') }}

                @endif

            </div>

        </div>

    </div>

</x-app-layout>

