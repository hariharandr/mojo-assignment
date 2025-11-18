<header class="w-full py-4 mb-6 flex items-center justify-between">
    <div>
        <a href="{{ url('/') }}" class="font-semibold text-lg">{{ config('app.name') }}</a>
    </div>
    <nav class="flex items-center gap-3">
        @auth
            <a href="{{ url('/dashboard') }}" class="px-4 py-2 border rounded text-sm">Dashboard</a>
            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-black text-white rounded text-sm">Logout</button>
            </form>
        @else
            <a href="{{ route('instagram.redirect') }}" class="px-4 py-2 bg-[#1b1b18] text-white rounded text-sm">Login with Instagram</a>
        @endauth
    </nav>
</header>
