<?php

namespace App\Http\Controllers;

use App\Models\InstagramAccount;
use App\Services\InstagramCommentService;
use App\Services\InstagramMediaService;
use Illuminate\Http\Request;

class InstagramApiController extends Controller
{
    public function profile(Request $request)
    {
        $account = $this->resolveAccount($request);
        return response()->json($account->profile_json ?? [
            'id' => $account->instagram_user_id,
            'username' => $account->username,
        ]);
    }

    public function feed(Request $request)
    {
        $limit = (int) $request->query('limit', config('instagram.default_limit', 25));
        $account = $this->resolveAccount($request);

        $mediaService = new InstagramMediaService($account);
        $data = $mediaService->getFeed($limit);

        return response()->json($data);
    }

    public function media(Request $request, $mediaId)
    {
        $account = $this->resolveAccount($request);
        $mediaService = new InstagramMediaService($account);
        $data = $mediaService->getMedia($mediaId);
        return response()->json($data);
    }

    public function postComment(Request $request, $mediaId)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $account = $this->resolveAccount($request);
        $commentService = new InstagramCommentService($account);
        $result = $commentService->postComment($mediaId, $request->input('message'));

        return response()->json($result);
    }

    public function replyComment(Request $request, $commentId)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $account = $this->resolveAccount($request);
        $commentService = new InstagramCommentService($account);
        $result = $commentService->replyToComment($commentId, $request->input('message'));

        return response()->json($result);
    }

    protected function resolveAccount(Request $request): InstagramAccount
    {
        $igUserId = $request->query('ig_user') ?? $request->input('ig_user');

        if ($igUserId) {
            return InstagramAccount::where('instagram_user_id', $igUserId)->firstOrFail();
        }

        // fallback: use the first connected account
        return InstagramAccount::firstOrFail();
    }
}
