<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Project;
use App\Support\IndexNow\PublicContentIndexNowNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request)
    {
        $validated = $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:50']]);
        $perPage = (int) ($validated['per_page'] ?? 15);
        $query = Project::with(['tags', 'media'])
            ->withCount('tags');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by featured
        if ($request->has('is_featured') && $request->is_featured !== 'all') {
            $query->where('is_featured', $request->is_featured);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%')
                    ->orWhere('full_description', 'like', '%'.$request->search.'%');
            });
        }

        $projects = $query->ordered()->paginate($perPage);

        return response()->json($projects);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        // Convert stringified JSON to arrays if needed
        if ($request->has('technologies') && is_string($request->technologies)) {
            $request->merge(['technologies' => json_decode($request->technologies, true)]);
        }

        if ($request->has('tags') && is_string($request->tags)) {
            $request->merge(['tags' => json_decode($request->tags, true)]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:projects,slug',
            'description' => 'required|string',
            'full_description' => 'nullable|string',
            'project_link' => 'nullable|url',
            'github_link' => 'nullable|url',
            'technologies' => 'required|array',
            'technologies.*' => 'string',
            'status' => 'required|in:completed,in-progress,archived',
            'completed_at' => 'nullable|date',
            'order' => 'nullable|integer|min:0',
            'is_featured' => 'string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'thumbnail' => 'nullable|image|max:5120',
            'main_image' => 'nullable|image|max:5120',
            'remove_thumbnail' => 'nullable|boolean',
            'remove_main_image' => 'nullable|boolean',
        ]);

        if ($validated['is_featured'] === 'true') {
            $validated['is_featured'] = true;
        } else {
            $validated['is_featured'] = false;
        }

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Set default order if not provided
        if (! isset($validated['order'])) {
            $validated['order'] = Project::max('order') + 1;
        }

        // Create project
        $project = DB::transaction(fn () => Project::create($validated));

        // Attach tags
        if (isset($validated['tags'])) {
            $project->tags()->sync($validated['tags']);
        }

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $this->uploadImage($request->file('thumbnail'), $project, 'thumbnail');
        }

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            $this->uploadImage($request->file('main_image'), $project, 'image');
        }

        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project->load(['tags', 'media']),
        ], 201);
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project)
    {
        return response()->json($project->load(['tags', 'media']));
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project)
    {
        // Convert stringified JSON to arrays if needed
        if ($request->has('technologies') && is_string($request->technologies)) {
            $request->merge(['technologies' => json_decode($request->technologies, true)]);
        }

        if ($request->has('tags') && is_string($request->tags)) {
            $request->merge(['tags' => json_decode($request->tags, true)]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('projects')->ignore($project->id),
            ],
            'description' => 'required|string',
            'full_description' => 'nullable|string',
            'project_link' => 'nullable|url',
            'github_link' => 'nullable|url',
            'technologies' => 'required|array',
            'technologies.*' => 'string',
            'status' => 'required|in:completed,in-progress,archived',
            'completed_at' => 'nullable|date',
            'order' => 'nullable|integer|min:0',
            'is_featured' => 'string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'thumbnail' => 'nullable|image|max:5120',
            'main_image' => 'nullable|image|max:5120',
            'remove_thumbnail' => 'nullable|boolean',
            'remove_main_image' => 'nullable|boolean',
        ]);

        if ($validated['is_featured'] === 'true') {
            $validated['is_featured'] = true;
        } else {
            $validated['is_featured'] = false;
        }

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Update project
        DB::transaction(function () use ($project, $validated): void {
            $project->update($validated);
        });

        $publicRelationsChanged = false;

        // Sync tags
        if (isset($validated['tags'])) {
            $changes = $project->tags()->sync($validated['tags']);
            $publicRelationsChanged = $changes['attached'] !== [] || $changes['detached'] !== [] || $changes['updated'] !== [];
        }

        if ($request->boolean('remove_thumbnail') && ! $request->hasFile('thumbnail')) {
            $oldThumbnail = $project->thumbnail();
            if ($oldThumbnail) {
                Storage::disk('public')->delete($oldThumbnail->url);
                $oldThumbnail->delete();
                $publicRelationsChanged = true;
            }
        }
        if ($request->boolean('remove_main_image') && ! $request->hasFile('main_image')) {
            $oldImage = $project->mainImage();
            if ($oldImage) {
                Storage::disk('public')->delete($oldImage->url);
                $oldImage->delete();
                $publicRelationsChanged = true;
            }
        }

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $oldThumbnail = $project->thumbnail();
            if ($oldThumbnail) {
                Storage::disk('public')->delete($oldThumbnail->url);
                $oldThumbnail->delete();
            }
            $this->uploadImage($request->file('thumbnail'), $project, 'thumbnail');
            $publicRelationsChanged = true;
        }

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            $oldImage = $project->mainImage();
            if ($oldImage) {
                Storage::disk('public')->delete($oldImage->url);
                $oldImage->delete();
            }
            $this->uploadImage($request->file('main_image'), $project, 'image');
            $publicRelationsChanged = true;
        }

        if ($publicRelationsChanged) {
            app(PublicContentIndexNowNotifier::class)->projectChanged($project, source: 'project-relations-updated');
        }

        return response()->json([
            'message' => 'Project updated successfully',
            'project' => $project->fresh()->load(['tags', 'media']),
        ]);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project)
    {
        // Delete associated media files
        foreach ($project->media as $media) {
            Storage::delete($media->url);
        }

        DB::transaction(function () use ($project): void {
            $project->delete();
        });

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }

    /**
     * Reorder projects.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'projects' => 'required|array',
            'projects.*.id' => 'required|exists:projects,id',
            'projects.*.order' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request): void {
            foreach ($request->projects as $projectData) {
                Project::query()->findOrFail($projectData['id'])
                    ->update(['order' => $projectData['order']]);
            }
        });

        return response()->json([
            'message' => 'Projects reordered successfully',
        ]);
    }

    public function setFeatured(Request $request, Project $project)
    {
        $validated = $request->validate([
            'is_featured' => ['required', 'boolean'],
        ]);

        $project->update(['is_featured' => $validated['is_featured']]);

        return response()->json([
            'message' => 'Project featured status updated.',
            'project' => $project->fresh(),
        ]);
    }

    /**
     * Upload image for project.
     */
    private function uploadImage($file, Project $project, string $type)
    {
        $path = $file->store('projects/'.$type, 'public');

        $project->media()->create([
            'url' => $path,
            'type' => $type,
            'thumbnail' => $path,
        ]);
    }

    /**
     * Upload gallery images.
     */
    public function uploadGalleryImages(Request $request, Project $project)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|max:5120',
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $image) {
            $path = $image->store('projects/gallery', 'public');

            $media = $project->media()->create([
                'url' => $path,
                'type' => 'gallery',
                'thumbnail' => $path,
            ]);

            $uploadedImages[] = $media;
        }

        app(PublicContentIndexNowNotifier::class)->projectChanged($project, source: 'project-gallery-updated');

        return response()->json([
            'message' => 'Images uploaded successfully',
            'images' => $uploadedImages,
        ]);
    }

    /**
     * Delete a media item.
     */
    public function deleteMedia(Project $project, Media $media)
    {
        if ($media->mediable_id !== $project->id) {
            return response()->json([
                'message' => 'Media does not belong to this project',
            ], 403);
        }

        Storage::delete($media->url);
        $media->delete();

        app(PublicContentIndexNowNotifier::class)->projectChanged($project, source: 'project-media-deleted');

        return response()->json([
            'message' => 'Media deleted successfully',
        ]);
    }
}
