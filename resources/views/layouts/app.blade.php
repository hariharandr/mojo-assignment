<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>{{ $title ?? config('app.name') }}</title>
    
    <!-- Remove the CDN link - you're using local compiled CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18]">
    @include('partials.header')
    <main class="max-w-6xl mx-auto p-6">
        @yield('content')
    </main>
    @include('partials.footer')
</body>
</html>