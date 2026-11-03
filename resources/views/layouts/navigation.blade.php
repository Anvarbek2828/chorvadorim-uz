@php
    // Joriy ferma va almashtirgich uchun faol a'zoliklar (CurrentFarm so'rov davomida eslab qoladi)
    $navFarm = Auth::user()->activeFarm();
    $navMemberships = app(\App\Services\CurrentFarm::class)->activeMemberships(Auth::user());
    // "Ferma" menyusi guruhidagi sahifalar
    $navFarmActive = request()->routeIs('plans.*', 'staff.*', 'farm-audit.*', 'farm-settings.*');
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 sticky top-0 z-50">

    <!-- Primary Navigation Menu -->

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex justify-between h-16">

            <div class="flex">

                <!-- Logo -->

                <div class="shrink-0 flex items-center">

                    <a href="{{ route('dashboard') }}">

                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />

                    </a>

                </div>



                <!-- Navigation Links -->

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">

                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">

                        {{ __('Dashboard') }}

                    </x-nav-link>

                    <x-nav-link :href="route('animals.index')" :active="request()->routeIs('animals.*')">

                        {{ __('Mening hayvonlarim') }}

                    </x-nav-link>

                    <!-- Ferma: boshqaruv sahifalari bitta ochiladigan menyuda (birorta huquq bo'lmasa chiqmaydi) -->
                    @canany(['plan.view', 'staff.manage', 'audit.view', 'farm.settings'])
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                {{-- pt-4 + h-9 + pb-3 + border = 66px: tagi chizig'i x-nav-link bilan bir balandlikda (sm:-my-px) --}}
                                <button class="flex items-center px-1 pt-4 pb-3 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out {{ $navFarmActive ? 'border-indigo-400 text-gray-900 focus:border-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:text-gray-700 focus:border-gray-300' }}">
                                    <div class="flex items-center h-9">
                                        <div>{{ __('Ferma') }}</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                @can('plan.view')
                                    <x-dropdown-link :href="route('plans.index')" class="{{ request()->routeIs('plans.*') ? 'font-semibold' : '' }}">
                                        {{ __('Tariflar') }}
                                    </x-dropdown-link>
                                @endcan

                                @can('staff.manage')
                                    <x-dropdown-link :href="route('staff.index')" class="{{ request()->routeIs('staff.*') ? 'font-semibold' : '' }}">
                                        {{ __('Xodimlar') }}
                                    </x-dropdown-link>
                                @endcan

                                @can('audit.view')
                                    <x-dropdown-link :href="route('farm-audit.index')" class="{{ request()->routeIs('farm-audit.*') ? 'font-semibold' : '' }}">
                                        {{ __('Ferma jurnali') }}
                                    </x-dropdown-link>
                                @endcan

                                @can('farm.settings')
                                    <x-dropdown-link :href="route('farm-settings.edit')" class="{{ request()->routeIs('farm-settings.*') ? 'font-semibold' : '' }}">
                                        {{ __('Ferma sozlamalari') }}
                                    </x-dropdown-link>
                                @endcan
                            </x-slot>
                        </x-dropdown>
                    @endcanany

                    @if (Auth::user()->is_admin)
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                            {{ __('Admin panel') }}
                        </x-nav-link>
                    @endif

                </div>

            </div>



            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-4">

                <!-- Shrift kattalashtirish -->

                <div class="flex items-center gap-1 border rounded-md px-1">

                    <button onclick="chorvadorimFontSize(-1)" class="px-2 py-1 text-sm text-gray-600 hover:text-gray-900" title="Shriftni kichraytirish">A-</button>

                    <button onclick="chorvadorimFontSize(1)" class="px-2 py-1 text-base text-gray-600 hover:text-gray-900" title="Shriftni kattalashtirish">A+</button>

                </div>



                <button id="theme-toggle-btn" onclick="chorvadorimToggleTheme()" class="border rounded-md px-2 py-1 text-gray-600 hover:text-gray-900" title="Kunduzgi/qorong'u rejim">&#127769;</button>






                <!-- Til almashtirish -->

                <div class="flex items-center gap-1 border rounded-md px-1">

                    <a href="{{ route('lang.switch', 'uz') }}" class="px-2 py-1 text-sm {{ session('locale', 'uz') === 'uz' ? 'font-bold text-gray-900' : 'text-gray-500' }} hover:text-gray-900">UZ</a>

                    <a href="{{ route('lang.switch', 'uz_cyrl') }}" class="px-2 py-1 text-sm {{ session('locale') === 'uz_cyrl' ? 'font-bold text-gray-900' : 'text-gray-500' }} hover:text-gray-900">ЎЗБ</a>

                    <a href="{{ route('lang.switch', 'ru') }}" class="px-2 py-1 text-sm {{ session('locale') === 'ru' ? 'font-bold text-gray-900' : 'text-gray-500' }} hover:text-gray-900">РУС</a>

                </div>



                <!-- Joriy ferma: almashtirgich (1 tadan ko'p faol a'zolik bo'lsa); sozlamalar "Ferma" menyusida -->
                @if ($navFarm)
                    @if ($navMemberships->count() > 1)
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border rounded-md text-sm leading-4 font-medium text-gray-700 bg-white hover:text-gray-900 focus:outline-none transition ease-in-out duration-150" title="{{ __('Joriy ferma') }}">
                                    <div>&#127968; {{ $navFarm->name }}</div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 py-2 text-xs text-gray-500">{{ __('Fermani almashtirish') }}</div>
                                @foreach ($navMemberships as $navMembership)
                                    <form method="POST" action="{{ route('farm.switch') }}">
                                        @csrf
                                        <input type="hidden" name="farm_id" value="{{ $navMembership->farm_id }}">
                                        <x-dropdown-link :href="route('dashboard')" class="{{ (int) $navMembership->farm_id === (int) $navFarm->id ? 'font-semibold' : '' }}"
                                                onclick="event.preventDefault(); this.closest('form').submit();">
                                            {{ $navMembership->farm?->name }}
                                        </x-dropdown-link>
                                    </form>
                                @endforeach
                            </x-slot>
                        </x-dropdown>
                    @else
                        <div class="text-sm font-medium text-gray-700" title="{{ __('Joriy ferma') }}">&#127968; {{ $navFarm->name }}</div>
                    @endif
                @endif

                <!-- Settings Dropdown -->

                <x-dropdown align="right" width="48">

                    <x-slot name="trigger">

                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">

                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">

                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">

                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />

                                </svg>

                            </div>

                        </button>

                    </x-slot>



                    <x-slot name="content">

                        <x-dropdown-link :href="route('profile.edit')">

                            {{ __('Profile') }}

                        </x-dropdown-link>



                        <!-- Authentication -->

                        <form method="POST" action="{{ route('logout') }}">

                            @csrf



                            <x-dropdown-link :href="route('logout')"

                                    onclick="event.preventDefault();

                                                this.closest('form').submit();">

                                {{ __('Log Out') }}

                            </x-dropdown-link>

                        </form>

                    </x-slot>

                </x-dropdown>

            </div>



            <!-- Hamburger -->

            <div class="-me-2 flex items-center sm:hidden">

                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">

                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">

                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />

                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />

                    </svg>

                </button>

            </div>

        </div>

    </div>



    <!-- Responsive Navigation Menu -->

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">

        <div class="pt-2 pb-3 space-y-1">

            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">

                {{ __('Dashboard') }}

            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('animals.index')" :active="request()->routeIs('animals.*')">

                {{ __('Mening hayvonlarim') }}

            </x-responsive-nav-link>

            @canany(['plan.view', 'staff.manage', 'audit.view', 'farm.settings'])
                <div class="px-4 pt-2 text-xs text-gray-500">{{ __('Ferma') }}</div>

                @can('plan.view')
                    <x-responsive-nav-link :href="route('plans.index')" :active="request()->routeIs('plans.*')">
                        {{ __('Tariflar') }}
                    </x-responsive-nav-link>
                @endcan

                @can('staff.manage')
                    <x-responsive-nav-link :href="route('staff.index')" :active="request()->routeIs('staff.*')">
                        {{ __('Xodimlar') }}
                    </x-responsive-nav-link>
                @endcan

                @can('audit.view')
                    <x-responsive-nav-link :href="route('farm-audit.index')" :active="request()->routeIs('farm-audit.*')">
                        {{ __('Ferma jurnali') }}
                    </x-responsive-nav-link>
                @endcan

                @can('farm.settings')
                    <x-responsive-nav-link :href="route('farm-settings.edit')" :active="request()->routeIs('farm-settings.*')">
                        {{ __('Ferma sozlamalari') }}
                    </x-responsive-nav-link>
                @endcan
            @endcanany

            @if (Auth::user()->is_admin)
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                    {{ __('Admin panel') }}
                </x-responsive-nav-link>
            @endif

        </div>



        <!-- Mobil: shrift va til -->

        <div class="px-4 py-3 border-t border-gray-200 flex flex-wrap items-center gap-4">

            <div class="flex items-center gap-1 border rounded-md px-1">

                <button onclick="chorvadorimFontSize(-1)" class="px-2 py-1 text-sm text-gray-600">A-</button>

                <button onclick="chorvadorimFontSize(1)" class="px-2 py-1 text-base text-gray-600">A+</button>

                <button id="theme-toggle-btn-mobile" onclick="chorvadorimToggleTheme()" class="border rounded-md px-2 py-1 text-gray-600">&#127769;</button>

            </div>

            <div class="flex items-center gap-1 border rounded-md px-1">

                <a href="{{ route('lang.switch', 'uz') }}" class="px-2 py-1 text-sm {{ session('locale', 'uz') === 'uz' ? 'font-bold text-gray-900' : 'text-gray-500' }}">UZ</a>

                <a href="{{ route('lang.switch', 'uz_cyrl') }}" class="px-2 py-1 text-sm {{ session('locale') === 'uz_cyrl' ? 'font-bold text-gray-900' : 'text-gray-500' }}">ЎЗБ</a>

                <a href="{{ route('lang.switch', 'ru') }}" class="px-2 py-1 text-sm {{ session('locale') === 'ru' ? 'font-bold text-gray-900' : 'text-gray-500' }}">РУС</a>

            </div>

        </div>



        <!-- Responsive Settings Options -->

        <div class="pt-4 pb-1 border-t border-gray-200">

            <div class="px-4">

                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>

                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>

                @if ($navFarm)
                    <div class="font-medium text-sm text-gray-700 mt-1">&#127968; {{ $navFarm->name }}</div>
                @endif

            </div>



            <div class="mt-3 space-y-1">

                <x-responsive-nav-link :href="route('profile.edit')">

                    {{ __('Profile') }}

                </x-responsive-nav-link>

                @if ($navMemberships->count() > 1)
                    @foreach ($navMemberships as $navMembership)
                        @continue((int) $navMembership->farm_id === (int) $navFarm?->id)
                        <form method="POST" action="{{ route('farm.switch') }}">
                            @csrf
                            <input type="hidden" name="farm_id" value="{{ $navMembership->farm_id }}">
                            <x-responsive-nav-link :href="route('dashboard')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __("Fermaga o'tish") }}: {{ $navMembership->farm?->name }}
                            </x-responsive-nav-link>
                        </form>
                    @endforeach
                @endif



                <!-- Authentication -->

                <form method="POST" action="{{ route('logout') }}">

                    @csrf



                    <x-responsive-nav-link :href="route('logout')"

                            onclick="event.preventDefault();

                                        this.closest('form').submit();">

                        {{ __('Log Out') }}

                    </x-responsive-nav-link>

                </form>

            </div>

        </div>

    </div>

</nav>



<script>

    function chorvadorimFontSize(step) {

        let size = parseInt(localStorage.getItem('chorvadorimFontSize') || '100');

        size = Math.min(150, Math.max(80, size + step * 10));

        localStorage.setItem('chorvadorimFontSize', size);

        document.documentElement.style.fontSize = size + '%';

    }

    (function () {

        const saved = localStorage.getItem('chorvadorimFontSize');

        if (saved) {

            document.documentElement.style.fontSize = saved + '%';

        }

    })();

</script>



<script>

    function chorvadorimToggleTheme() {

        const html = document.documentElement;

        html.classList.toggle('dark');

        const isDark = html.classList.contains('dark');

        localStorage.setItem('chorvadorimTheme', isDark ? 'dark' : 'light');

        updateThemeButtonIcon();

    }



    function updateThemeButtonIcon() {

        const isDark = document.documentElement.classList.contains('dark');

        const icon = isDark ? '&#9728;' : '&#127769;';

        document.querySelectorAll('#theme-toggle-btn, #theme-toggle-btn-mobile').forEach(function (btn) {

            btn.innerHTML = icon;

        });

    }



    document.addEventListener('DOMContentLoaded', updateThemeButtonIcon);

</script>

<script>

    function chorvadorimToggleTheme() {

        const html = document.documentElement;

        html.classList.toggle('dark');

        const isDark = html.classList.contains('dark');

        localStorage.setItem('chorvadorimTheme', isDark ? 'dark' : 'light');

        updateThemeButtonIcon();

    }



    function updateThemeButtonIcon() {

        const isDark = document.documentElement.classList.contains('dark');

        const icon = isDark ? '&#9728;' : '&#127769;';

        document.querySelectorAll('#theme-toggle-btn, #theme-toggle-btn-mobile').forEach(function (btn) {

            btn.innerHTML = icon;

        });

    }



    document.addEventListener('DOMContentLoaded', updateThemeButtonIcon);

</script>