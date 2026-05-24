<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogCommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $comments = BlogComment::with('post:id,title,slug')
            ->when(
                $request->filled('status') && $request->status !== 'all',
                fn ($query) => $query->where('status', $request->status),
            )
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->search . '%';

                $query->where(function ($inner) use ($search) {
                    $inner->where('author_name', 'like', $search)
                        ->orWhere('author_email', 'like', $search)
                        ->orWhere('body', 'like', $search);
                });
            })
            ->latest()
            ->paginate(20);

        return response()->json($comments);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'total'    => BlogComment::count(),
            'pending'  => BlogComment::where('status', 'pending')->count(),
            'approved' => BlogComment::where('status', 'approved')->count(),
            'spam'     => BlogComment::where('status', 'spam')->count(),
        ]);
    }

    public function approve(BlogComment $comment): JsonResponse
    {
        $comment->update([
            'status'      => 'approved',
            'is_approved' => true,
        ]);

        return response()->json(['message' => 'Comment approved.']);
    }

    public function markPending(BlogComment $comment): JsonResponse
    {
        $comment->update([
            'status'      => 'pending',
            'is_approved' => false,
        ]);

        return response()->json(['message' => 'Comment moved to pending.']);
    }

    public function markSpam(BlogComment $comment): JsonResponse
    {
        $comment->update([
            'status'      => 'spam',
            'is_approved' => false,
        ]);

        return response()->json(['message' => 'Comment marked as spam.']);
    }

    public function destroy(BlogComment $comment): JsonResponse
    {
        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
