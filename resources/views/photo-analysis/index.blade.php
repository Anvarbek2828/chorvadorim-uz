<x-app-layout>

    <x-slot name="header">

        <div class="flex justify-between items-center">

            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $animal->name }} — Rasm tahlili</h2>

            <a href="{{ route('animals.show', $animal) }}" class="text-gray-600 hover:text-gray-900 text-sm">Orqaga qaytish</a>

        </div>

    </x-slot>



    <div class="py-12">

        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">



            @if (session('success'))

            <div class="bg-green-100 text-green-700 p-3 rounded">{{ session('success') }}</div>

            @endif

            @if (session('error'))

            <div class="bg-red-100 text-red-700 p-3 rounded">
                {{ session('error') }}
                @if (session('show_plans_link'))
                    @include('plans.partials.upgrade-link')
                @endif
            </div>

            @endif



            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('photo-analysis.store', $animal) }}" enctype="multipart/form-data">

                    @csrf

                    <label class="block text-sm font-medium text-gray-700 mb-2">Yangi rasm yuklang</label>

                    <input type="file" name="photo" accept="image/*" capture="environment" required

                        class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-gray-800 file:text-white hover:file:bg-gray-700">

                    <p class="text-xs text-gray-400 mt-2">Hayvonning yon tomondan, to'liq tanasi ko'rinadigan rasmini yuklang.</p>

                    <button type="submit" class="mt-4 bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">

                        Rasmni tahlil qilish

                    </button>

                </form>

            </div>



            @if ($analyses->count() >= 2)

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <h3 class="font-semibold text-gray-800 mb-4">O'sish dinamikasi</h3>

                <canvas id="growthChart" height="120"></canvas>

            </div>

            @endif



            <div class="space-y-4">

                @forelse ($analyses->reverse() as $analysis)

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <div class="flex justify-between items-start">

                        <div class="text-sm text-gray-500">{{ $analysis->created_at->format('d.m.Y H:i') }}</div>

                        <form method="POST" action="{{ route('photo-analysis.destroy', [$animal, $analysis]) }}" onsubmit="return confirm('Bu tahlilni o\'chirmoqchimisiz?');">

                            @csrf

                            @method('DELETE')

                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs">O'chirish</button>

                        </form>

                    </div>

                    <div class="flex gap-4 mt-1 text-sm">

                        @if ($analysis->bcs_score)

                        <span class="font-medium">BCS: {{ $analysis->bcs_score }}/5</span>

                        @endif

                        @if ($analysis->estimated_weight)

                        <span class="font-medium">Taxminiy vazn: {{ $analysis->estimated_weight }} kg</span>

                        @endif

                    </div>

                    @if ($analysis->ai_summary)

                    <p class="text-gray-700 text-sm mt-2">{{ $analysis->ai_summary }}</p>

                    @endif

                    <x-ai-disclaimer type="image" />



                    <img src="{{ Storage::url($analysis->photo_path) }}" alt="Hayvon rasmi"

                        style="max-width:100%;max-height:280px;width:auto;height:auto;object-fit:cover;border-radius:0.5rem;"

                        class="mt-3 rounded-lg">



                    @if ($analysis->chatMessages->isNotEmpty())

                    <div class="mt-4 pt-4 border-t space-y-4">

                        @foreach ($analysis->chatMessages as $msg)

                        <div>

                            <div class="text-xs text-gray-500 mb-1">Savol:</div>

                            <div class="text-sm font-medium bg-gray-50 rounded p-2 mb-2">{{ $msg->question }}</div>

                            <div class="text-xs text-gray-500 mb-1">Javob:</div>

                            <div class="text-sm text-gray-800">{!! $msg->answer !!}</div>

                            <x-ai-disclaimer />

                        </div>

                        @endforeach

                    </div>

                    @endif



                    <div class="mt-4 pt-4 border-t">

                        <div class="flex items-center gap-2 mb-3">

                            <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background-color:#2563eb;border-radius:9999px;flex-shrink:0;">

                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="18" height="18">

                                    <path d="M21.426 11.095l-17-8A1 1 0 0 0 3.03 4.242l1.212 8.485a1 1 0 0 0 0 .546L3.03 21.758a1 1 0 0 0 1.396 1.147l17-8a1 1 0 0 0 0-1.81zM5.679 19.6l.939-6.6H12a1 1 0 0 0 0-2H6.618l-.939-6.6L18.945 12z"/>

                                </svg>

                            </span>

                            <span class="text-sm font-medium text-gray-700">Shu tahlil bo'yicha savolingiz bo'lsa, shu yerga yozing:</span>

                        </div>

                        <form method="POST" action="{{ route('photo-analysis.ask', [$animal, $analysis]) }}">

                            @csrf

                            <textarea name="question" rows="2" class="block w-full rounded-md border-gray-300 text-sm" placeholder="Masalan: BCS 3.5 bu yaxshimi? Nima qilishim kerak?" required></textarea>

                            <button type="submit" class="mt-2 bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700 text-xs">Savol yuborish</button>

                        </form>

                    </div>

                </div>

                @empty

                <div class="text-gray-500 text-sm text-center py-8">Hali rasm tahlili qilinmagan. Yuqoridagi formadan birinchi rasmni yuklang.</div>

                @endforelse

            </div>



        </div>

    </div>



    @if ($analyses->count() >= 2)

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

    <script>

        const labels = @json($analyses->pluck('created_at')->map(fn($d) => $d->format('d.m.Y')));

        const bcsData = @json($analyses->pluck('bcs_score'));

        const weightData = @json($analyses->pluck('estimated_weight'));



        new Chart(document.getElementById('growthChart'), {

            type: 'line',

            data: {

                labels: labels,

                datasets: [

                    { label: 'BCS (1-5)', data: bcsData, borderColor: '#d97706', backgroundColor: '#d9770622', yAxisID: 'y1', tension: 0.3 },

                    { label: 'Taxminiy vazn (kg)', data: weightData, borderColor: '#1f2937', backgroundColor: '#1f293722', yAxisID: 'y2', tension: 0.3 }

                ]

            },

            options: {

                scales: {

                    y1: { type: 'linear', position: 'left', min: 1, max: 5, title: { display: true, text: 'BCS' } },

                    y2: { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'kg' } },

                }

            }

        });

    </script>

    @endif

</x-app-layout>

