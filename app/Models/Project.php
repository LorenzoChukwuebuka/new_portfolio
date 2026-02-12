<?php

namespace App\Models;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'full_description',
        'project_link',
        'github_link',
        'technologies',
        'status',
        'completed_at',
        'order',
        'is_featured',
    ];

    protected $casts = [
        'technologies' => 'array',
        'completed_at' => 'date',
        'is_featured' => 'boolean',
    ];

    /**
     * Get all tags for the project (polymorphic).
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'tagable');
    }

    /**
     * Get all media for the project (polymorphic).
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /**
     * Get thumbnail image.
     */
    public function thumbnail()
    {
        return $this->media()->where('type', 'thumbnail')->first();
    }

    /**
     * Get main project image.
     */
    public function mainImage()
    {
        return $this->media()->where('type', 'image')->first();
    }

    /**
     * Get gallery images.
     */
    public function galleryImages()
    {
        return $this->media()->where('type', 'gallery')->get();
    }

    /**
     * Scope a query to only include featured projects.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include completed projects.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to order by custom order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('created_at', 'desc');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}