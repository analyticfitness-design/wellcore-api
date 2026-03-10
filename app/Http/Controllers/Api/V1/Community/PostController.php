<?php

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use App\Models\CommunityReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /** GET /v1/community/posts */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $posts = CommunityPost::with(['author:id,name'])
            ->withCount('reactions')
            ->where('audience', 'all')
            ->whereNull('parent_id')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(function ($post) use ($userId) {
                $reactionGroups = CommunityReaction::where('post_id', $post->id)
                    ->selectRaw('emoji, COUNT(*) as count')
                    ->groupBy('emoji')
                    ->get()
                    ->map(fn ($r) => ['emoji' => $r->emoji, 'count' => (int) $r->count])
                    ->values();

                $myReaction = CommunityReaction::where('post_id', $post->id)
                    ->where('user_id', $userId)
                    ->value('emoji');

                return array_merge($post->toArray(), [
                    'reactions'   => $reactionGroups,
                    'my_reaction' => $myReaction,
                ]);
            });

        return response()->json(['data' => $posts]);
    }

    /** POST /v1/community/posts */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content'   => 'required|string|max:2000',
            'post_type' => 'in:text,workout,milestone',
            'audience'  => 'in:all,rise',
            'parent_id' => 'nullable|exists:community_posts,id',
        ]);

        $post = $request->user()->communityPosts()->create([
            'content'   => strip_tags(preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $validated['content'])),
            'post_type' => $validated['post_type'] ?? 'text',
            'audience'  => $validated['audience'] ?? 'all',
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json(['data' => $post->load('author:id,name')], 201);
    }

    /** POST /v1/community/posts/{post}/react */
    public function react(Request $request, CommunityPost $post): JsonResponse
    {
        $validated = $request->validate([
            'emoji' => 'required|string|in:🔥,💪,❤️,👏,🏆,⚡',
        ]);

        CommunityReaction::updateOrCreate(
            ['post_id' => $post->id, 'user_id' => $request->user()->id],
            ['emoji' => $validated['emoji']]
        );

        return response()->json(['message' => 'Reacción registrada', 'emoji' => $validated['emoji']]);
    }

    /** DELETE /v1/community/posts/{post}/react */
    public function unreact(Request $request, CommunityPost $post): JsonResponse
    {
        CommunityReaction::where('post_id', $post->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Reacción eliminada']);
    }
}
