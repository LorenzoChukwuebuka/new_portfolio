<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Cv extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'file_name',
        'file_path',
        'file_size',
        'version',
        'is_active',
        'download_count',
        'description',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'file_size'      => 'integer',
        'download_count' => 'integer',
    ];

    /**
     * Get the full URL to the CV file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the download URL.
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('cv.download', $this->id);
    }

    /**
     * Increment download count.
     */
    public function incrementDownloads()
    {
        $this->increment('download_count');
    }

    /**
     * Set this CV as active and deactivate others.
     */
    public function setAsActive()
    {
        static::where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }

    /**
     * Scope a query to only include active CV.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Delete CV file from storage.
     */
    public function deleteFile(): bool
    {
        return Storage::delete($this->file_path);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Cv $cv) {
            $cv->deleteFile();
        });
    }
}