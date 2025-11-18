<?php

namespace App\Services;

use App\Models\InstagramAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class InstagramMediaService
{
    protected InstagramAccount $account;
    protected string $cacheStore;

    public function __construct(InstagramAccount $account)
    {
        $this->account = $account;
        $this->cacheStore = Schema::hasTable('cache') ? 'database' : config('cache.default');
    }

    protected function cacheKeyFeed(string $limit): string
    {
        return "instagram:feed:{$this->account->instagram_user_id}:{$limit}";
    }

    protected function cacheKeyMedia(string $mediaId): string
    {
        return "instagram:media:{$this->account->instagram_user_id}:{$mediaId}";
    }

    public function getFeed(?int $limit = null): array
    {
        $limit = $limit ?: config('instagram.default_limit', 25);
        $key = $this->cacheKeyFeed((string)$limit);
        $ttl = config('instagram.feed_ttl', 300);

        $cache = Cache::store($this->cacheStore);

        return $cache->remember($key, $ttl, function () use ($limit) {
            $res = Http::get(config('instagram.graph_base') . "/{$this->account->instagram_user_id}/media", [
                'fields' => 'id,caption,media_type,media_url,permalink,timestamp,comments_count',
                'access_token' => $this->account->access_token,
                'limit' => $limit,
            ]);

            if ($res->successful()) {
                return $res->json();
            }

            return [
                'error' => true,
                'status' => $res->status(),
                'body' => $res->body()
            ];
        });
    }

    public function getMedia(string $mediaId): array
    {
        $key = $this->cacheKeyMedia($mediaId);
        $ttl = config('instagram.media_ttl', 180);
        $cache = Cache::store($this->cacheStore);

        return $cache->remember($key, $ttl, function () use ($mediaId) {
            $res = Http::get(config('instagram.graph_base') . "/{$mediaId}", [
                'fields' => 'id,caption,media_type,media_url,permalink,timestamp,comments_count,children{media_url,media_type,id}',
                'access_token' => $this->account->access_token,
            ]);

            if ($res->successful()) {
                return $res->json();
            }

            return [
                'error' => true,
                'status' => $res->status(),
                'body' => $res->body()
            ];
        });
    }

    public function clearFeedCache(?int $limit = null): void
    {
        $limit = $limit ?: config('instagram.default_limit', 25);
        $cache = Cache::store($this->cacheStore);
        $cache->forget($this->cacheKeyFeed((string)$limit));
    }

    public function clearMediaCache(string $mediaId): void
    {
        $cache = Cache::store($this->cacheStore);
        $cache->forget($this->cacheKeyMedia($mediaId));
    }
}
