<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="bg-slate-100 dark:bg-slate-900">
            <div class="md:pl-[15rem] pt-16 flex flex-col min-h-screen">
                @include('layouts.navigation')
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-white dark:bg-slate-800 shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main class="flex-grow">
                    {{ $slot }}
                </main>
                <footer
                    class="text-center border-t lg:text-left">
                    <div class="p-4 text-center text-slate-700 dark:text-slate-200">
                        © 2020 - 2023 Copyright:
                        <a class="text-slate-800 dark:text-slate-400"
                        href="https://tailwind-elements.com/">Susana Piñero Rodríguez</a>
                    </div>
                </footer>
            </div>
        </div>
    </body>
</html>
