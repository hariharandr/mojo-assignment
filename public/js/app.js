import './bootstrap';
import $ from 'jquery';
import LazyLoad from './lazyload';

window.$ = $;
window.jQuery = $;

document.addEventListener('DOMContentLoaded', () => {
    // small feed init bindings (used by profile blade)
    const feedGrid = document.getElementById('feedGrid');
    if (feedGrid) {
        const apiBase = '/api/instagram';
        const limitSelect = document.getElementById('limitSelect');
        const refreshBtn = document.getElementById('refreshFeed');

        async function getJson(path, params = {}) {
            const url = new URL(path, window.location.origin);
            Object.keys(params).forEach(k => url.searchParams.append(k, params[k]));
            const res = await fetch(url, { credentials: 'same-origin' });
            return res.json();
        }

        async function loadFeed() {
            const limit = limitSelect ? limitSelect.value : 24;
            const data = await getJson('/api/instagram/feed', { limit });
            renderFeedItems(data);
        }

        function renderFeedItems(items) {
            const grid = document.getElementById('feedGrid');
            grid.innerHTML = '';
            if (!items || !items.data) {
                grid.innerHTML = '<div class="col-span-full text-center text-sm text-gray-500">No media available</div>';
                return;
            }
            items.data.forEach(item => {
                const div = document.createElement('div');
                div.className = 'rounded overflow-hidden bg-gray-50 cursor-pointer group';
                const thumb = document.createElement('img');
                thumb.alt = item.id;
                thumb.className = 'w-full h-44 object-cover';
                thumb.src = item.media_url ?? '';
                const meta = document.createElement('div');
                meta.className = 'p-2 text-sm';
                const caption = document.createElement('div');
                caption.className = 'line-clamp-2 text-gray-700';
                caption.textContent = item.caption ?? '';
                const actions = document.createElement('div');
                actions.className = 'mt-2 flex items-center justify-between text-xs text-gray-500';
                actions.innerHTML = `<div>${item.media_type ?? ''}</div><div>${item.comments_count ?? 0} comments</div>`;

                div.appendChild(thumb);
                meta.appendChild(caption);
                meta.appendChild(actions);
                div.appendChild(meta);

                div.addEventListener('click', () => {
                    const ev = new CustomEvent('instagram.media.clicked', { detail: item });
                    document.dispatchEvent(ev);
                });
                grid.appendChild(div);
            });
        }

        if (refreshBtn) refreshBtn.addEventListener('click', loadFeed);
        if (limitSelect) limitSelect.addEventListener('change', loadFeed);

        // small lazyload instance for infinite scroll (if you want)
        const lazy = new LazyLoad(window, 200, () => {
            // on bottom reached: load more (you can implement pagination)
            // this is placeholder: just refresh feed
            loadFeed();
        }, 'down');
        lazy.watch();

        loadFeed();
    }

    // comment modal actions
    document.addEventListener('instagram.media.clicked', (e) => {
        const modal = document.getElementById('commentModal');
        const commentText = document.getElementById('commentText');
        const sendComment = document.getElementById('sendComment');
        const closeModal = document.getElementById('closeModal');
        const media = e.detail;
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        commentText.value = '';

        function cleanup() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            sendComment.removeEventListener('click', submit);
            closeModal.removeEventListener('click', cleanup);
        }

        async function submit() {
            const message = commentText.value.trim();
            if (!message) return alert('Please enter a message');
            const res = await fetch(`/api/instagram/media/${media.id}/comments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ message }),
                credentials: 'same-origin'
            });
            const body = await res.json();
            if (body.error) {
                alert('Failed to post comment: ' + (body.body ?? res.statusText));
                return;
            }
            alert('Comment posted');
            cleanup();
        }

        sendComment.addEventListener('click', submit);
        closeModal.addEventListener('click', cleanup);
    });
});
