<?php

namespace App\Services;

use App\Models\InstagramAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class InstagramCommentService
{
    protected InstagramAccount $account;
    protected string $cacheStore;

    public function __construct(InstagramAccount $account)
    {
        $this->account = $account;
        $this->cacheStore = Schema::hasTable('cache') ? 'database' : config('cache.default');
    }

    protected function clearCachesRelatedToMedia(string $mediaId): void
    {
        $cache = Cache::store($this->cacheStore);
        $cache->forget("instagram:feed:{$this->account->instagram_user_id}:" . config('instagram.default_limit'));
        $cache->forget("instagram:media:{$this->account->instagram_user_id}:{$mediaId}");
    }

    public function postComment(string $mediaId, string $message): array
    {
        $endpoint = config('instagram.graph_base') . "/{$mediaId}/comments";

        $res = Http::asForm()->post($endpoint, [
            'message' => $message,
            'access_token' => $this->account->access_token,
        ]);

        if ($res->successful()) {
            $this->clearCachesRelatedToMedia($mediaId);
            return $res->json();
        }

        return [
            'error' => true,
            'status' => $res->status(),
            'body' => $res->body(),
        ];
    }

    public function replyToComment(string $commentId, string $message): array
    {
        $endpoint = config('instagram.graph_base') . "/{$commentId}/replies";

        $res = Http::asForm()->post($endpoint, [
            'message' => $message,
            'access_token' => $this->account->access_token,
        ]);

        if ($res->successful()) {
            return $res->json();
        }

        return [
            'error' => true,
            'status' => $res->status(),
            'body' => $res->body(),
        ];
    }
}
