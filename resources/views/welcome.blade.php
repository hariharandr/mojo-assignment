@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750">
    <main class="flex max-w-[900px] w-full">
        <div class="flex-1 p-6 bg-white dark:bg-[#161615] rounded-lg shadow">
            <h1 class="mb-1 font-medium">Mojo Assignment Deployment Test (Main Branch)</h1>
            <p class="mb-2 text-[#706f6c] dark:text-[#A1A09A]">Laravel has an incredibly rich ecosystem.</p>

            @if(session('error'))
                <div class="bg-red-100 text-red-800 p-3 mb-4">{{ session('error') }}</div>
            @endif

            <div class="mt-4">
                @include('components.instagram.login-button')
            </div>

            <ul class="mt-6 space-y-3 text-sm text-gray-700">
                <li>Read the <a href="https://laravel.com/docs" class="text-[#f53003] underline">Documentation</a></li>
                <li>Watch <a href="https://laracasts.com" class="text-[#f53003] underline">Laracasts</a></li>
            </ul>
        </div>
    </main>
</div>
@endsection
