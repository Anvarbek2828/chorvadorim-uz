<x-app-layout>

    <x-slot name="header">

        <h2 class="font-semibold text-xl text-gray-800 leading-tight">

            {{ __('Dashboard') }}

        </h2>

    </x-slot>

    <div class="py-12">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900">

                    {{ __("You're logged in!") }}

                </div>

            </div>



            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <h3 class="font-semibold text-lg mb-4">{{ __("Chorvadorim.uz nima qila oladi?") }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="border rounded-lg p-4 flex gap-3">

                        <div class="text-3xl">&#x1F404;</div>

                        <div>

                            <div class="font-semibold">{{ __("Hayvonlarni ro'yxatga olish") }}</div>

                            <div class="text-sm text-gray-500">{{ __("Har bir hayvon uchun zoti, vazni, pasport ma'lumotlarini saqlang") }}</div>

                        </div>

                    </div>

                    <div class="border rounded-lg p-4 flex gap-3">

                        <div class="text-3xl">&#x1F4AC;</div>

                        <div>

                            <div class="font-semibold">{{ __('AI Maslahat') }}</div>

                            <div class="text-sm text-gray-500">{{ __("Ozuqa, kasallik va parvarish bo'yicha savol bering - sun'iy intellekt javob beradi") }}</div>

                        </div>

                    </div>

                    <div class="border rounded-lg p-4 flex gap-3">

                        <div class="text-3xl">&#x1F4F7;</div>

                        <div>

                            <div class="font-semibold">{{ __('Rasm tahlili') }}</div>

                            <div class="text-sm text-gray-500">{{ __("Hayvon rasmini yuklang - tana holati va taxminiy vazn avtomatik hisoblanadi") }}</div>

                        </div>

                    </div>

                    <div class="border rounded-lg p-4 flex gap-3">

                        <div class="text-3xl">&#x1F4B0;</div>

                        <div>

                            <div class="font-semibold">{{ __('Moliyaviy hisob') }}</div>

                            <div class="text-sm text-gray-500">{{ __("Har bir hayvon uchun xarajat va daromadni alohida kuzating") }}</div>

                        </div>

                    </div>

                    <div class="border rounded-lg p-4 flex gap-3">

                        <div class="text-3xl">&#x2708;</div>

                        <div>

                            <div class="font-semibold">{{ __('Telegram bot') }}</div>

                            <div class="text-sm text-gray-500">{{ __("Hayvon holatini Telegram orqali ham bilib turing") }}</div>

                        </div>

                    </div>

                    <div class="border rounded-lg p-4 flex gap-3">

                        <div class="text-3xl">&#x1F310;</div>

                        <div>

                            <div class="font-semibold">{{ __("Uch tilda ishlaydi") }}</div>

                            <div class="text-sm text-gray-500">{{ __("O'zbek (lotin/kirill) va rus tillarida foydalanish mumkin") }}</div>

                        </div>

                    </div>

                </div>

            </div>



            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if (auth()->user()->telegram_chat_id)

                    <div class="flex items-center gap-2 text-green-700">

                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">

                            <path d="M9.71 12.29a1 1 0 0 0-1.42 1.42l3 3a1 1 0 0 0 1.42 0l7-7a1 1 0 0 0-1.42-1.42L12 14.59l-2.29-2.3z"/>

                        </svg>

                        <span class="font-medium">{{ __("Telegram bog'langan") }}</span>

                    </div>

                @else

                    <h3 class="font-semibold text-lg mb-2">{{ __("Telegram bot orqali hayvon holatini bilib turing") }}</h3>

                    <p class="text-sm text-gray-500 mb-4">{{ __("Botni oching (@chorvadorim_bot), keyin quyidagi kodni unga yuboring.") }}</p>



                    <button id="tg-generate-btn" onclick="generateTelegramCode()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">

                        {{ __("Kod olish") }}

                    </button>



                    <div id="tg-code-box" class="hidden mt-4">

                        <div class="text-4xl font-bold tracking-widest text-gray-800" id="tg-code-value"></div>

                        <div class="text-sm text-gray-500 mt-2" id="tg-code-timer"></div>

                    </div>

                @endif

            </div>

        </div>

    </div>



    <script>

        let tgCountdownInterval = null;



        function generateTelegramCode() {

            fetch("{{ route('telegram.generate-code') }}", {

                method: 'POST',

                headers: {

                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',

                    'Accept': 'application/json',

                }

            })

            .then(res => res.json())

            .then(data => {

                document.getElementById('tg-code-value').textContent = data.code;

                document.getElementById('tg-code-box').classList.remove('hidden');



                const expiresAt = new Date(data.expires_at).getTime();

                if (tgCountdownInterval) clearInterval(tgCountdownInterval);



                tgCountdownInterval = setInterval(() => {

                    const remaining = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));

                    const min = Math.floor(remaining / 60);

                    const sec = remaining % 60;

                    document.getElementById('tg-code-timer').textContent =

                        remaining > 0 ? `Amal qilish muddati: ${min}:${sec.toString().padStart(2, '0')}` : 'Kod muddati tugadi, qayta oling.';

                    if (remaining <= 0) clearInterval(tgCountdownInterval);

                }, 1000);

            });

        }

    </script>

</x-app-layout>

