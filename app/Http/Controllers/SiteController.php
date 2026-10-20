<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Project;

class SiteController extends Controller
{
    public function show_home_view()
    {
        $projects = $this->publicProjectsQuery()->limit(15)->get();
        $posts = $this->publicPostsQuery()->limit(15)->get();

        return view('welcome', compact('projects', 'posts'));
    }

    public function show_posts()
    {
        $query = Post::with(['category', 'tags', 'media'])->published()->withCount('tags');
        $posts = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($posts);
    }

    public function post(string $slug)
    {
        $post = Post::where('slug', $slug)
            ->with(['category', 'tags', 'media'])
            ->published()
            ->firstOrFail();

        $post->incrementViews();

        return response()->json($post);
    }

    public function show_projects_view()
    {
        $projects = $this->publicProjectsQuery()
            ->ordered()
            ->paginate(15);

        return view('project.index', compact('projects'));
    }

    public function show_project_view(string $slug)
    {
        $project = Project::with(['tags', 'media'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('project.show', compact('project'));
    }

    public function show_blog_posts_view()
    {
        $posts = $this->publicPostsQuery()
            ->published()
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('blog.index', compact('posts'));
    }

    public function show_blog_post_view(string $slug)
    {
        $post = Post::where('slug', $slug)
            ->with(['category', 'tags', 'media'])
            ->published()
            ->firstOrFail();

        return view('blog.show', compact('post'));
    }

    public function show_projects()
    {
        $query = Project::with(['tags', 'media'])
            ->withCount('tags');
        $projects = $query->ordered()->paginate(15);

        return response()->json($projects);
    }

    public function show_project(Project $project)
    {
        $project->incrementViews();

        return response()->json($project->load(['tags', 'media']));
    }

    private function publicProjectsQuery()
    {
        return Project::query()
            ->select(['id', 'title', 'slug', 'description', 'project_link', 'github_link', 'technologies', 'status', 'completed_at', 'order', 'is_featured', 'created_at'])
            ->with([
                'tags:id,name',
                'media:id,mediable_id,mediable_type,url,type',
            ]);
    }

    private function publicPostsQuery()
    {
        return Post::query()
            ->select(['id', 'category_id', 'title', 'slug', 'excerpt', 'author', 'read_time', 'status', 'published_at', 'created_at'])
            ->with([
                'category:id,name',
                'tags:id,name',
                'media:id,mediable_id,mediable_type,url,type',
            ]);
    }
}
