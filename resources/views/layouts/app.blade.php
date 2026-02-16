<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body>
        <nav class="bg-gray-800 p-4">
            <div class="container mx-auto flex items-center justify-between">
                <a href="{{ url('/') }}" class="text-white text-lg font-semibold">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <ul class="flex space-x-4">
                    <li>
                        <a href="{{ url('/') }}" class="text-gray-300 hover:text-white">Home</a>
                    </li>
                    <li>
                        <a href="{{ url('/bills') }}" class="text-gray-300 hover:text-white">Contas</a>
                    </li>
                    {{-- <li>
                        <a href="{{ url('/cart') }}" class="text-gray-300 hover:text-white">Cart</a>
                    </li>
                    <li>
                        <a href="{{ url('/contact') }}" class="text-gray-300 hover:text-white">Contact</a>
                    </li> --}}
                </ul>
            </div>
        </nav>

        <x-alert-success/>

        {{ $slot }}
        
        @livewireScripts
    </body>
</html>
