<?php

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = CommunityPost::with(['author:id,name'])
            ->where('audience', 'all')
            ->whereNull('parent_id')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json(['data' => $posts]);
    }

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

        return response()->json(['data' => $post], 201);
    }
}
