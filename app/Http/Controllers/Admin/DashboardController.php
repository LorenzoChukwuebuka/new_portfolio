<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Cv;
use App\Models\Post;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function index()
    {
        $stats = [
            'posts'      => [
                'total'       => Post::count(),
                'published'   => Post::where('status', 'published')->count(),
                'draft'       => Post::where('status', 'draft')->count(),
                'archived'    => Post::where('status', 'archived')->count(),
                'total_views' => Post::sum('views_count'),
            ],
            'projects'   => [
                'total'       => Project::count(),
                'completed'   => Project::where('status', 'completed')->count(),
                'in_progress' => Project::where('status', 'in-progress')->count(),
                'featured'    => Project::where('is_featured', true)->count(),
            ],
            'contacts'   => [
                'total'   => ContactMessage::count(),
                'unread'  => ContactMessage::where('status', 'unread')->count(),
                'read'    => ContactMessage::where('status', 'read')->count(),
                'replied' => ContactMessage::where('status', 'replied')->count(),
                'today'   => ContactMessage::whereDate('created_at', today())->count(),
            ],
            'cvs'        => [
                'total'           => Cv::count(),
                'active'          => Cv::where('is_active', true)->count(),
                'total_downloads' => Cv::sum('download_count'),
            ],
            'categories' => Category::count(),
            'tags'       => Tag::count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get recent activities.
     */
    public function recentActivities()
    {
        $recentPosts = Post::with('category')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($post) {
                return [
                    'type'   => 'post',
                    'title'  => $post->title,
                    'status' => $post->status,
                    'date'   => $post->created_at,
                    //'url'    => route('admin.posts.edit', $post->id),
                ];
            });

        $recentProjects = Project::latest()
            ->take(5)
            ->get()
            ->map(function ($project) {
                return [
                    'type'   => 'project',
                    'title'  => $project->title,
                    'status' => $project->status,
                    'date'   => $project->created_at,
                    //'url'    => route('admin.projects.edit', $project->id),
                ];
            });

        $recentMessages = ContactMessage::latest()
            ->take(5)
            ->get()
            ->map(function ($message) {
                return [
                    'type'   => 'contact',
                    'title'  => $message->subject ?? 'No subject',
                    'status' => $message->status,
                    'date'   => $message->created_at,
                   // 'url'    => route('admin.contacts.show', $message->id),
                ];
            });

        $activities = collect()
            ->merge($recentPosts)
            ->merge($recentProjects)
            ->merge($recentMessages)
            ->sortByDesc('date')
            ->take(10)
            ->values();

        return response()->json($activities);
    }

    /**
     * Get popular posts.
     */
    public function popularPosts()
    {
        $posts = Post::with('category')
            ->published()
            ->orderBy('views_count', 'desc')
            ->take(10)
            ->get();

        return response()->json($posts);
    }

    /**
     * Get posts analytics by month.
     */
    public function postsAnalytics(Request $request)
    {
        $months = $request->get('months', 6);

        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date       = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd   = $date->copy()->endOfMonth();

            $data[] = [
                'month'     => $date->format('M Y'),
                'published' => Post::where('status', 'published')
                    ->whereBetween('published_at', [$monthStart, $monthEnd])
                    ->count(),
                'total'     => Post::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count(),
            ];
        }

        return response()->json($data);
    }

    /**
     * Get contact messages analytics.
     */
    public function contactsAnalytics(Request $request)
    {
        $days = $request->get('days', 7);

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $data[] = [
                'date'  => $date->format('M d'),
                'count' => ContactMessage::whereDate('created_at', $date)->count(),
            ];
        }

        return response()->json($data);
    }
}