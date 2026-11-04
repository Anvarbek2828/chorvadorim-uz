<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>

        <meta charset="utf-8">

        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="csrf-token" content="{{ csrf_token() }}">



        <title>{{ config('app.name', 'Laravel') }}</title>



        <script>

            (function () {

                var theme = localStorage.getItem('chorvadorimTheme');

                if (theme === 'dark') {

                    document.documentElement.classList.add('dark');

                }

            })();

        </script>



        <!-- Fonts -->

        <link rel="preconnect" href="https://fonts.bunny.net">

        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />



        <!-- Scripts -->

        @vite(['resources/css/app.css', 'resources/js/app.js'])



        <style>

            html.dark { color-scheme: dark; }

            html.dark body { background-color:#0f172a !important; color:#f1f5f9 !important; }

            html.dark .bg-gray-100 { background-color:#0f172a !important; }

            html.dark .bg-white { background-color:#1e293b !important; }

            html.dark .bg-gray-50 { background-color:#273548 !important; }

            html.dark .bg-gray-800 { background-color:#0f172a !important; }

            html.dark .bg-gray-700 { background-color:#334155 !important; }



            html.dark h1, html.dark h2, html.dark h3, html.dark h4, html.dark h5, html.dark h6 { color:#f8fafc !important; }

            html.dark p, html.dark span, html.dark div, html.dark label, html.dark li, html.dark td, html.dark th { color:#e2e8f0; }

            html.dark .text-gray-900, html.dark .text-gray-800 { color:#f1f5f9 !important; }

            html.dark .text-gray-700 { color:#e2e8f0 !important; }

            html.dark .text-gray-600 { color:#cbd5e1 !important; }

            html.dark .text-gray-500 { color:#b0bcc9 !important; }

            html.dark .text-gray-400 { color:#94a3b8 !important; }



            html.dark .border, html.dark .border-gray-300, html.dark .border-gray-200, html.dark .border-gray-100 { border-color:#3e4c63 !important; }



            html.dark .shadow, html.dark .shadow-sm { box-shadow:0 1px 3px rgba(0,0,0,.7) !important; }



            html.dark input, html.dark select, html.dark textarea {

                background-color:#273548 !important; color:#f1f5f9 !important; border-color:#475569 !important;

            }

            html.dark input::placeholder, html.dark textarea::placeholder { color:#8494a8 !important; }

            html.dark input[type="radio"], html.dark input[type="checkbox"] { accent-color:#3b82f6; }



            html.dark a { color:#93c5fd; }

            html.dark a.text-gray-600, html.dark a.text-gray-500 { color:#cbd5e1 !important; }



            html.dark .bg-red-100 { background-color:#3f1d2b !important; }

            html.dark .text-red-700, html.dark .text-red-600 { color:#fca5a5 !important; }

            html.dark .bg-green-100 { background-color:#0f2e22 !important; }

            html.dark .text-green-700, html.dark .text-green-600 { color:#86efac !important; }

            html.dark .bg-blue-50, html.dark .bg-blue-100 { background-color:#152238 !important; }

            html.dark .text-blue-700, html.dark .text-blue-600 { color:#93c5fd !important; }

            html.dark .bg-green-50 { background-color:#0f2e22 !important; }

            html.dark .bg-orange-50 { background-color:#3a2313 !important; }

            html.dark .text-orange-700 { color:#fdba74 !important; }

            html.dark .bg-amber-600 { background-color:#b45309 !important; }



            html.dark .ai-disclaimer { background-color:#3a2313 !important; border-color:#78350f !important; }

            html.dark .ai-disclaimer__text { color:#fde68a !important; }

            html.dark .ai-disclaimer__icon { color:#fbbf24 !important; }



            html.dark .bg-gray-800.text-white, html.dark button.bg-gray-800 { background-color:#334155 !important; color:#f8fafc !important; }

            html.dark svg.fill-current { color:#cbd5e1; }

            html.dark hr { border-color:#3e4c63 !important; }

            html.dark details summary { color:#e2e8f0 !important; }

        </style>

    </head>

    <body class="font-sans antialiased">

        <div class="min-h-screen bg-gray-100">

            @include('layouts.navigation')

            @include('layouts.partials.subscription-banner')



            <!-- Page Heading -->

            @isset($header)

                <header class="bg-white shadow">

                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

                        {{ $header }}

                    </div>

                </header>

            @endisset



            <!-- Page Content -->

            <main>

                {{ $slot }}

            </main>

        </div>

    </body>

</html>

