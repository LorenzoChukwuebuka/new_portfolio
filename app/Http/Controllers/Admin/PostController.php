<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    /**
     * Display a listing of posts.
     */
    public function index(Request $request)
    {
        $query = Post::with(['category', 'tags', 'media'])
            ->withCount('tags');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('excerpt', 'like', '%' . $request->search . '%')
                    ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        $posts = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($posts);
    }

    /**
     * Store a newly created post.
     */
    public function store(Request $request)
    {
        // Convert stringified JSON to arrays if needed
        if ($request->has('meta_data') && is_string($request->meta_data)) {
            $request->merge(['meta_data' => json_decode($request->meta_data, true)]);
        }

        if ($request->has('tags') && is_string($request->tags)) {
            $request->merge(['tags' => json_decode($request->tags, true)]);
        }

        $validated = $request->validate([
            'category_id'    => 'nullable|exists:categories,id',
            'title'          => 'required|string|max:255',
            'slug'           => 'nullable|string|max:255|unique:posts,slug',
            'excerpt'        => 'required|string',
            'content'        => 'required|string',
            'author'         => 'nullable|string|max:255',
            'read_time'      => 'nullable|integer|min:1',
            'status'         => 'required|in:draft,published,archived',
            'published_at'   => 'nullable|date',
            'meta_data'      => 'nullable|array',
            'tags'           => 'nullable|array',
            'tags.*'         => 'exists:tags,id',
            'featured_image' => 'nullable|image|max:5120', // 5MB
        ]);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Set published_at if status is published and date not set
        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        // Create post
        $post = Post::create($validated);

        // Attach tags
        if (isset($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $this->uploadFeaturedImage($request->file('featured_image'), $post);
        }

        return response()->json([
            'message' => 'Post created successfully',
            'post'    => $post->load(['category', 'tags', 'media']),
        ], 201);
    }

    /**
     * Display the specified post.
     */
    public function show(Post $post)
    {
        return response()->json($post->load(['category', 'tags', 'media']));
    }

    /**
     * Update the specified post.
     */
    public function update(Request $request, Post $post)
    {

        // Convert stringified JSON to arrays if needed
        if ($request->has('meta_data') && is_string($request->meta_data)) {
            $request->merge(['meta_data' => json_decode($request->meta_data, true)]);
        }

        if ($request->has('tags') && is_string($request->tags)) {
            $request->merge(['tags' => json_decode($request->tags, true)]);
        }

        $validated = $request->validate([
            'category_id'    => 'nullable|exists:categories,id',
            'title'          => 'required|string|max:255',
            'slug'           => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('posts')->ignore($post->id),
            ],
            'excerpt'        => 'required|string',
            'content'        => 'required|string',
            'author'         => 'nullable|string|max:255',
            'read_time'      => 'nullable|integer|min:1',
            'status'         => 'required|in:draft,published,archived',
            'published_at'   => 'nullable|date',
            'meta_data'      => 'nullable|array',
            'tags'           => 'nullable|array',
            'tags.*'         => 'exists:tags,id',
            'featured_image' => 'nullable|image|max:5120',
        ]);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Set published_at if status changed to published
        if ($validated['status'] === 'published' && ! $post->published_at) {
            $validated['published_at'] = $validated['published_at'] ?? now();
        }

        // Update post
        $post->update($validated);

        // Sync tags
        if (isset($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            // Delete old featured image
            $oldFeaturedImage = $post->featuredImage();
            if ($oldFeaturedImage) {
                Storage::delete($oldFeaturedImage->url);
                $oldFeaturedImage->delete();
            }

            $this->uploadFeaturedImage($request->file('featured_image'), $post);
        }

        return response()->json([
            'message' => 'Post updated successfully',
            'post'    => $post->fresh()->load(['category', 'tags', 'media']),
        ]);
    }

    /**
     * Remove the specified post.
     */
    public function destroy(Post $post)
    {
        // Delete associated media files
        foreach ($post->media as $media) {
            Storage::delete($media->url);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully',
        ]);
    }

    /**
     * Restore a soft deleted post.
     */
    public function restore($id)
    {
        $post = Post::withTrashed()->findOrFail($id);
        $post->restore();

        return response()->json([
            'message' => 'Post restored successfully',
            'post'    => $post->load(['category', 'tags', 'media']),
        ]);
    }

    /**
     * Upload featured image for post.
     */
    private function uploadFeaturedImage($file, Post $post)
    {
        $path = $file->store('posts/featured', 'public');

        $post->media()->create([
            'url'       => $path,
            'type'      => 'featured',
            'thumbnail' => $path, // You can generate thumbnail here
        ]);
    }

    /**
     * Upload gallery images.
     */
    public function uploadGalleryImages(Request $request, Post $post)
    {
        $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|max:5120',
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $image) {
            $path = $image->store('posts/gallery', 'public');

            $media = $post->media()->create([
                'url'       => $path,
                'type'      => 'gallery',
                'thumbnail' => $path,
            ]);

            $uploadedImages[] = $media;
        }

        return response()->json([
            'message' => 'Images uploaded successfully',
            'images'  => $uploadedImages,
        ]);
    }

    /**
     * Delete a media item.
     */
    public function deleteMedia(Post $post, Media $media)
    {
        if ($media->mediable_id !== $post->id) {
            return response()->json([
                'message' => 'Media does not belong to this post',
            ], 403);
        }

        Storage::delete($media->url);
        $media->delete();

        return response()->json([
            'message' => 'Media deleted successfully',
        ]);
    }
}