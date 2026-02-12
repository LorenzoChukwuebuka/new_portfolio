<?php

namespace App\Models;

use App\Models\Post;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
    ];

    /**
     * Get all posts that have this tag.
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'tagable');
    }

    /**
     * Get all projects that have this tag.
     */
    public function projects(): MorphToMany
    {
        return $this->morphedByMany(Project::class, 'tagable');
    }
}