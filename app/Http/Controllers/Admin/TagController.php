<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    /**
     * Display a listing of tags.
     */
    public function index(Request $request)
    {
        $query = Tag::withCount(['posts', 'projects']);

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $tags = $query->orderBy('name')->paginate(15);

        return response()->json($tags);
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
            'type' => 'nullable|string|max:255',
        ]);

        $tag = Tag::create($validated);

        return response()->json([
            'message' => 'Tag created successfully',
            'tag' => $tag,
        ], 201);
    }

    /**
     * Display the specified tag.
     */
    public function show(Tag $tag)
    {
        return response()->json($tag->load(['posts', 'projects']));
    }

    /**
     * Update the specified tag.
     */
    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags')->ignore($tag->id),
            ],
            'type' => 'nullable|string|max:255',
        ]);

        $tag->update($validated);

        return response()->json([
            'message' => 'Tag updated successfully',
            'tag' => $tag->fresh(),
        ]);
    }

    /**
     * Remove the specified tag.
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response()->json([
            'message' => 'Tag deleted successfully',
        ]);
    }

    /**
     * Restore a soft deleted tag.
     */
    public function restore($id)
    {
        $tag = Tag::withTrashed()->findOrFail($id);
        $tag->restore();

        return response()->json([
            'message' => 'Tag restored successfully',
            'tag' => $tag,
        ]);
    }

    /**
     * Get all tags for dropdown.
     */
    public function list(Request $request)
    {
        $query = Tag::select('id', 'name', 'type');

        // Filter by type if provided
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        $tags = $query->orderBy('name')->get();

        return response()->json($tags);
    }

    /**
     * Get tags usage statistics.
     */
    public function stats()
    {
        $stats = [
            'total' => Tag::count(),
            'with_posts' => Tag::has('posts')->count(),
            'with_projects' => Tag::has('projects')->count(),
            'unused' => Tag::doesntHave('posts')
                ->doesntHave('projects')
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Bulk delete unused tags.
     */
    public function deleteUnused()
    {
        $count = Tag::doesntHave('posts')
            ->doesntHave('projects')
            ->delete();

        return response()->json([
            'message' => "Deleted {$count} unused tags",
            'count' => $count,
        ]);
    }
}