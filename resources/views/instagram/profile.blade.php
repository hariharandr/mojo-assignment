@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <header class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Instagram Dashboard</h1>
        <div class="flex items-center gap-3">
            @include('components.instagram.login-button')
        </div>
    </header>

    <main class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="col-span-1 bg-white rounded-lg p-5 shadow">
            <div id="profile" class="flex flex-col items-center gap-3">
                <div class="w-24 h-24 rounded-full bg-gray-100 overflow-hidden" id="profileAvatar">
                    <img src="" alt="avatar" id="profileImg" class="w-full h-full object-cover hidden"/>
                </div>
                <div class="text-center">
                    <h2 id="profileName" class="font-medium text-lg"></h2>
                    <p id="profileUsername" class="text-sm text-gray-500"></p>
                </div>
                <div class="mt-4 w-full text-sm text-gray-600 grid grid-cols-2 gap-2">
                    <div class="bg-gray-50 p-3 rounded text-center">
                        <div id="mediaCount" class="text-lg font-semibold">0</div>
                        <div class="text-xs text-gray-400">Media</div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded text-center">
                        <div id="accountType" class="text-lg font-semibold">—</div>
                        <div class="text-xs text-gray-400">Account</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="col-span-2 bg-white rounded-lg p-5 shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium">Feed</h3>
                <div class="flex items-center gap-2">
                    <button id="refreshFeed" class="px-3 py-1 border rounded text-sm">Refresh</button>
                    <select id="limitSelect" class="px-2 py-1 border rounded text-sm">
                        <option value="12">12</option>
                        <option value="24" selected>24</option>
                        <option value="48">48</option>
                    </select>
                </div>
            </div>

            <div id="feedGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3"></div>
        </section>
    </main>
</div>

<!-- Comment modal -->
<div id="commentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg w-full max-w-lg p-4">
        <div class="flex items-center justify-between">
            <h4 class="font-medium">Reply to Post</h4>
            <button id="closeModal" class="text-gray-500">✕</button>
        </div>
        <div class="mt-3">
            <textarea id="commentText" rows="4" class="w-full border rounded p-2" placeholder="Write your reply..."></textarea>
            <div class="flex items-center justify-end gap-2 mt-3">
                <button id="sendComment" class="px-4 py-2 bg-[#f53803] text-white rounded">Send</button>
            </div>
        </div>
    </div>
</div>
@endsection
