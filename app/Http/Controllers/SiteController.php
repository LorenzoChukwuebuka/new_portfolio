<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Project;

class SiteController extends Controller
{
    public function show_posts()
    {
        $query = Post::with(['category', 'tags', 'media'])->where('status', 'published')->withCount('tags');
        $posts = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($posts);
    }

  public function post(string $slug)
{
    $post = Post::where('slug', $slug)
        ->with(['category', 'tags', 'media'])
        ->where('status', 'published')
        ->firstOrFail();

    return response()->json($post);
}


    public function show_projects_view()
    {
        return view('project.index');
    }

    public function show_project_view()
    {
        return view('project.show');
    }

    public function show_blog_posts_view()
    {
        return view('blog.index');
    }

    public function show_blog_post_view(string $slug)
    {
        $post = Post::where('slug', $slug)
            ->with(['category', 'tags', 'media'])
            ->where('status', 'published')
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
        return response()->json($project->load(['tags', 'media']));
    }
}