<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use App\Models\Cv;
use App\Models\Project;
use App\Support\Content\ArticleHtml;

class SiteController extends Controller
{
    public function show_home_view()
    {
        $projects = $this->publicProjectsQuery()
            ->where('is_featured', true)
            ->ordered()
            ->limit(4)
            ->get();

        if ($projects->isEmpty()) {
            $projects = $this->publicProjectsQuery()->ordered()->limit(4)->get();
        }

        $posts = $this->publicPostsQuery()
            ->published()
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();
        $totalPosts = Post::published()->count();
        $hasActiveCv = Cv::active()->exists();

        return view('welcome', compact('projects', 'posts', 'totalPosts', 'hasActiveCv'));
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
        $query = $this->publicProjectsQuery()->ordered();
        $status = request()->query('status');
        $technology = trim((string) request()->query('tech', ''));

        if (in_array($status, ['completed', 'in-progress', 'archived'], true)) {
            $query->where('status', $status);
        }

        if ($technology !== '') {
            $query->whereJsonContains('technologies', $technology);
        }

        $projects = $query->paginate(12)->withQueryString();
        $statuses = ['completed', 'in-progress', 'archived'];
        $technologies = Project::query()
            ->orderBy('title')
            ->get(['technologies'])
            ->flatMap(fn (Project $project) => $project->technologies ?? [])
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('project.index', compact('projects', 'statuses', 'technologies', 'status', 'technology'));
    }

    public function show_project_view(string $slug)
    {
        $project = Project::with(['tags', 'media'])
            ->where('slug', $slug)
            ->firstOrFail();

        $orderedProjects = Project::query()
            ->orderBy('order')
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'slug', 'order', 'created_at']);
        $projectIndex = $orderedProjects->search(fn (Project $item) => $item->id === $project->id);
        $previousProject = $projectIndex !== false && $projectIndex > 0 ? $orderedProjects[$projectIndex - 1] : null;
        $nextProject = $projectIndex !== false && $projectIndex < $orderedProjects->count() - 1
            ? $orderedProjects[$projectIndex + 1]
            : null;

        return view('project.show', compact('project', 'previousProject', 'nextProject'));
    }

    public function show_blog_posts_view()
    {
        $query = $this->publicPostsQuery()->published();
        $categorySlug = trim((string) request()->query('category', ''));
        $tagName = trim((string) request()->query('tag', ''));

        if ($categorySlug !== '') {
            $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $categorySlug));
        }
        if ($tagName !== '') {
            $query->whereHas('tags', fn ($tagQuery) => $tagQuery->where('name', $tagName));
        }

        $posts = $query->orderByDesc('published_at')->paginate(9)->withQueryString();
        $totalPosts = Post::published()->count();
        $categories = Category::query()
            ->withCount(['publishedPosts as posts_count'])
            ->orderBy('name')
            ->get();
        $activeCategory = $categorySlug !== '' ? Category::where('slug', $categorySlug)->first() : null;
        $featuredPost = $posts->currentPage() === 1 ? $posts->first() : null;

        return view('blog.index', compact('posts', 'totalPosts', 'categories', 'activeCategory', 'featuredPost', 'tagName'));
    }

    public function show_blog_post_view(string $slug)
    {
        $post = Post::where('slug', $slug)
            ->with(['category', 'tags', 'media'])
            ->published()
            ->firstOrFail();

        $article = app(ArticleHtml::class)->render($post->content);
        if (! $post->read_time) {
            $post->read_time = ArticleHtml::readTime($article['html']);
        }

        $userAgent = (string) request()->userAgent();
        $looksLikeBot = preg_match('/bot|crawler|spider|slurp|preview|monitor|uptime|facebookexternalhit|whatsapp/i', $userAgent) === 1;
        $viewKey = 'viewed_post_'.$post->id;
        if (! $looksLikeBot && ! session()->has($viewKey)) {
            $post->incrementViews();
            $post->refresh();
            session()->put($viewKey, true);
        }

        $previousPost = Post::published()
            ->where(function ($query) use ($post): void {
                $query->where('published_at', '<', $post->published_at)
                    ->orWhere(function ($tie) use ($post): void {
                        $tie->where('published_at', $post->published_at)->where('id', '<', $post->id);
                    });
            })
            ->orderByDesc('published_at')->orderByDesc('id')->first(['id', 'title', 'slug', 'excerpt', 'published_at', 'read_time']);
        $nextPost = Post::published()
            ->where(function ($query) use ($post): void {
                $query->where('published_at', '>', $post->published_at)
                    ->orWhere(function ($tie) use ($post): void {
                        $tie->where('published_at', $post->published_at)->where('id', '>', $post->id);
                    });
            })
            ->orderBy('published_at')->orderBy('id')->first(['id', 'title', 'slug', 'excerpt', 'published_at', 'read_time']);

        $relatedPosts = $post->category_id
            ? $this->publicPostsQuery()->published()
                ->where('category_id', $post->category_id)
                ->whereKeyNot($post->id)
                ->orderByDesc('published_at')->limit(3)->get()
            : collect();
        $comments = $post->approvedComments()->get();

        return view('blog.show', compact('post', 'article', 'previousPost', 'nextPost', 'relatedPosts', 'comments'));
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
