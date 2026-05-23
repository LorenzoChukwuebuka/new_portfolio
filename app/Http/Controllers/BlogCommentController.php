<?php

namespace App\Http\Controllers;

use App\Models\BlogComment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogCommentController extends Controller
{
    /**
     * Display approved comments for a post.
     */
    public function index(Post $post): JsonResponse
    {
        return response()->json(
            $post->approvedComments()
                ->get()
                ->map(fn (BlogComment $comment) => $this->formatComment($comment)),
        );
    }

    /**
     * Store a newly created comment or reply.
     */
    public function store(Request $request, Post $post): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json([
                'message' => 'Comment received.',
            ], 201);
        }

        $validated = $request->validate([
            'author_name'  => ['required', 'string', 'max:120'],
            'author_email' => ['required', 'email', 'max:255'],
            'body'         => ['required', 'string', 'min:2', 'max:2000'],
            'parent_id'    => [
                'nullable',
                Rule::exists('blog_comments', 'id')
                    ->where('post_id', $post->id)
                    ->whereNull('parent_id'),
            ],
        ]);

        $comment = $post->comments()->create([
            ...$validated,
            'is_approved' => true,
            'ip_hash'     => $request->ip() ? hash('sha256', $request->ip()) : null,
            'user_agent'  => str($request->userAgent())->limit(500, '')->toString(),
        ]);

        return response()->json([
            'message' => 'Comment posted.',
            'comment' => $this->formatComment($comment->load('replies')),
        ], 201);
    }

    private function formatComment(BlogComment $comment): array
    {
        return [
            'id'          => $comment->id,
            'parent_id'   => $comment->parent_id,
            'author_name' => $comment->author_name,
            'body'        => $comment->body,
            'created_at'  => $comment->created_at?->toISOString(),
            'replies'     => $comment->replies
                ->map(fn (BlogComment $reply) => $this->formatComment($reply))
                ->values(),
        ];
    }
}
