<?php
namespace App\Models;

use App\Models\Category;
use App\Models\Media;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'author',
        'read_time',
        'status',
        'published_at',
        'views_count',
        'meta_data',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'meta_data'    => 'array',
        'views_count'  => 'integer',
    ];

    /**
     * Get the category that owns the post.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all tags for the post (polymorphic).
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'tagable');
    }

    /**
     * Get all media for the post (polymorphic).
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /**
     * Get featured image.
     */
    public function featuredImage()
    {
        return $this->media()->where('type', 'featured')->first();
    }

    /**
     * Get gallery images.
     */
    public function galleryImages()
    {
        return $this->media()->where('type', 'gallery')->get();
    }

    /**
     * Scope a query to only include published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to order by latest.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    /**
     * Increment views count.
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}